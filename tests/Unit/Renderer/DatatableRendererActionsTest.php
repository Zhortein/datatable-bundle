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
use Zhortein\DatatableBundle\Result\DatatableResult;

final class DatatableRendererActionsTest extends TestCase
{
    public function test_it_renders_row_actions(): void
    {
        $definition = $this->createDefinitionWithActions();

        $result = new DatatableResult(
            rows: [
                [
                    'e_id' => 42,
                    'email' => 'alice@example.test',
                ],
            ],
            totalItems: 1,
        );

        $urlGenerator = $this->createUrlGeneratorStub();

        $renderer = new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            urlGenerator: $urlGenerator,
            routeParameterResolver: new RowActionRouteParameterResolver(),
        );

        $html = $renderer->renderBody($definition, $result);

        self::assertStringContainsString('href="/users/42"', $html);
        self::assertStringContainsString('View', $html);
        self::assertStringContainsString('btn btn-sm btn-primary', $html);
        self::assertStringContainsString('data-test="view-user"', $html);
    }

    public function test_it_renders_actions_header_when_definition_has_row_actions(): void
    {
        $renderer = new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            urlGenerator: $this->createUrlGeneratorStub(),
            routeParameterResolver: new RowActionRouteParameterResolver(),
        );

        $html = $renderer->render($this->createDefinitionWithActions());

        self::assertStringContainsString('Actions', $html);
    }

    public function test_empty_state_colspan_includes_actions_column(): void
    {
        $renderer = new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            urlGenerator: $this->createUrlGeneratorStub(),
            routeParameterResolver: new RowActionRouteParameterResolver(),
        );

        $html = $renderer->renderEmptyBody($this->createDefinitionWithActions());

        self::assertStringContainsString('colspan="2"', $html);
    }

    public function test_it_skips_non_get_actions_for_now(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email')
            ->addRowAction(
                name: 'delete',
                route: 'app_user_delete',
                label: 'Delete',
                httpMethod: 'DELETE',
                routeParameters: ['id' => 'e.id'],
            )
        ;

        $result = new DatatableResult(
            rows: [
                [
                    'e_id' => 42,
                    'email' => 'alice@example.test',
                ],
            ],
            totalItems: 1,
        );

        $renderer = new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            urlGenerator: $this->createUrlGeneratorStub(),
            routeParameterResolver: new RowActionRouteParameterResolver(),
        );

        $html = $renderer->renderBody($definition, $result);

        self::assertStringNotContainsString('Delete', $html);
        self::assertStringNotContainsString('app_user_delete', $html);
    }

    private function createDefinitionWithActions(): DatatableDefinition
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email')
            ->addRowAction(
                name: 'view',
                route: 'app_user_show',
                label: 'View',
                className: 'btn btn-sm btn-primary',
                routeParameters: ['id' => 'e.id'],
                attributes: ['data-test' => 'view-user'],
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
                $id = $parameters['id'] ?? null;

                if ('app_user_show' === $name && (is_string($id) || is_int($id))) {
                    return '/users/'.$id;
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
