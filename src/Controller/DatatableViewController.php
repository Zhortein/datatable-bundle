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
use Zhortein\DatatableBundle\Contract\DatatableViewOwnerResolverInterface;
use Zhortein\DatatableBundle\Exception\DatatableViewAccessDeniedException;
use Zhortein\DatatableBundle\Exception\DatatableViewConflictException;
use Zhortein\DatatableBundle\Exception\DatatableViewNotFoundException;
use Zhortein\DatatableBundle\Exception\InvalidDatatableStateException;
use Zhortein\DatatableBundle\Exception\UnsupportedDatatableViewProviderException;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\State\DatatableStateUrlSerializer;
use Zhortein\DatatableBundle\View\DatatableView;
use Zhortein\DatatableBundle\View\DatatableViewCsrfTokenIdGenerator;
use Zhortein\DatatableBundle\View\DatatableViewManager;
use Zhortein\DatatableBundle\View\DatatableViewMetadata;
use Zhortein\DatatableBundle\View\DatatableViewScope;
use Zhortein\DatatableBundle\View\DatatableViewState;

final readonly class DatatableViewController
{
    public const int API_VERSION = 1;
    private const int MAX_REQUEST_PAYLOAD_LENGTH = 65536;

    public function __construct(
        private DatatableDefinitionFactory $definitionFactory,
        private DatatableContextRequestResolver $contextRequestResolver,
        private DatatableViewOwnerResolverInterface $ownerResolver,
        private DatatableViewManager $manager,
        private DatatableStateUrlSerializer $stateSerializer,
        private ?CsrfTokenManagerInterface $csrfTokenManager = null,
    ) {
    }

    public function list(Request $request, string $name): JsonResponse
    {
        try {
            [$scope, $ownerIdentifier] = $this->resolveScopeAndOwner($request, $name);
            $views = array_map(
                static fn (DatatableViewMetadata $metadata): array => $metadata->toArray(),
                $this->manager->list($scope, $ownerIdentifier),
            );

            return $this->success(['views' => $views]);
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function load(Request $request, string $name, string $viewIdentifier): JsonResponse
    {
        try {
            [$scope, $ownerIdentifier] = $this->resolveScopeAndOwner($request, $name);

            return $this->viewResponse($this->manager->load(
                $scope,
                $ownerIdentifier,
                $viewIdentifier,
            ));
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function create(Request $request, string $name): JsonResponse
    {
        try {
            [$scope, $ownerIdentifier] = $this->resolveScopeAndOwner($request, $name);
            $this->assertValidCsrfToken($request, $scope);
            $payload = $this->readPayload($request);
            $view = $this->manager->create(
                $scope,
                $ownerIdentifier,
                $this->readRequiredString($payload, 'name'),
                $this->readViewState($payload),
                $this->readBoolean($payload, 'default', false),
            );

            return $this->viewResponse($view, Response::HTTP_CREATED);
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function mutate(
        Request $request,
        string $name,
        string $viewIdentifier,
    ): JsonResponse {
        try {
            [$scope, $ownerIdentifier] = $this->resolveScopeAndOwner($request, $name);
            $this->assertValidCsrfToken($request, $scope);
            $payload = $this->readPayload($request);
            $operation = $this->readRequiredString($payload, 'operation');
            $revision = $this->readRequiredString($payload, 'revision');

            $view = match ($operation) {
                'rename' => $this->manager->rename(
                    $scope,
                    $ownerIdentifier,
                    $viewIdentifier,
                    $this->readRequiredString($payload, 'name'),
                    $revision,
                ),
                'update' => $this->manager->update(
                    $scope,
                    $ownerIdentifier,
                    $viewIdentifier,
                    $this->readViewState($payload),
                    $revision,
                ),
                'set_default' => $this->manager->setDefault(
                    $scope,
                    $ownerIdentifier,
                    $viewIdentifier,
                    $revision,
                ),
                default => throw new \InvalidArgumentException(sprintf(
                    'The datatable view operation "%s" is unsupported.',
                    $operation,
                )),
            };

            return $this->viewResponse($view);
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function delete(
        Request $request,
        string $name,
        string $viewIdentifier,
    ): Response {
        try {
            [$scope, $ownerIdentifier] = $this->resolveScopeAndOwner($request, $name);
            $this->assertValidCsrfToken($request, $scope);
            $payload = $this->readPayload($request);
            $this->manager->delete(
                $scope,
                $ownerIdentifier,
                $viewIdentifier,
                $this->readRequiredString($payload, 'revision'),
            );

            return new Response(status: Response::HTTP_NO_CONTENT);
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    /**
     * @return array{DatatableViewScope, string|null}
     */
    private function resolveScopeAndOwner(Request $request, string $name): array
    {
        $definition = $this->definitionFactory->create($name);
        $instance = $this->contextRequestResolver->resolve($request, $definition);
        $contextToken = $this->readOptionalQueryString(
            $request,
            DatatableContextTransport::CONTEXT_QUERY_PARAMETER,
        );
        $namespace = $this->readOptionalQueryString(
            $request,
            DatatableViewScope::SCOPE_QUERY_PARAMETER,
        ) ?? 'default';
        $locale = $this->readOptionalQueryString(
            $request,
            DatatableViewScope::LOCALE_QUERY_PARAMETER,
        ) ?? $request->getLocale();
        $ownerIdentifier = $this->ownerResolver->resolveOwnerIdentifier($request);

        if (null !== $ownerIdentifier) {
            $ownerIdentifier = trim($ownerIdentifier);

            if (
                '' === $ownerIdentifier
                || 512 < strlen($ownerIdentifier)
                || 1 === preg_match('/[\x00-\x1F\x7F]/', $ownerIdentifier)
            ) {
                throw new \InvalidArgumentException('The datatable view owner identifier is invalid.');
            }
        }

        return [
            DatatableViewScope::create(
                datatableName: $name,
                instance: $instance,
                namespace: $namespace,
                locale: '' === trim($locale) ? 'und' : $locale,
                contextFingerprint: null === $contextToken ? null : hash('sha256', $contextToken),
            ),
            $ownerIdentifier,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function readViewState(array $payload): DatatableViewState
    {
        $state = $payload['state'] ?? null;

        if (!is_array($state)) {
            throw new \InvalidArgumentException('The datatable view state must be an object.');
        }

        $serializedState = json_encode(
            $state,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        return DatatableViewState::create(
            $this->stateSerializer->deserialize($serializedState),
            $this->readBoolean($payload, 'includePage', false),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function readPayload(Request $request): array
    {
        $content = $request->getContent();

        if (
            !is_string($content)
            || '' === $content
            || self::MAX_REQUEST_PAYLOAD_LENGTH < strlen($content)
        ) {
            throw new \InvalidArgumentException('The datatable view request body has an invalid length.');
        }

        $payload = json_decode($content, true, 16, JSON_THROW_ON_ERROR);

        if (!is_array($payload)) {
            throw new \InvalidArgumentException('The datatable view request body must be a JSON object.');
        }

        foreach (array_keys($payload) as $key) {
            if (!is_string($key)) {
                throw new \InvalidArgumentException('The datatable view request body must use string keys.');
            }
        }

        /** @var array<string, mixed> $payload */
        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function readRequiredString(array $payload, string $name): string
    {
        $value = $payload[$name] ?? null;

        if (!is_string($value) || '' === trim($value)) {
            throw new \InvalidArgumentException(sprintf(
                'The datatable view field "%s" must be a non-empty string.',
                $name,
            ));
        }

        return trim($value);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function readBoolean(array $payload, string $name, bool $default): bool
    {
        $value = $payload[$name] ?? $default;

        if (!is_bool($value)) {
            throw new \InvalidArgumentException(sprintf(
                'The datatable view field "%s" must be a boolean.',
                $name,
            ));
        }

        return $value;
    }

    private function assertValidCsrfToken(Request $request, DatatableViewScope $scope): void
    {
        $token = $request->headers->get('X-CSRF-Token');

        if (
            null === $this->csrfTokenManager
            || !is_string($token)
            || '' === $token
            || !$this->csrfTokenManager->isTokenValid(new CsrfToken(
                DatatableViewCsrfTokenIdGenerator::generate(
                    $scope->getDatatableName(),
                    $scope->getInstance(),
                ),
                $token,
            ))
        ) {
            throw new DatatableViewAccessDeniedException('The datatable view CSRF token is invalid.');
        }
    }

    private function readOptionalQueryString(Request $request, string $name): ?string
    {
        $value = $request->query->all()[$name] ?? null;

        if (null === $value) {
            return null;
        }

        if (!is_string($value) || '' === trim($value)) {
            throw new \InvalidArgumentException(sprintf(
                'The datatable view query parameter "%s" must be a non-empty string.',
                $name,
            ));
        }

        return trim($value);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function success(array $payload, int $status = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse(
            array_merge(['version' => self::API_VERSION], $payload),
            $status,
        );
    }

    private function viewResponse(
        DatatableView $view,
        int $status = Response::HTTP_OK,
    ): JsonResponse {
        return $this->success(['view' => $view->toArray()], $status);
    }

    private function error(\Throwable $exception): JsonResponse
    {
        [$status, $code] = match (true) {
            $exception instanceof DatatableViewAccessDeniedException => [
                Response::HTTP_FORBIDDEN,
                'forbidden',
            ],
            $exception instanceof DatatableViewNotFoundException => [
                Response::HTTP_NOT_FOUND,
                'not_found',
            ],
            $exception instanceof DatatableViewConflictException => [
                Response::HTTP_CONFLICT,
                'conflict',
            ],
            $exception instanceof UnsupportedDatatableViewProviderException => [
                Response::HTTP_NOT_IMPLEMENTED,
                'provider_unavailable',
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

        return $this->success([
            'error' => [
                'code' => $code,
                'message' => Response::HTTP_INTERNAL_SERVER_ERROR === $status
                    ? 'The datatable view operation failed.'
                    : $exception->getMessage(),
            ],
        ], $status);
    }
}
