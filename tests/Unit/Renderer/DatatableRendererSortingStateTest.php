<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final class DatatableRendererSortingStateTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_exposes_sorting_state_on_datatable_shell(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition(), [
            'sortField' => 'e.email',
            'sortDirection' => 'desc',
        ]);

        self::assertStringContainsString('data-zhortein-datatable-sort-field-value="e.email"', $html);
        self::assertStringContainsString('data-zhortein-datatable-sort-direction-value="desc"', $html);
    }

    public function test_it_renders_ascending_sort_state_on_active_header(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition(), [
            'sortField' => 'e.email',
            'sortDirection' => 'asc',
        ]);

        self::assertStringContainsString('aria-sort="ascending"', $html);
        self::assertStringContainsString('data-zhortein-datatable-field-param="e.email"', $html);
        self::assertStringContainsString('data-zhortein-datatable-current-sort-param="true"', $html);
        self::assertStringContainsString('data-zhortein-datatable-sort-direction-param="asc"', $html);
        self::assertStringContainsString('sorted ascending', $html);
    }

    public function test_it_renders_descending_sort_state_on_active_header(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition(), [
            'sortField' => 'e.email',
            'sortDirection' => 'desc',
        ]);

        self::assertStringContainsString('aria-sort="descending"', $html);
        self::assertStringContainsString('data-zhortein-datatable-sort-direction-param="desc"', $html);
        self::assertStringContainsString('sorted descending', $html);
    }

    public function test_it_does_not_render_sort_state_when_no_sort_is_active(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition());

        self::assertStringContainsString('data-zhortein-datatable-sort-field-value=""', $html);
        self::assertStringContainsString('data-zhortein-datatable-sort-direction-value="asc"', $html);
        self::assertStringNotContainsString('aria-sort=', $html);
        self::assertStringContainsString('data-zhortein-datatable-current-sort-param="false"', $html);
    }

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.createdAt', label: 'Created at', sortable: false)
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
