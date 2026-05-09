<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Action\RowActionRouteParameterResolver;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final class DatatableRendererGlobalActionsTest extends TestCase
{
    public function test_it_renders_global_actions_in_toolbar(): void
    {
        $renderer = new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            urlGenerator: $this->createUrlGeneratorStub(),
            routeParameterResolver: new RowActionRouteParameterResolver(),
        );

        $html = $renderer->render($this->createDefinitionWithGlobalAction());

        self::assertStringContainsString('href="/users/create"', $html);
        self::assertStringContainsString('Create', $html);
        self::assertStringContainsString('btn btn-sm btn-primary', $html);
        self::assertStringContainsString('data-test="create-user"', $html);
    }

    public function test_it_does_not_render_global_actions_without_url_generator(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinitionWithGlobalAction());

        self::assertStringNotContainsString('Create', $html);
        self::assertStringNotContainsString('/users/create', $html);
    }

    private function createDefinitionWithGlobalAction(): DatatableDefinition
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email')
            ->addGlobalAction(
                name: 'create',
                route: 'app_user_create',
                label: 'Create',
                className: 'btn btn-sm btn-primary',
                attributes: ['data-test' => 'create-user'],
            )
        ;

        return $definition;
    }

    private function createUrlGeneratorStub(): UrlGeneratorInterface
    {
        return new class implements UrlGeneratorInterface {
            /**
             * @param array<mixed> $parameters
             */
            public function generate(
                string $name,
                array $parameters = [],
                int $referenceType = self::ABSOLUTE_PATH,
            ): string {
                if ('app_user_create' === $name) {
                    return '/users/create';
                }

                return '/'.$name;
            }

            public function setContext(RequestContext $context): void
            {
            }

            public function getContext(): RequestContext
            {
                return new RequestContext();
            }
        };
    }

    private function createTwigEnvironment(): Environment
    {
        $loader = new FilesystemLoader();
        $loader->addPath(__DIR__.'/../../../templates', 'ZhorteinDatatable');

        return new Environment($loader, [
            'strict_variables' => true,
            'autoescape' => 'html',
        ]);
    }
}
