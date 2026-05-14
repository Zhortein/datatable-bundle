<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final class DatatableRendererXlsxExportControlTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_renders_csv_export_controls_by_default(): void
    {
        $html = $this->createRenderer()->render($this->createDefinition());

        self::assertStringContainsString('CSV current view', $html);
        self::assertStringContainsString('CSV full dataset', $html);
        self::assertStringContainsString('/_zhortein/datatable/users/export/csv?mode=current', $html);
        self::assertStringContainsString('/_zhortein/datatable/users/export/csv?mode=full', $html);
        self::assertStringNotContainsString('XLSX current view', $html);
        self::assertStringNotContainsString('XLSX full dataset', $html);
        self::assertStringNotContainsString('/_zhortein/datatable/users/export/xlsx', $html);
    }

    public function test_it_renders_xlsx_export_controls_when_enabled(): void
    {
        $html = $this->createRenderer()->render($this->createDefinition(), [
            'exportFormats' => ['csv', 'xlsx'],
        ]);

        self::assertStringContainsString('CSV current view', $html);
        self::assertStringContainsString('CSV full dataset', $html);
        self::assertStringContainsString('XLSX current view', $html);
        self::assertStringContainsString('XLSX full dataset', $html);
        self::assertStringContainsString('/_zhortein/datatable/users/export/xlsx?mode=current', $html);
        self::assertStringContainsString('/_zhortein/datatable/users/export/xlsx?mode=full', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-export-format-param="xlsx"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-export-url-param="/_zhortein/datatable/users/export/xlsx"', $html);
    }

    public function test_it_can_render_only_xlsx_export_controls(): void
    {
        $html = $this->createRenderer()->render($this->createDefinition(), [
            'exportFormats' => ['xlsx'],
        ]);

        self::assertStringNotContainsString('CSV current view', $html);
        self::assertStringNotContainsString('CSV full dataset', $html);
        self::assertStringContainsString('XLSX current view', $html);
        self::assertStringContainsString('XLSX full dataset', $html);
    }

    public function test_it_uses_custom_export_urls_per_format(): void
    {
        $html = $this->createRenderer()->render($this->createDefinition(), [
            'exportFormats' => ['csv', 'xlsx'],
            'exportUrls' => [
                'csv' => '/custom/users/export/csv',
                'xlsx' => '/custom/users/export/xlsx',
            ],
        ]);

        self::assertStringContainsString('/custom/users/export/csv?mode=current', $html);
        self::assertStringContainsString('/custom/users/export/xlsx?mode=current', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-export-url-param="/custom/users/export/xlsx"', $html);
    }

    public function test_it_ignores_unknown_export_formats(): void
    {
        $html = $this->createRenderer()->render($this->createDefinition(), [
            'exportFormats' => ['pdf', 'csv'],
        ]);

        self::assertStringContainsString('CSV current view', $html);
        self::assertStringNotContainsString('pdf', strtolower($html));
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
