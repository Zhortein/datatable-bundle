<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Context\DatatableContextTransport;
use Zhortein\DatatableBundle\Contract\DatatablePreferenceIdentityResolverInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterType;
use Zhortein\DatatableBundle\Preference\CacheDatatablePreferenceProvider;
use Zhortein\DatatableBundle\Preference\DatatablePreference;
use Zhortein\DatatableBundle\Preference\DatatablePreferenceSchema;
use Zhortein\DatatableBundle\Preference\DatatablePreferenceScope;
use Zhortein\DatatableBundle\Preference\DatatablePreferenceScopeResolver;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\State\DatatableStateUrlSerializer;

final class DatatableRendererPreferencesTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_scoped_cache_preferences_apply_below_runtime_options(): void
    {
        $request = Request::create('/admin/users');
        $request->attributes->set('_route', 'admin_users');
        $request->setLocale('fr');
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $scopeResolver = new DatatablePreferenceScopeResolver(
            $requestStack,
            new RendererPreferenceIdentityResolver(),
        );
        $provider = new CacheDatatablePreferenceProvider(new ArrayAdapter(), 3600);
        $definition = new DatatableDefinition('users');
        $definition
            ->addColumn('email', label: 'Email')
            ->addColumn('phone', label: 'Phone')
            ->addFilter(
                'status',
                'status',
                type: FilterType::Choice,
                choices: ['Active' => 'active'],
                preferenceSafe: true,
            )
        ;
        $scope = DatatablePreferenceScope::create(
            ownerIdentifier: 'user-42',
            datatableName: 'users',
            instance: 'admin-users',
            routeScope: 'admin_users',
            locale: 'fr',
            schemaVersion: DatatablePreferenceSchema::version($definition),
        );
        $provider->savePreference($scope, DatatablePreference::create(
            pageSize: 50,
            visibleColumns: ['email'],
            hiddenColumns: ['phone'],
            filters: ['status' => 'active'],
        ));
        $renderer = new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            csrfTokenManager: new RendererPreferenceCsrfTokenManager(),
            contextTransport: new DatatableContextTransport('preference-test-secret'),
            stateUrlSerializer: new DatatableStateUrlSerializer(),
            preferenceProvider: $provider,
            preferenceScopeResolver: $scopeResolver,
        );

        $html = $renderer->render($definition, [
            'instance' => 'admin-users',
            'preferences' => true,
            'pageSize' => 10,
        ]);

        self::assertStringContainsString(
            'data-zhortein--datatable-bundle--datatable-page-size-value="10"',
            $html,
        );
        self::assertStringContainsString('Email', $html);
        self::assertStringNotContainsString(
            'data-zhortein--datatable-bundle--datatable-field-param="phone"',
            $html,
        );
        self::assertStringContainsString('value="active" selected', $html);
        self::assertStringContainsString(
            'data-zhortein--datatable-bundle--datatable-preferences-csrf-token-value="preference-token"',
            $html,
        );
        self::assertStringContainsString(
            rawurlencode(DatatablePreferenceScope::ROUTE_QUERY_PARAMETER).'=admin_users',
            html_entity_decode($html),
        );
    }

    private function createTwigEnvironment(): Environment
    {
        $loader = new FilesystemLoader();
        $loader->addPath(__DIR__.'/../../../templates', 'ZhorteinDatatable');
        $twig = new Environment($loader, [
            'strict_variables' => true,
            'autoescape' => 'html',
        ]);

        $this->addTranslationExtension($twig);

        return $twig;
    }
}

final readonly class RendererPreferenceIdentityResolver implements DatatablePreferenceIdentityResolverInterface
{
    public function resolvePreferenceOwnerIdentifier(Request $request): ?string
    {
        return 'user-42';
    }
}

final class RendererPreferenceCsrfTokenManager implements CsrfTokenManagerInterface
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
