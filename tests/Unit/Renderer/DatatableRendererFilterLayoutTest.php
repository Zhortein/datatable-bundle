<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterType;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final class DatatableRendererFilterLayoutTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_toolbar_filter_layout_is_default(): void
    {
        $html = $this->createRenderer()->render($this->createDefinition(), [
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringContainsString('zhortein-datatable__filters', $html);
        self::assertStringContainsString('name="filters[email]"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-target="activeFilters"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-target="clearFiltersButton"', $html);
    }

    public function test_header_filter_layout_hides_toolbar_filters(): void
    {
        $html = $this->createRenderer()->render($this->createDefinition(), [
            'filterLayout' => 'header',
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringNotContainsString('zhortein-datatable__filters d-flex', $html);
        self::assertStringContainsString('zhortein-datatable__column-filter', $html);
        self::assertStringContainsString('name="filters[email]"', $html);
    }

    public function test_none_filter_layout_hides_all_filter_controls(): void
    {
        $html = $this->createRenderer()->render($this->createDefinition(), [
            'filterLayout' => 'none',
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringNotContainsString('zhortein-datatable__filters', $html);
        self::assertStringNotContainsString('name="filters[email]"', $html);
        self::assertStringNotContainsString('data-zhortein--datatable-bundle--datatable-filter-control="true"', $html);
    }

    public function test_unknown_filter_layout_falls_back_to_toolbar_in_renderer_options(): void
    {
        $html = $this->createRenderer()->render($this->createDefinition(), [
            'filterLayout' => 'unknown',
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringContainsString('zhortein-datatable__filters', $html);
        self::assertStringContainsString('name="filters[email]"', $html);
    }

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email')
            ->addFilter(
                name: 'email',
                field: 'e.email',
                label: 'Email',
                type: FilterType::Text,
                placeholder: 'Search email',
            )
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
