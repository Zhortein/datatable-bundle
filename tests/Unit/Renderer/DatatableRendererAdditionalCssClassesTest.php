<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final class DatatableRendererAdditionalCssClassesTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_appends_additional_root_class(): void
    {
        $html = $this->createRenderer()->render($this->createDefinition(), [
            'rootClass' => 'datatable--compact my-root-class',
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringContainsString('class="zhortein-datatable datatable--compact my-root-class"', $html);
    }

    public function test_it_appends_additional_table_wrapper_class(): void
    {
        $html = $this->createRenderer()->render($this->createDefinition(), [
            'tableWrapperClass' => 'my-table-wrapper',
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringContainsString('class="table-responsive my-table-wrapper"', $html);
    }

    public function test_it_appends_wrapper_class_even_when_responsive_wrapper_is_disabled(): void
    {
        $html = $this->createRenderer()->render($this->createDefinition(), [
            'tableResponsive' => false,
            'tableWrapperClass' => 'my-table-wrapper',
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringContainsString('class="my-table-wrapper"', $html);
        self::assertStringNotContainsString('table-responsive my-table-wrapper', $html);
    }

    public function test_it_appends_additional_table_class_without_removing_defaults(): void
    {
        $html = $this->createRenderer()->render($this->createDefinition(), [
            'tableClass' => 'my-table table-sm-custom',
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringContainsString('table', $html);
        self::assertStringContainsString('align-middle', $html);
        self::assertStringContainsString('mb-0', $html);
        self::assertStringContainsString('table-striped', $html);
        self::assertStringContainsString('table-hover', $html);
        self::assertStringContainsString('my-table table-sm-custom', $html);
    }

    public function test_it_ignores_empty_additional_classes(): void
    {
        $html = $this->createRenderer()->render($this->createDefinition(), [
            'rootClass' => '   ',
            'tableWrapperClass' => '',
            'tableClass' => ' ',
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringContainsString('class="zhortein-datatable"', $html);
        self::assertStringContainsString('class="table-responsive"', $html);
        self::assertStringContainsString('class="table align-middle mb-0 table-striped table-hover"', $html);
    }

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('users');
        $definition->addColumn('e.email', label: 'Email');

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
