<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterType;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final class DatatableRendererColumnHeaderFilterDropdownTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_renders_column_header_filter_dropdown_for_matching_filter(): void
    {
        $html = $this->createRenderer()->render($this->createDefinition(), [
            'filterLayout' => 'header',
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringContainsString('zhortein-datatable__column-filter', $html);
        self::assertStringContainsString('zhortein-datatable__column-filter-toggle', $html);
        self::assertStringContainsString('zhortein-datatable__column-filter-menu', $html);
        self::assertStringContainsString('aria-label="Filter Email"', $html);
        self::assertStringContainsString('name="filters[email]"', $html);
        self::assertStringContainsString('placeholder="Search email"', $html);
    }

    public function test_it_hides_toolbar_filters_when_header_layout_is_enabled(): void
    {
        $html = $this->createRenderer()->render($this->createDefinition(), [
            'filterLayout' => 'header',
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringNotContainsString('zhortein-datatable__filters d-flex', $html);
    }

    public function test_it_does_not_render_column_header_filter_for_column_without_matching_filter(): void
    {
        $html = $this->createRenderer()->render($this->createDefinition(), [
            'filterLayout' => 'header',
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringContainsString('Created at', $html);
        self::assertStringNotContainsString('aria-label="Filter Created at"', $html);
        self::assertStringNotContainsString('name="filters[createdAt]"', $html);
    }

    public function test_it_keeps_toolbar_filters_when_toolbar_layout_is_used(): void
    {
        $html = $this->createRenderer()->render($this->createDefinition(), [
            'filterLayout' => 'toolbar',
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringContainsString('zhortein-datatable__filters', $html);
        self::assertStringContainsString('name="filters[email]"', $html);
        self::assertStringNotContainsString('zhortein-datatable__column-filter-toggle', $html);
    }

    public function test_it_renders_choice_and_boolean_filters_in_headers(): void
    {
        $html = $this->createRenderer()->render($this->createDefinition(), [
            'filterLayout' => 'header',
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringContainsString('name="filters[status]"', $html);
        self::assertStringContainsString('<option value="enabled">Enabled</option>', $html);
        self::assertStringContainsString('name="filters[enabled]"', $html);
        self::assertStringContainsString('<option value="1">Yes</option>', $html);
        self::assertStringContainsString('<option value="0">No</option>', $html);
    }

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.status', label: 'Status')
            ->addColumn('e.enabled', label: 'Enabled')
            ->addColumn('e.createdAt', label: 'Created at')
            ->addFilter(
                name: 'email',
                field: 'e.email',
                label: 'Email',
                type: FilterType::Text,
                placeholder: 'Search email',
            )
            ->addFilter(
                name: 'status',
                field: 'e.status',
                label: 'Status',
                type: FilterType::Choice,
                choices: [
                    'Enabled' => 'enabled',
                    'Disabled' => 'disabled',
                ],
            )
            ->addFilter(
                name: 'enabled',
                field: 'e.enabled',
                label: 'Enabled',
                type: FilterType::Boolean,
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
