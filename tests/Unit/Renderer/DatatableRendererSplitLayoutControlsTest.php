<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final class DatatableRendererSplitLayoutControlsTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_default_layout_renders_column_visibility_and_page_size_once_in_toolbar(): void
    {
        $html = $this->createRenderer()->render($this->createDefinition(), [
            'export' => false,
            'columnVisibility' => true,
            'pageSizeSelector' => true,
        ]);

        self::assertStringContainsString('zhortein-datatable__toolbar', $html);
        self::assertStringNotContainsString('zhortein-datatable__bottom-controls', $html);

        self::assertSame(1, substr_count($html, 'zhortein-datatable__column-visibility'));
        self::assertSame(1, substr_count($html, 'data-zhortein--datatable-bundle--datatable-target="pageSizeInput"'));
        self::assertSame(1, substr_count($html, 'id="zhortein-datatable-users_page_size"'));
    }

    public function test_split_layout_renders_column_visibility_and_page_size_once_in_bottom_controls(): void
    {
        $html = $this->createRenderer()->render($this->createDefinition(), [
            'controlsLayout' => 'split',
            'export' => false,
            'columnVisibility' => true,
            'pageSizeSelector' => true,
        ]);

        self::assertStringContainsString('zhortein-datatable__toolbar', $html);
        self::assertStringContainsString('zhortein-datatable__bottom-controls', $html);

        self::assertSame(1, substr_count($html, 'zhortein-datatable__column-visibility'));
        self::assertSame(1, substr_count($html, 'data-zhortein--datatable-bundle--datatable-target="pageSizeInput"'));
        self::assertSame(1, substr_count($html, 'id="zhortein-datatable-users_page_size"'));
    }

    public function test_split_layout_keeps_summary_in_bottom_controls(): void
    {
        $html = $this->createRenderer()->render($this->createDefinition(), [
            'controlsLayout' => 'split',
            'export' => false,
        ]);

        self::assertStringContainsString('zhortein-datatable__bottom-controls', $html);
        self::assertStringContainsString('ms-auto text-end', $html);
        self::assertSame(1, substr_count($html, 'data-zhortein--datatable-bundle--datatable-target="summary"'));
    }

    public function test_split_layout_does_not_render_disabled_bottom_controls(): void
    {
        $html = $this->createRenderer()->render($this->createDefinition(), [
            'controlsLayout' => 'split',
            'export' => false,
            'columnVisibility' => false,
            'pageSizeSelector' => false,
        ]);

        self::assertStringContainsString('zhortein-datatable__bottom-controls', $html);
        self::assertStringNotContainsString('zhortein-datatable__column-visibility', $html);
        self::assertStringNotContainsString('data-zhortein--datatable-bundle--datatable-target="pageSizeInput"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-target="summary"', $html);
    }

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.displayName', label: 'Display name')
        ;

        return $definition;
    }

    private function createRenderer(): DatatableRenderer
    {
        return new DatatableRenderer($this->createTwigEnvironment());
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
