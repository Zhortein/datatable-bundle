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

        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-sort-field-value="e.email"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-sort-direction-value="desc"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-sorts-value="[{&quot;field&quot;:&quot;e.email&quot;,&quot;direction&quot;:&quot;desc&quot;}]"', $html);
    }

    public function test_it_renders_ascending_sort_state_on_active_header(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition(), [
            'sortField' => 'e.email',
            'sortDirection' => 'asc',
        ]);

        self::assertStringContainsString('aria-sort="ascending"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-field-param="e.email"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-current-sort-param="true"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-sort-direction-param="asc"', $html);
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
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-sort-direction-param="desc"', $html);
        self::assertStringContainsString('sorted descending', $html);
    }

    public function test_it_does_not_render_sort_state_when_no_sort_is_active(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition());

        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-sort-field-value=""', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-sort-direction-value="asc"', $html);
        self::assertStringNotContainsString('aria-sort=', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-current-sort-param="false"', $html);
    }

    public function test_it_renders_priorities_and_keeps_aria_sort_on_the_primary_column_only(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition(), [
            'sorts' => [
                ['field' => 'e.displayName', 'direction' => 'asc'],
                ['field' => 'e.email', 'direction' => 'desc'],
            ],
        ]);

        self::assertSame(1, substr_count($html, 'aria-sort='));
        self::assertStringContainsString('aria-sort="ascending"', $html);
        self::assertStringContainsString('sorted descending, priority 2 of 2', $html);
        self::assertStringContainsString('zhortein-datatable__sort-priority', $html);
        self::assertStringContainsString('>2</span>', preg_replace('/\s+/', '', $html) ?? $html);
    }

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.displayName', label: 'Display name')
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
