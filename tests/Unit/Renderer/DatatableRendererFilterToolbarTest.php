<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterType;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final class DatatableRendererFilterToolbarTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_renders_text_filter(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition());

        self::assertStringContainsString('name="filters[email]"', $html);
        self::assertStringContainsString('type="text"', $html);
        self::assertStringContainsString('placeholder="Search an email"', $html);
        self::assertStringContainsString('Email', $html);
        self::assertStringContainsString('data-zhortein-datatable-filter-control="true"', $html);
        self::assertStringContainsString('data-action="input->zhortein-datatable#changeFilter change->zhortein-datatable#changeFilter"', $html);
    }

    public function test_it_renders_choice_filter(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition());

        self::assertStringContainsString('name="filters[status]"', $html);
        self::assertStringContainsString('<option value="enabled">Enabled</option>', $html);
        self::assertStringContainsString('<option value="disabled">Disabled</option>', $html);
        self::assertStringContainsString('data-action="change->zhortein-datatable#changeFilter"', $html);
    }

    public function test_it_renders_boolean_filter(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition());

        self::assertStringContainsString('name="filters[enabled]"', $html);
        self::assertStringContainsString('<option value="1">Yes</option>', $html);
        self::assertStringContainsString('<option value="0">No</option>', $html);
    }

    public function test_it_renders_range_filters(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition());

        self::assertStringContainsString('name="filters[createdAt][from]"', $html);
        self::assertStringContainsString('name="filters[createdAt][to]"', $html);
        self::assertStringContainsString('name="filters[amount][from]"', $html);
        self::assertStringContainsString('name="filters[amount][to]"', $html);
    }

    public function test_it_renders_active_filter_summary_and_clear_button(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition());

        self::assertStringContainsString('data-zhortein-datatable-target="activeFilters"', $html);
        self::assertStringContainsString('data-active-filter-count="0"', $html);
        self::assertStringContainsString('Filters are active.', $html);
        self::assertStringContainsString('data-zhortein-datatable-target="clearFiltersButton"', $html);
        self::assertStringContainsString('data-action="zhortein-datatable#clearFilters"', $html);
        self::assertStringContainsString('Clear filters', $html);
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
                placeholder: 'Search an email',
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
            ->addFilter(
                name: 'createdAt',
                field: 'e.createdAt',
                label: 'Created at',
                type: FilterType::DateRange,
            )
            ->addFilter(
                name: 'amount',
                field: 'e.amount',
                label: 'Amount',
                type: FilterType::NumberRange,
            )
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
