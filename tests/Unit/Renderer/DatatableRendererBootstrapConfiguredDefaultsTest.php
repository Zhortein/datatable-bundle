<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final class DatatableRendererBootstrapConfiguredDefaultsTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_uses_configured_bootstrap_defaults(): void
    {
        $renderer = new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            defaultTableOptions: [
                'tableStriped' => false,
                'tableHover' => false,
                'tableBordered' => true,
                'tableBorderless' => false,
                'tableSmall' => true,
                'tableResponsive' => false,
            ],
        );

        $html = $renderer->render($this->createDefinition(), [
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringContainsString('table-bordered', $html);
        self::assertStringContainsString('table-sm', $html);
        self::assertStringNotContainsString('table-striped', $html);
        self::assertStringNotContainsString('table-hover', $html);
        self::assertStringNotContainsString('class="table-responsive"', $html);
    }

    public function test_runtime_options_override_configured_bootstrap_defaults(): void
    {
        $renderer = new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            defaultTableOptions: [
                'tableStriped' => false,
                'tableHover' => false,
                'tableBordered' => true,
                'tableSmall' => true,
            ],
        );

        $html = $renderer->render($this->createDefinition(), [
            'tableStriped' => true,
            'tableHover' => true,
            'tableBordered' => false,
            'tableSmall' => false,
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringContainsString('table-striped', $html);
        self::assertStringContainsString('table-hover', $html);
        self::assertStringNotContainsString('table-bordered', $html);
        self::assertStringNotContainsString('table-sm', $html);
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
