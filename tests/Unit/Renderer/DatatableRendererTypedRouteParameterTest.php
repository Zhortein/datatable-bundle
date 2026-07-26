<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Action\RowActionRouteParameterResolver;
use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Definition\RouteParameter;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Result\DatatableResult;

final class DatatableRendererTypedRouteParameterTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_resolves_typed_parameters_for_every_action_kind(): void
    {
        $definition = new DatatableDefinition('articles');
        $definition
            ->setContext(new DatatableContext(['locale' => 'fr']))
            ->addColumn('title', label: 'Title')
            ->addRowAction(
                name: 'preview',
                route: 'app_article_preview',
                routeParameters: [
                    'id' => RouteParameter::row('e.id'),
                    'locale' => RouteParameter::context('locale'),
                ],
            )
            ->addGlobalAction(
                name: 'create',
                route: 'app_article_create',
                routeParameters: [
                    'locale' => RouteParameter::context('locale'),
                    'format' => RouteParameter::literal('html'),
                ],
            )
            ->addBulkAction(
                name: 'publish',
                route: 'app_article_publish',
                routeParameters: [
                    'locale' => RouteParameter::context('locale'),
                    'format' => RouteParameter::literal('html'),
                ],
            )
        ;

        $renderer = new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            urlGenerator: new RouteParameterUrlGeneratorFixture(),
            routeParameterResolver: new RowActionRouteParameterResolver(),
        );

        $shell = $renderer->render($definition);
        $body = $renderer->renderBody($definition, new DatatableResult(
            rows: [['e_id' => 42, 'title' => 'Typed routes']],
            totalItems: 1,
            filteredItems: 1,
        ));

        self::assertStringContainsString('/app_article_create?locale=fr&amp;format=html', $shell);
        self::assertStringContainsString('/app_article_publish?locale=fr&amp;format=html', $shell);
        self::assertStringContainsString('/app_article_preview?id=42&amp;locale=fr', $body);
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

final class RouteParameterUrlGeneratorFixture implements UrlGeneratorInterface
{
    /**
     * @param array<mixed> $parameters
     */
    public function generate(
        string $name,
        array $parameters = [],
        int $referenceType = self::ABSOLUTE_PATH,
    ): string {
        $query = http_build_query($parameters, arg_separator: '&', encoding_type: PHP_QUERY_RFC3986);

        return '/'.$name.('' === $query ? '' : '?'.$query);
    }

    public function setContext(RequestContext $context): void
    {
    }

    public function getContext(): RequestContext
    {
        return new RequestContext();
    }
}
