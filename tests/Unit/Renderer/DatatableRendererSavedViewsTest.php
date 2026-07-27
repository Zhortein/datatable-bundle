<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Context\DatatableContextTransport;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\State\DatatableStateUrlSerializer;
use Zhortein\DatatableBundle\View\DatatableViewScope;

final class DatatableRendererSavedViewsTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_named_views_are_opt_in_and_scoped_per_instance_locale_and_context(): void
    {
        $definition = new DatatableDefinition('orders');
        $definition
            ->setContext(new DatatableContext(
                ['tenant' => 'acme'],
                ['tenant'],
            ))
            ->addColumn('reference', label: 'Reference')
        ;
        $renderer = new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            csrfTokenManager: new SavedViewCsrfTokenManagerFixture(),
            contextTransport: new DatatableContextTransport('saved-view-test-secret'),
            stateUrlSerializer: new DatatableStateUrlSerializer(),
        );

        $html = $renderer->render($definition, [
            'instance' => 'open-orders',
            'savedViews' => true,
            'savedViewsScope' => 'admin_orders',
            'savedViewsLocale' => 'fr',
            'savedViewsIncludePage' => true,
            'savedViewsUrl' => '/custom/views?existing=value#panel',
        ]);

        $viewsUrl = $this->extractAttribute(
            $html,
            'data-zhortein--datatable-bundle--datatable-saved-views-url-value',
        );
        $query = parse_url($viewsUrl, PHP_URL_QUERY);

        self::assertIsString($query);
        parse_str($query, $parameters);
        self::assertSame('value', $parameters['existing']);
        self::assertSame('open-orders', $parameters[DatatableContextTransport::INSTANCE_QUERY_PARAMETER]);
        self::assertSame('admin_orders', $parameters[DatatableViewScope::SCOPE_QUERY_PARAMETER]);
        self::assertSame('fr', $parameters[DatatableViewScope::LOCALE_QUERY_PARAMETER]);
        self::assertArrayHasKey(DatatableContextTransport::CONTEXT_QUERY_PARAMETER, $parameters);
        self::assertStringEndsWith('#panel', $viewsUrl);
        self::assertStringContainsString(
            'data-zhortein--datatable-bundle--datatable-saved-views-csrf-token-value="saved-view-token"',
            $html,
        );
        self::assertStringContainsString(
            'data-zhortein--datatable-bundle--datatable-saved-views-include-page-value="true"',
            $html,
        );
        self::assertStringContainsString('zhortein-datatable__saved-views', $html);
    }

    public function test_named_view_markup_is_absent_by_default(): void
    {
        $definition = new DatatableDefinition('orders')
            ->addColumn('reference', label: 'Reference')
        ;
        $renderer = new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            csrfTokenManager: new SavedViewCsrfTokenManagerFixture(),
            stateUrlSerializer: new DatatableStateUrlSerializer(),
        );

        $html = $renderer->render($definition);

        self::assertStringNotContainsString('datatable-saved-views-url-value', $html);
        self::assertStringNotContainsString('datatable-saved-view-error-message-value', $html);
        self::assertStringNotContainsString('zhortein-datatable__saved-views', $html);
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

    private function extractAttribute(string $html, string $attribute): string
    {
        $matched = preg_match(sprintf('/%s="([^"]+)"/', preg_quote($attribute, '/')), $html, $matches);

        self::assertSame(1, $matched);

        return html_entity_decode($matches[1]);
    }
}

final class SavedViewCsrfTokenManagerFixture implements CsrfTokenManagerInterface
{
    public function getToken(string $tokenId): CsrfToken
    {
        return new CsrfToken($tokenId, 'saved-view-token');
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
        return 'saved-view-token' === $token->getValue();
    }
}
