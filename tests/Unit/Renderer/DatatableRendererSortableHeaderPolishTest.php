<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final class DatatableRendererSortableHeaderPolishTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_renders_neutral_sort_indicator_for_sortable_columns_without_active_sort(): void
    {
        $html = $this->createRenderer()->renderHeader($this->createDefinition());

        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-target="header"', $html);
        self::assertStringContainsString('zhortein-datatable__sort-button', $html);
        self::assertStringContainsString('zhortein-datatable__sort-indicator', $html);
        self::assertStringContainsString('↕', $html);
        self::assertStringContainsString('data-action="zhortein--datatable-bundle--datatable#sort"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-field-param="e.email"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-current-sort-param="false"', $html);
        self::assertStringNotContainsString('aria-sort=', $html);
    }

    public function test_it_renders_ascending_sort_indicator_for_current_sort_column(): void
    {
        $html = $this->createRenderer()->renderHeader($this->createDefinition(), [
            'sortField' => 'e.email',
            'sortDirection' => 'asc',
        ]);

        self::assertStringContainsString('aria-sort="ascending"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-current-sort-param="true"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-sort-direction-param="asc"', $html);
        self::assertStringContainsString('↑', $html);
        self::assertStringContainsString('sorted ascending', $html);
        self::assertStringContainsString('active', $html);
    }

    public function test_it_renders_descending_sort_indicator_for_current_sort_column(): void
    {
        $html = $this->createRenderer()->renderHeader($this->createDefinition(), [
            'sortField' => 'e.email',
            'sortDirection' => 'desc',
        ]);

        self::assertStringContainsString('aria-sort="descending"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-sort-direction-param="desc"', $html);
        self::assertStringContainsString('↓', $html);
        self::assertStringContainsString('sorted descending', $html);
    }

    public function test_non_sortable_columns_remain_static(): void
    {
        $html = $this->createRenderer()->renderHeader($this->createDefinition());

        self::assertStringContainsString('Created at', $html);
        self::assertStringNotContainsString('data-zhortein--datatable-bundle--datatable-field-param="e.createdAt"', $html);
    }

    public function test_it_preserves_header_column_class(): void
    {
        $html = $this->createRenderer()->renderHeader($this->createDefinition());

        self::assertStringContainsString('class="text-end"', $html);
    }

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.createdAt', label: 'Created at', sortable: false, className: 'text-end')
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
