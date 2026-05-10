<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final class DatatableRendererExportControlTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_renders_csv_export_control_by_default(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition());

        self::assertStringContainsString('zhortein-datatable__export', $html);
        self::assertStringContainsString('Export', $html);
        self::assertStringContainsString('/_zhortein/datatable/users/export/csv?mode=current', $html);
        self::assertStringContainsString('/_zhortein/datatable/users/export/csv?mode=full', $html);
        self::assertStringContainsString('CSV current view', $html);
        self::assertStringContainsString('CSV full dataset', $html);
        self::assertStringContainsString('data-zhortein-datatable-export-mode="current"', $html);
        self::assertStringContainsString('data-zhortein-datatable-export-mode="full"', $html);
    }

    public function test_it_uses_custom_export_url(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition(), [
            'exportUrl' => '/custom/users/export',
        ]);

        self::assertStringContainsString('/custom/users/export?mode=current', $html);
        self::assertStringContainsString('/custom/users/export?mode=full', $html);
    }

    public function test_it_can_disable_export_control(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition(), [
            'export' => false,
        ]);

        self::assertStringNotContainsString('zhortein-datatable__export', $html);
        self::assertStringNotContainsString('CSV current view', $html);
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
