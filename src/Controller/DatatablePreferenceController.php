<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Zhortein\DatatableBundle\Context\DatatableContextRequestResolver;
use Zhortein\DatatableBundle\Context\DatatableContextTransport;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Exception\DatatablePreferenceAccessDeniedException;
use Zhortein\DatatableBundle\Exception\DatatablePreferenceStorageException;
use Zhortein\DatatableBundle\Exception\InvalidDatatableStateException;
use Zhortein\DatatableBundle\Exception\UnsupportedDatatablePreferenceProviderException;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\Preference\DatatablePreferenceCsrfTokenIdGenerator;
use Zhortein\DatatableBundle\Preference\DatatablePreferenceProviderInterface;
use Zhortein\DatatableBundle\Preference\DatatablePreferenceSanitizer;
use Zhortein\DatatableBundle\Preference\DatatablePreferenceSchema;
use Zhortein\DatatableBundle\Preference\DatatablePreferenceScope;
use Zhortein\DatatableBundle\Preference\DatatablePreferenceScopeResolver;
use Zhortein\DatatableBundle\Preference\WritableDatatablePreferenceProviderInterface;
use Zhortein\DatatableBundle\State\DatatableStateUrlSerializer;

final readonly class DatatablePreferenceController
{
    public const int API_VERSION = 1;
    private const int MAX_REQUEST_PAYLOAD_LENGTH = 65536;

    public function __construct(
        private DatatableDefinitionFactory $definitionFactory,
        private DatatableContextRequestResolver $contextRequestResolver,
        private DatatablePreferenceProviderInterface $preferenceProvider,
        private DatatablePreferenceScopeResolver $scopeResolver,
        private DatatablePreferenceSanitizer $sanitizer,
        private DatatableStateUrlSerializer $stateSerializer,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private string $schemaVersion = '1',
    ) {
    }

    public function save(Request $request, string $name): JsonResponse
    {
        try {
            [$definition, $scope] = $this->resolveDefinitionAndScope($request, $name);
            $this->assertWritableProvider();
            $this->assertValidCsrfToken($request, $name, $scope->getInstance());
            $payload = $this->readPayload($request);
            $state = $payload['state'] ?? null;

            if (!is_array($state)) {
                throw new \InvalidArgumentException('The datatable preference state must be an object.');
            }

            $serializedState = json_encode(
                $state,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            );
            $preference = $this->sanitizer->sanitize(
                $definition,
                $this->stateSerializer->deserialize($serializedState),
            );
            $this->getWritableProvider()->savePreference($scope, $preference);

            return $this->success([
                'preference' => $preference->toStorageArray(),
            ]);
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function reset(Request $request, string $name): Response
    {
        try {
            [, $scope] = $this->resolveDefinitionAndScope($request, $name);
            $this->assertWritableProvider();
            $this->assertValidCsrfToken($request, $name, $scope->getInstance());
            $this->getWritableProvider()->resetPreference($scope);

            return new Response(status: Response::HTTP_NO_CONTENT);
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    /**
     * @return array{DatatableDefinition, DatatablePreferenceScope}
     */
    private function resolveDefinitionAndScope(Request $request, string $name): array
    {
        $definition = $this->definitionFactory->create($name);
        $instance = $this->contextRequestResolver->resolve($request, $definition);
        $namespace = $this->readRequiredQueryString(
            $request,
            DatatablePreferenceScope::SCOPE_QUERY_PARAMETER,
        );
        $routeScope = $this->readRequiredQueryString(
            $request,
            DatatablePreferenceScope::ROUTE_QUERY_PARAMETER,
        );
        $locale = $this->readRequiredQueryString(
            $request,
            DatatablePreferenceScope::LOCALE_QUERY_PARAMETER,
        );
        $contextToken = $this->readOptionalQueryString(
            $request,
            DatatableContextTransport::CONTEXT_QUERY_PARAMETER,
        );
        $scope = $this->scopeResolver->resolve(
            request: $request,
            definition: $definition,
            instance: $instance,
            namespace: $namespace,
            locale: $locale,
            schemaVersion: DatatablePreferenceSchema::version($definition, $this->schemaVersion),
            contextFingerprint: null === $contextToken ? null : hash('sha256', $contextToken),
            routeScope: $routeScope,
        );

        if (null === $scope) {
            throw new DatatablePreferenceAccessDeniedException('A datatable preference owner is required.');
        }

        return [$definition, $scope];
    }

    /**
     * @return array<string, mixed>
     */
    private function readPayload(Request $request): array
    {
        $content = $request->getContent();

        if ('' === $content || self::MAX_REQUEST_PAYLOAD_LENGTH < strlen($content)) {
            throw new \InvalidArgumentException('The datatable preference request body has an invalid length.');
        }

        $payload = json_decode($content, true, 16, JSON_THROW_ON_ERROR);

        if (!is_array($payload)) {
            throw new \InvalidArgumentException('The datatable preference request body must be a JSON object.');
        }

        foreach (array_keys($payload) as $key) {
            if (!is_string($key)) {
                throw new \InvalidArgumentException('The datatable preference request body must use string keys.');
            }
        }

        /** @var array<string, mixed> $payload */
        return $payload;
    }

    private function assertValidCsrfToken(
        Request $request,
        string $datatableName,
        string $instance,
    ): void {
        $token = $request->headers->get('X-CSRF-Token');

        if (
            !is_string($token)
            || '' === $token
            || !$this->csrfTokenManager->isTokenValid(new CsrfToken(
                DatatablePreferenceCsrfTokenIdGenerator::generate($datatableName, $instance),
                $token,
            ))
        ) {
            throw new DatatablePreferenceAccessDeniedException('The datatable preference CSRF token is invalid.');
        }
    }

    private function assertWritableProvider(): void
    {
        if (!$this->preferenceProvider instanceof WritableDatatablePreferenceProviderInterface) {
            throw new UnsupportedDatatablePreferenceProviderException('The configured datatable preference provider is read-only.');
        }
    }

    private function getWritableProvider(): WritableDatatablePreferenceProviderInterface
    {
        $this->assertWritableProvider();

        /** @var WritableDatatablePreferenceProviderInterface $provider */
        $provider = $this->preferenceProvider;

        return $provider;
    }

    private function readRequiredQueryString(Request $request, string $name): string
    {
        $value = $this->readOptionalQueryString($request, $name);

        if (null === $value) {
            throw new \InvalidArgumentException(sprintf('The datatable preference query parameter "%s" is required.', $name));
        }

        return $value;
    }

    private function readOptionalQueryString(Request $request, string $name): ?string
    {
        $value = $request->query->all()[$name] ?? null;

        if (null === $value) {
            return null;
        }

        if (!is_string($value) || '' === trim($value)) {
            throw new \InvalidArgumentException(sprintf('The datatable preference query parameter "%s" must be a non-empty string.', $name));
        }

        return trim($value);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function success(array $payload): JsonResponse
    {
        return new JsonResponse(array_merge(['version' => self::API_VERSION], $payload));
    }

    private function error(\Throwable $exception): JsonResponse
    {
        [$status, $code] = match (true) {
            $exception instanceof DatatablePreferenceAccessDeniedException => [
                Response::HTTP_FORBIDDEN,
                'forbidden',
            ],
            $exception instanceof UnsupportedDatatablePreferenceProviderException => [
                Response::HTTP_NOT_IMPLEMENTED,
                'provider_unavailable',
            ],
            $exception instanceof DatatablePreferenceStorageException => [
                Response::HTTP_SERVICE_UNAVAILABLE,
                'storage_unavailable',
            ],
            $exception instanceof InvalidDatatableStateException,
            $exception instanceof BadRequestHttpException,
            $exception instanceof \InvalidArgumentException,
            $exception instanceof \JsonException => [
                Response::HTTP_BAD_REQUEST,
                'invalid_request',
            ],
            default => [
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'internal_error',
            ],
        };

        return new JsonResponse([
            'version' => self::API_VERSION,
            'error' => [
                'code' => $code,
                'message' => Response::HTTP_INTERNAL_SERVER_ERROR === $status
                    ? 'The datatable preference operation failed.'
                    : $exception->getMessage(),
            ],
        ], $status);
    }
}
