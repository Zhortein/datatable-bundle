<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Zhortein\DatatableBundle\Context\DatatableContextRequestResolver;
use Zhortein\DatatableBundle\Context\DatatableContextTransport;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Contract\DatatablePreferenceIdentityResolverInterface;
use Zhortein\DatatableBundle\Controller\DatatablePreferenceController;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\Preference\CacheDatatablePreferenceProvider;
use Zhortein\DatatableBundle\Preference\DatatablePreferenceSanitizer;
use Zhortein\DatatableBundle\Preference\DatatablePreferenceSchema;
use Zhortein\DatatableBundle\Preference\DatatablePreferenceScope;
use Zhortein\DatatableBundle\Preference\DatatablePreferenceScopeResolver;
use Zhortein\DatatableBundle\Registry\DatatableRegistry;
use Zhortein\DatatableBundle\Sorting\SortCriterion;
use Zhortein\DatatableBundle\State\DatatableStateUrlSerializer;

final class DatatablePreferenceControllerTest extends TestCase
{
    public function test_save_filters_the_state_and_reset_deletes_the_scoped_preference(): void
    {
        $datatable = new PreferenceControllerDatatable();
        $registry = new DatatableRegistry(
            new ServiceLocator([
                'users' => static fn (): PreferenceControllerDatatable => $datatable,
            ]),
            ['users' => PreferenceControllerDatatable::class],
        );
        $definitionFactory = new DatatableDefinitionFactory($registry);
        $transport = new DatatableContextTransport('preference-controller-secret');
        $requestStack = new RequestStack();
        $scopeResolver = new DatatablePreferenceScopeResolver(
            $requestStack,
            new ControllerPreferenceIdentityResolver(),
        );
        $provider = new CacheDatatablePreferenceProvider(new ArrayAdapter(), 3600);
        $controller = new DatatablePreferenceController(
            definitionFactory: $definitionFactory,
            contextRequestResolver: new DatatableContextRequestResolver($transport),
            preferenceProvider: $provider,
            scopeResolver: $scopeResolver,
            sanitizer: new DatatablePreferenceSanitizer(100),
            stateSerializer: new DatatableStateUrlSerializer(),
            csrfTokenManager: new ControllerPreferenceCsrfTokenManager(),
        );
        $request = $this->createRequest('POST', [
            'state' => [
                'version' => 1,
                'page' => 4,
                'pageSize' => 500,
                'search' => 'not persisted',
                'sortField' => 'email',
                'sortDirection' => 'desc',
                'sorts' => [
                    ['field' => 'email', 'direction' => 'desc'],
                    ['field' => 'computed', 'direction' => 'asc'],
                ],
                'filters' => [
                    'status' => 'active',
                    'secret' => 'classified',
                ],
                'advancedFilters' => [
                    'operator' => 'and',
                    'conditions' => [],
                ],
                'visibleColumns' => ['email', 'unknown'],
                'hiddenColumns' => ['phone', 'unknown'],
            ],
        ]);

        $response = $controller->save($request, 'users');

        self::assertSame(200, $response->getStatusCode());
        $preference = $provider->getPreferenceForScope($this->createScope(
            $definitionFactory->create('users'),
        ));
        self::assertSame(100, $preference->getPageSize());
        self::assertSame(['email'], $preference->getVisibleColumns());
        self::assertSame(['phone'], $preference->getHiddenColumns());
        self::assertSame(['status' => 'active'], $preference->getFilters());
        self::assertSame(['email'], array_map(
            static fn (SortCriterion $sort): string => $sort->getField(),
            $preference->getSorts(),
        ));

        $resetResponse = $controller->reset($this->createRequest('DELETE'), 'users');

        self::assertSame(204, $resetResponse->getStatusCode());
        self::assertTrue(
            $provider->getPreferenceForScope($this->createScope(
                $definitionFactory->create('users'),
            ))->isEmpty(),
        );
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    private function createRequest(string $method, ?array $payload = null): Request
    {
        $query = http_build_query([
            '_zd_instance' => 'admin-users',
            DatatablePreferenceScope::SCOPE_QUERY_PARAMETER => 'tenant-a',
            DatatablePreferenceScope::ROUTE_QUERY_PARAMETER => 'admin_users',
            DatatablePreferenceScope::LOCALE_QUERY_PARAMETER => 'fr',
        ]);
        $request = Request::create(
            '/_zhortein/datatable/users/preferences?'.$query,
            $method,
            server: [
                'HTTP_X_CSRF_TOKEN' => 'preference-token',
            ],
            content: null === $payload ? null : json_encode($payload, JSON_THROW_ON_ERROR),
        );
        $request->setLocale('fr');

        return $request;
    }

    private function createScope(DatatableDefinition $definition): DatatablePreferenceScope
    {
        return DatatablePreferenceScope::create(
            ownerIdentifier: 'user-42',
            datatableName: 'users',
            instance: 'admin-users',
            routeScope: 'admin_users',
            namespace: 'tenant-a',
            locale: 'fr',
            schemaVersion: DatatablePreferenceSchema::version($definition),
        );
    }
}

final class PreferenceControllerDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->addColumn('email')
            ->addColumn('phone')
            ->addComputedColumn('computed', 'computed_resolver')
            ->addFilter('status', 'status', preferenceSafe: true)
            ->addFilter('secret', 'secret')
        ;
    }
}

final readonly class ControllerPreferenceIdentityResolver implements DatatablePreferenceIdentityResolverInterface
{
    public function resolvePreferenceOwnerIdentifier(Request $request): ?string
    {
        return 'user-42';
    }
}

final class ControllerPreferenceCsrfTokenManager implements CsrfTokenManagerInterface
{
    public function getToken(string $tokenId): CsrfToken
    {
        return new CsrfToken($tokenId, 'preference-token');
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
        return 'preference-token' === $token->getValue();
    }
}
