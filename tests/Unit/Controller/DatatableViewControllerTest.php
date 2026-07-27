<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Zhortein\DatatableBundle\Context\DatatableContextRequestResolver;
use Zhortein\DatatableBundle\Context\DatatableContextTransport;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Contract\DatatableViewOwnerResolverInterface;
use Zhortein\DatatableBundle\Controller\DatatableViewController;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\Registry\DatatableRegistry;
use Zhortein\DatatableBundle\State\DatatableStateUrlSerializer;
use Zhortein\DatatableBundle\View\AllowAllDatatableViewAuthorizationChecker;
use Zhortein\DatatableBundle\View\DatatableViewManager;
use Zhortein\DatatableBundle\View\DenyDatatableViewAuthorizationChecker;
use Zhortein\DatatableBundle\View\InMemoryDatatableViewProvider;

final class DatatableViewControllerTest extends TestCase
{
    public function test_it_exposes_the_complete_versioned_http_lifecycle(): void
    {
        $controller = $this->createController();
        $createResponse = $controller->create(
            $this->createRequest('POST', [
                'name' => 'Active users',
                'state' => $this->createState([
                    'page' => 4,
                    'search' => 'alice',
                    'filters' => ['status' => 'active'],
                ]),
                'includePage' => false,
                'default' => true,
            ]),
            'users',
        );
        $createdPayload = $this->decode($createResponse);
        $created = $createdPayload['view'] ?? null;
        self::assertIsArray($created);
        $createdState = $created['state'] ?? null;
        self::assertIsArray($createdState);

        self::assertSame(201, $createResponse->getStatusCode());
        self::assertSame(DatatableViewController::API_VERSION, $createdPayload['version']);
        self::assertSame('Active users', $created['name']);
        self::assertSame(1, $createdState['page']);
        self::assertTrue($created['default']);
        self::assertFalse($created['includePage']);

        $identifier = $created['id'];
        $revision = $created['revision'];
        self::assertIsString($identifier);
        self::assertIsString($revision);

        $list = $this->decode($controller->list($this->createRequest('GET'), 'users'));
        $listedViews = $list['views'] ?? null;
        self::assertIsArray($listedViews);
        self::assertCount(1, $listedViews);
        $listedView = $listedViews[0] ?? null;
        self::assertIsArray($listedView);
        self::assertArrayNotHasKey('state', $listedView);

        $loaded = $this->decode($controller->load(
            $this->createRequest('GET'),
            'users',
            $identifier,
        ));
        $loadedView = $loaded['view'] ?? null;
        self::assertIsArray($loadedView);
        $loadedState = $loadedView['state'] ?? null;
        self::assertIsArray($loadedState);
        self::assertSame('alice', $loadedState['search']);

        $updatedResponse = $controller->mutate(
            $this->createRequest('PATCH', [
                'operation' => 'update',
                'revision' => $revision,
                'state' => $this->createState(),
                'includePage' => false,
            ]),
            'users',
            $identifier,
        );
        $updatedContent = $updatedResponse->getContent();
        self::assertIsString($updatedContent);
        self::assertStringContainsString('"filters":{}', $updatedContent);
        self::assertStringContainsString('"advancedFilters":{}', $updatedContent);
        $updatedPayload = $this->decode($updatedResponse);
        $updatedView = $updatedPayload['view'] ?? null;
        self::assertIsArray($updatedView);
        $revision = $updatedView['revision'] ?? null;
        self::assertIsString($revision);

        $renamed = $this->decode($controller->mutate(
            $this->createRequest('PATCH', [
                'operation' => 'rename',
                'revision' => $revision,
                'name' => 'Enabled users',
            ]),
            'users',
            $identifier,
        ));
        $renamedView = $renamed['view'] ?? null;
        self::assertIsArray($renamedView);
        self::assertSame('Enabled users', $renamedView['name']);
        self::assertNotSame($revision, $renamedView['revision']);

        $deleteResponse = $controller->delete(
            $this->createRequest('DELETE', [
                'revision' => $renamedView['revision'],
            ]),
            'users',
            $identifier,
        );
        self::assertSame(204, $deleteResponse->getStatusCode());
        $emptyList = $this->decode(
            $controller->list($this->createRequest('GET'), 'users'),
        );
        self::assertSame([], $emptyList['views']);
    }

    public function test_it_returns_empty_state_maps_as_json_objects(): void
    {
        $response = $this->createController()->create(
            $this->createRequest('POST', [
                'name' => 'Unfiltered users',
                'state' => $this->createState(),
            ]),
            'users',
        );
        $content = $response->getContent();

        self::assertIsString($content);
        self::assertStringContainsString('"filters":{}', $content);
        self::assertStringContainsString('"advancedFilters":{}', $content);
        self::assertStringContainsString('"visibleColumns":[]', $content);
        self::assertStringContainsString('"hiddenColumns":[]', $content);
    }

    public function test_stale_revision_returns_a_conflict_response(): void
    {
        $controller = $this->createController();
        $createdPayload = $this->decode($controller->create(
            $this->createRequest('POST', [
                'name' => 'My view',
                'state' => $this->createState(),
            ]),
            'users',
        ));
        $created = $createdPayload['view'] ?? null;
        self::assertIsArray($created);
        $identifier = $created['id'] ?? null;
        self::assertIsString($identifier);
        $response = $controller->mutate(
            $this->createRequest('PATCH', [
                'operation' => 'rename',
                'revision' => 'stale',
                'name' => 'Renamed',
            ]),
            'users',
            $identifier,
        );

        self::assertSame(409, $response->getStatusCode());
        $errorPayload = $this->decode($response);
        $error = $errorPayload['error'] ?? null;
        self::assertIsArray($error);
        self::assertSame('conflict', $error['code']);
    }

    public function test_mutations_require_a_valid_csrf_token(): void
    {
        $controller = $this->createController();
        $request = $this->createRequest('POST', [
            'name' => 'My view',
            'state' => $this->createState(),
        ]);
        $request->headers->remove('X-CSRF-Token');
        $response = $controller->create($request, 'users');

        self::assertSame(403, $response->getStatusCode());
        $payload = $this->decode($response);
        $error = $payload['error'] ?? null;
        self::assertIsArray($error);
        self::assertSame('forbidden', $error['code']);
    }

    public function test_mutations_are_denied_when_csrf_protection_is_unavailable(): void
    {
        $controller = $this->createController(csrfEnabled: false);
        $response = $controller->create(
            $this->createRequest('POST', [
                'name' => 'My view',
                'state' => $this->createState(),
            ]),
            'users',
        );

        self::assertSame(403, $response->getStatusCode());
    }

    public function test_authorization_is_denied_by_default(): void
    {
        $controller = $this->createController(authorized: false);
        $response = $controller->list($this->createRequest('GET'), 'users');

        self::assertSame(403, $response->getStatusCode());
    }

    public function test_invalid_scope_input_returns_a_bad_request_response(): void
    {
        $controller = $this->createController();
        $request = $this->createRequest('GET');
        $request->query->set(
            DatatableContextTransport::INSTANCE_QUERY_PARAMETER,
            ['invalid'],
        );
        $response = $controller->list($request, 'users');

        self::assertSame(400, $response->getStatusCode());
        $payload = $this->decode($response);
        $error = $payload['error'] ?? null;
        self::assertIsArray($error);
        self::assertSame('invalid_request', $error['code']);
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function createState(array $overrides = []): array
    {
        return array_replace([
            'version' => 1,
            'page' => 1,
            'pageSize' => 25,
            'search' => null,
            'sortField' => null,
            'sortDirection' => 'asc',
            'filters' => [],
            'advancedFilters' => [],
            'visibleColumns' => [],
            'hiddenColumns' => [],
        ], $overrides);
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    private function createRequest(string $method, ?array $payload = null): Request
    {
        return Request::create(
            '/_zhortein/datatable/users/views'
                .'?_zd_instance=users-table'
                .'&_zd_view_scope=admin_users'
                .'&_zd_view_locale=fr',
            $method,
            server: ['HTTP_X_CSRF_TOKEN' => 'valid-csrf-token'],
            content: null === $payload ? null : json_encode($payload, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(JsonResponse $response): array
    {
        $content = $response->getContent();
        self::assertIsString($content);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

        return $payload;
    }

    private function createController(
        bool $authorized = true,
        bool $csrfEnabled = true,
    ): DatatableViewController {
        $registry = new DatatableRegistry(
            new ServiceLocator([
                'users' => static fn (): SavedViewControllerTestDatatable => new SavedViewControllerTestDatatable(),
            ]),
            ['users' => SavedViewControllerTestDatatable::class],
        );
        $provider = new InMemoryDatatableViewProvider();

        return new DatatableViewController(
            definitionFactory: new DatatableDefinitionFactory($registry),
            contextRequestResolver: new DatatableContextRequestResolver(
                new DatatableContextTransport('view-controller-test-secret'),
            ),
            ownerResolver: new SavedViewOwnerResolverFixture(),
            manager: new DatatableViewManager(
                $provider,
                $authorized
                    ? new AllowAllDatatableViewAuthorizationChecker()
                    : new DenyDatatableViewAuthorizationChecker(),
            ),
            stateSerializer: new DatatableStateUrlSerializer(),
            csrfTokenManager: $csrfEnabled ? new SavedViewControllerCsrfTokenManagerFixture() : null,
        );
    }
}

final class SavedViewControllerTestDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition->addColumn('email', label: 'Email');
    }
}

final readonly class SavedViewOwnerResolverFixture implements DatatableViewOwnerResolverInterface
{
    public function resolveOwnerIdentifier(Request $request): string
    {
        return 'opaque-owner-identifier';
    }
}

final class SavedViewControllerCsrfTokenManagerFixture implements CsrfTokenManagerInterface
{
    public function getToken(string $tokenId): CsrfToken
    {
        return new CsrfToken($tokenId, 'valid-csrf-token');
    }

    public function refreshToken(string $tokenId): CsrfToken
    {
        return $this->getToken($tokenId);
    }

    public function removeToken(string $tokenId): ?string
    {
        return null;
    }

    public function isTokenValid(CsrfToken $token): bool
    {
        return 'valid-csrf-token' === $token->getValue();
    }
}
