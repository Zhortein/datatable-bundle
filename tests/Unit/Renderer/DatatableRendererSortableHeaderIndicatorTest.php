<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Request\DatatableRequest;

final class DatatableRendererSortableHeaderIndicatorTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_renders_neutral_indicator_when_column_is_not_sorted(): void
    {
        $html = $this->createRenderer()->renderHeader(
            $this->createDefinition(),
            DatatableRequest::create(),
        );

        self::assertStringContainsString('aria-sort="none"', $html);
        self::assertStringContainsString('zhortein-datatable__sort-indicator', $html);
        self::assertStringContainsString('↕', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-sort-direction-param="asc"', $html);
    }

    public function test_it_renders_ascending_indicator_when_column_is_sorted_ascending(): void
    {
        $html = $this->createRenderer()->renderHeader(
            $this->createDefinition(),
            DatatableRequest::create(
                sort: 'email',
                direction: SortDirection::Asc,
            ),
        );

        self::assertStringContainsString('aria-sort="ascending"', $html);
        self::assertStringContainsString('zhortein-datatable__sort-indicator', $html);
        self::assertStringContainsString('↑', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-sort-direction-param="desc"', $html);
        self::assertStringNotContainsString('↕', $html);
    }

    public function test_it_renders_descending_indicator_when_column_is_sorted_descending(): void
    {
        $html = $this->createRenderer()->renderHeader(
            $this->createDefinition(),
            DatatableRequest::create(
                sort: 'email',
                direction: SortDirection::Desc,
            ),
        );

        self::assertStringContainsString('aria-sort="descending"', $html);
        self::assertStringContainsString('zhortein-datatable__sort-indicator', $html);
        self::assertStringContainsString('↓', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-sort-direction-param="asc"', $html);
        self::assertStringNotContainsString('↕', $html);
    }

    public function test_it_keeps_neutral_indicator_on_other_sortable_columns(): void
    {
        $html = $this->createRenderer()->renderHeader(
            $this->createDefinition(),
            DatatableRequest::create(
                sort: 'email',
                direction: SortDirection::Asc,
            ),
        );

        self::assertStringContainsString('Display name', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-sort-field-param="displayName"', $html);
        self::assertStringContainsString('aria-sort="none"', $html);
    }

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('email', label: 'Email', sortable: true)
            ->addColumn('displayName', label: 'Display name', sortable: true)
            ->addColumn('enabled', label: 'Enabled', sortable: false)
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
