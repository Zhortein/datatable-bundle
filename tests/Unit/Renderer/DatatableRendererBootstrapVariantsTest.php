<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final class DatatableRendererBootstrapVariantsTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_keeps_current_default_table_classes(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition(), [
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringContainsString('class="table align-middle mb-0 table-striped table-hover"', $html);
        self::assertStringContainsString('class="table-responsive"', $html);
    }

    public function test_it_can_disable_default_striped_and_hover_classes(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition(), [
            'tableStriped' => false,
            'tableHover' => false,
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringContainsString('class="table align-middle mb-0"', $html);
        self::assertStringNotContainsString('table-striped', $html);
        self::assertStringNotContainsString('table-hover', $html);
    }

    public function test_it_can_enable_bordered_table(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition(), [
            'tableBordered' => true,
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringContainsString('table-bordered', $html);
    }

    public function test_it_can_enable_borderless_table(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition(), [
            'tableBorderless' => true,
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringContainsString('table-borderless', $html);
    }

    public function test_it_can_enable_small_table(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition(), [
            'tableSmall' => true,
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringContainsString('table-sm', $html);
    }

    public function test_it_can_disable_responsive_wrapper(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition(), [
            'tableResponsive' => false,
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringNotContainsString('class="table-responsive"', $html);
    }

    public function test_it_renders_default_footer_layout(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition());

        self::assertStringContainsString('zhortein-datatable__footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3', $html);
        self::assertStringContainsString('zhortein-datatable__summary', $html);
        self::assertStringContainsString('zhortein-datatable__pagination', $html);
    }

    public function test_it_renders_split_footer_layout(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition(), [
            'controlsLayout' => 'split',
        ]);

        self::assertStringContainsString('zhortein-datatable__bottom-controls d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3', $html);
        self::assertStringContainsString('zhortein-datatable__bottom-controls-start', $html);
        self::assertStringContainsString('zhortein-datatable__bottom-controls-center', $html);
        self::assertStringContainsString('zhortein-datatable__bottom-controls-end', $html);
    }

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('users');
        $definition->addColumn('e.email', label: 'Email');

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
