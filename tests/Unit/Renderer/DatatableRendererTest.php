<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final class DatatableRendererTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_renders_bootstrap_datatable_shell(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.id', visible: false)
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.createdAt', label: 'Created at', searchable: false, className: 'text-end')
        ;

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($definition, [
            'columnVisibility' => false,
        ]);

        self::assertStringContainsString('id="zhortein-datatable-users"', $html);
        self::assertStringContainsString('data-controller="zhortein--datatable-bundle--datatable"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-name-value="users"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-fragments-url-value="/_zhortein/datatable/users/fragments"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-page-value="1"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-page-size-value="25"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-target="summary"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-target="loading"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-target="error"', $html);
        self::assertStringContainsString('table', $html);
        self::assertStringContainsString('align-middle', $html);
        self::assertStringContainsString('mb-0', $html);
        self::assertStringContainsString('table-striped', $html);
        self::assertStringContainsString('table-hover', $html);
        self::assertStringContainsString('Email', $html);
        self::assertStringContainsString('Created at', $html);
        self::assertStringNotContainsString('e.id', $html);
        self::assertStringContainsString('No data available.', $html);
        self::assertStringContainsString('colspan="2"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-export-url-value="/_zhortein/datatable/users/export/csv"', $html);
    }

    public function test_it_exposes_runtime_export_url(): void
    {
        $definition = new DatatableDefinition('users');
        $definition->addColumn('e.email', label: 'Email');

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($definition, [
            'exportUrl' => '/custom/users/export',
        ]);

        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-export-url-value="/custom/users/export"', $html);
    }

    public function test_it_renders_optional_search_input(): void
    {
        $definition = new DatatableDefinition('users');
        $definition->addColumn('e.email', label: 'Email');

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($definition, [
            'search' => true,
        ]);

        self::assertStringContainsString('type="search"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-target="searchInput"', $html);
    }

    public function test_it_renders_runtime_fragments_url_and_page_size(): void
    {
        $definition = new DatatableDefinition('users');
        $definition->addColumn('e.email', label: 'Email');

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($definition, [
            'fragmentsUrl' => '/custom/users/fragments',
            'pageSize' => 50,
        ]);

        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-fragments-url-value="/custom/users/fragments"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-page-size-value="50"', $html);
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
