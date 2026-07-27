<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final class DatatableRendererEndpointRoutingTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_generates_default_endpoints_through_the_router(): void
    {
        $urlGenerator = new LocalizedEndpointUrlGeneratorFixture();
        $renderer = new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            urlGenerator: $urlGenerator,
            csrfTokenManager: new EndpointRoutingCsrfTokenManagerFixture(),
        );
        $definition = new DatatableDefinition('users')
            ->addColumn('email', label: 'Email')
        ;

        $html = $renderer->render($definition, [
            'exportFormats' => ['csv', 'xlsx'],
            'savedViews' => true,
        ]);

        self::assertStringContainsString(
            'data-zhortein--datatable-bundle--datatable-fragments-url-value="/fr/_zhortein/datatable/users/fragments"',
            $html,
        );
        self::assertStringContainsString(
            'data-zhortein--datatable-bundle--datatable-export-url-value="/fr/_zhortein/datatable/users/export"',
            $html,
        );
        self::assertStringContainsString(
            'data-zhortein--datatable-bundle--datatable-export-url-param="/fr/_zhortein/datatable/users/export/xlsx"',
            $html,
        );
        self::assertStringContainsString(
            'data-zhortein--datatable-bundle--datatable-saved-views-url-value="/fr/_zhortein/datatable/users/views?',
            $html,
        );
        self::assertSame([
            'zhortein_datatable_fragments',
            'zhortein_datatable_export',
            'zhortein_datatable_export',
            'zhortein_datatable_views_list',
        ], $urlGenerator->generatedRoutes);
    }

    public function test_it_does_not_replace_explicit_endpoint_urls(): void
    {
        $urlGenerator = new LocalizedEndpointUrlGeneratorFixture();
        $renderer = new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            urlGenerator: $urlGenerator,
            csrfTokenManager: new EndpointRoutingCsrfTokenManagerFixture(),
        );
        $definition = new DatatableDefinition('users')
            ->addColumn('email', label: 'Email')
        ;

        $html = $renderer->render($definition, [
            'fragmentsUrl' => '/application/users/fragments',
            'exportUrl' => '/application/users/export/csv',
            'exportFormats' => ['csv', 'xlsx'],
            'exportUrls' => [
                'csv' => '/application/users/export/csv',
                'xlsx' => '/application/users/export/xlsx',
            ],
            'savedViews' => true,
            'savedViewsUrl' => '/application/users/views',
        ]);

        self::assertStringContainsString(
            'data-zhortein--datatable-bundle--datatable-fragments-url-value="/application/users/fragments"',
            $html,
        );
        self::assertStringContainsString(
            'data-zhortein--datatable-bundle--datatable-export-url-value="/application/users/export/csv"',
            $html,
        );
        self::assertStringContainsString(
            'data-zhortein--datatable-bundle--datatable-export-url-param="/application/users/export/xlsx"',
            $html,
        );
        self::assertStringContainsString(
            'data-zhortein--datatable-bundle--datatable-saved-views-url-value="/application/users/views?',
            $html,
        );
        self::assertSame([], $urlGenerator->generatedRoutes);
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

final class LocalizedEndpointUrlGeneratorFixture implements UrlGeneratorInterface
{
    /**
     * @var list<string>
     */
    public array $generatedRoutes = [];

    /**
     * @param array<mixed> $parameters
     */
    public function generate(
        string $name,
        array $parameters = [],
        int $referenceType = self::ABSOLUTE_PATH,
    ): string {
        $this->generatedRoutes[] = $name;
        $datatableName = $parameters['name'] ?? null;

        if (!is_string($datatableName)) {
            throw new \InvalidArgumentException('The datatable route requires a name.');
        }

        $format = $parameters['format'] ?? 'csv';

        if (!is_string($format)) {
            throw new \InvalidArgumentException('The datatable export route requires a format.');
        }

        return match ($name) {
            'zhortein_datatable_fragments' => sprintf('/fr/_zhortein/datatable/%s/fragments', $datatableName),
            'zhortein_datatable_export' => sprintf(
                '/fr/_zhortein/datatable/%s/export%s',
                $datatableName,
                'csv' === $format ? '' : '/'.$format,
            ),
            'zhortein_datatable_views_list' => sprintf('/fr/_zhortein/datatable/%s/views', $datatableName),
            default => throw new \InvalidArgumentException(sprintf('Unexpected route "%s".', $name)),
        };
    }

    public function setContext(RequestContext $context): void
    {
    }

    public function getContext(): RequestContext
    {
        return new RequestContext();
    }
}

final class EndpointRoutingCsrfTokenManagerFixture implements CsrfTokenManagerInterface
{
    public function getToken(string $tokenId): CsrfToken
    {
        return new CsrfToken($tokenId, 'endpoint-routing-token');
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
        return 'endpoint-routing-token' === $token->getValue();
    }
}
