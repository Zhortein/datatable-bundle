<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Result\DatatableResult;

final class DatatableRendererPaginationTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_renders_bootstrap_pagination_from_result(): void
    {
        $definition = $this->createDefinition();

        $result = new DatatableResult(
            rows: [
                ['email' => 'alice@example.test'],
            ],
            page: 2,
            pageSize: 10,
            totalItems: 35,
            filteredItems: 35,
        );

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->renderPagination($definition, $result);

        self::assertStringContainsString('zhortein-datatable__pagination', $html);
        self::assertStringContainsString('<nav aria-label="Datatable pagination">', $html);
        self::assertStringContainsString('class="pagination mb-0"', $html);
        self::assertStringContainsString('data-action="zhortein-datatable#goToPage"', $html);
        self::assertStringContainsString('data-zhortein-datatable-page-param="1"', $html);
        self::assertStringContainsString('data-zhortein-datatable-page-param="2"', $html);
        self::assertStringContainsString('data-zhortein-datatable-page-param="3"', $html);
        self::assertStringContainsString('data-zhortein-datatable-page-param="4"', $html);
        self::assertStringContainsString('aria-current="page"', $html);
        self::assertStringContainsString('Previous', $html);
        self::assertStringContainsString('Next', $html);
    }

    public function test_it_disables_previous_button_on_first_page(): void
    {
        $definition = $this->createDefinition();

        $result = new DatatableResult(
            rows: [
                ['email' => 'alice@example.test'],
            ],
            page: 1,
            pageSize: 10,
            totalItems: 25,
            filteredItems: 25,
        );

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->renderPagination($definition, $result);

        self::assertStringContainsString('page-item disabled', $html);
        self::assertStringContainsString('disabled aria-disabled="true"', $html);
    }

    public function test_it_does_not_render_nav_when_single_page(): void
    {
        $definition = $this->createDefinition();

        $result = new DatatableResult(
            rows: [
                ['email' => 'alice@example.test'],
            ],
            page: 1,
            pageSize: 10,
            totalItems: 5,
            filteredItems: 5,
        );

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->renderPagination($definition, $result);

        self::assertStringContainsString('zhortein-datatable__pagination', $html);
        self::assertStringNotContainsString('<nav', $html);
        self::assertStringNotContainsString('page-link', $html);
    }

    public function test_it_keeps_placeholder_rendering_without_result(): void
    {
        $definition = $this->createDefinition();

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->renderPaginationPlaceholder($definition);

        self::assertStringContainsString('zhortein-datatable__pagination', $html);
        self::assertStringContainsString('data-zhortein-datatable-target="pagination"', $html);
        self::assertStringNotContainsString('<nav', $html);
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
