<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final class DatatableRendererColumnVisibilityControlsTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_renders_column_visibility_controls_by_default(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition());

        self::assertStringContainsString('zhortein-datatable__column-visibility', $html);
        self::assertStringContainsString('Columns', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-column-visibility-control="true"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-column-name="e.email"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-column-name="e.createdAt"', $html);
        self::assertStringContainsString('data-action="change->zhortein--datatable-bundle--datatable#changeColumnVisibility"', $html);
        self::assertStringContainsString('Email', $html);
        self::assertStringContainsString('Created at', $html);
    }

    public function test_it_does_not_render_definition_hidden_columns_as_toggleable_controls(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition());

        self::assertStringNotContainsString('data-zhortein--datatable-bundle--datatable-column-name="e.id"', $html);
        self::assertStringNotContainsString('data-zhortein--datatable-bundle--datatable-definition-hidden="true"', $html);
        self::assertStringNotContainsString('Identifier', $html);
    }

    public function test_it_can_disable_column_visibility_controls(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition(), [
            'columnVisibility' => false,
        ]);

        self::assertStringNotContainsString('zhortein-datatable__column-visibility', $html);
        self::assertStringNotContainsString('data-zhortein--datatable-bundle--datatable-column-visibility-control="true"', $html);
    }

    public function test_it_uses_runtime_visibility_state_for_checked_columns(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition(), [
            'visibleColumns' => ['e.email'],
            'hiddenColumns' => ['e.createdAt'],
        ]);

        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-column-name="e.email"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-column-name="e.createdAt"', $html);
        self::assertStringContainsString('name="columns[e.email]"', $html);
        self::assertStringContainsString('name="columns[e.createdAt]"', $html);
    }

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.id', label: 'Identifier', visible: false, sortable: false, searchable: false)
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.createdAt', label: 'Created at')
        ;

        return $definition;
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
