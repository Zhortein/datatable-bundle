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
use Zhortein\DatatableBundle\Icon\IconResolver;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Result\DatatableResult;

final class DatatableRendererIconResolutionTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_explicit_action_icon_still_works(): void
    {
        $definition = new DatatableDefinition('users');
        $definition->addRowAction(
            name: 'view',
            route: 'app_user_show',
            icon: 'explicit-icon',
        );

        $html = $this->createRenderer()->renderBody($definition, $this->createEmptyResult());

        self::assertStringContainsString('explicit-icon', $html);
    }

    public function test_default_row_action_icon_resolved(): void
    {
        $definition = new DatatableDefinition('users');
        $definition->addRowAction(
            name: 'edit',
            route: 'app_user_edit',
        );

        $html = $this->createRenderer()->renderBody($definition, $this->createEmptyResult());

        self::assertStringContainsString('bi bi-pencil', $html);
    }

    public function test_default_global_action_icon_resolved(): void
    {
        $definition = new DatatableDefinition('users');
        $definition->addGlobalAction(
            name: 'create',
            route: 'app_user_create',
        );

        $html = $this->createRenderer()->render($definition);

        self::assertStringContainsString('bi bi-plus-lg', $html);
    }

    public function test_default_bulk_action_icon_resolved(): void
    {
        $definition = new DatatableDefinition('users');
        $definition->addBulkAction(
            name: 'process',
            route: 'app_user_bulk_process',
        );

        $html = $this->createRenderer()->render($definition);

        self::assertStringContainsString('bi bi-collection', $html);
    }

    public function test_labels_remain_visible(): void
    {
        $definition = new DatatableDefinition('users');
        $definition->addRowAction(
            name: 'view',
            route: 'app_user_show',
            label: 'View Details',
        );

        $html = $this->createRenderer()->renderBody($definition, $this->createEmptyResult());

        self::assertStringContainsString('bi bi-eye', $html);
        self::assertStringContainsString('View Details', $html);
    }

    private function createRenderer(): DatatableRenderer
    {
        return new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            iconResolver: new IconResolver(),
            urlGenerator: $this->createUrlGeneratorStub(),
            routeParameterResolver: new RowActionRouteParameterResolver(),
        );
    }

    private function createEmptyResult(): DatatableResult
    {
        return new DatatableResult(
            rows: [['id' => 1]],
            totalItems: 1,
        );
    }

    private function createUrlGeneratorStub(): UrlGeneratorInterface
    {
        return new class implements UrlGeneratorInterface {
            /**
             * @param array<mixed> $parameters
             */
            public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
            {
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

        $twig = new Environment($loader, [
            'strict_variables' => true,
            'autoescape' => 'html',
        ]);

        $this->addTranslationExtension($twig);

        return $twig;
    }
}
