<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Result\DatatableResult;

final class DatatableRendererAccessibilityTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_shell_exposes_accessibility_attributes(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition());

        self::assertStringContainsString('aria-busy="false"', $html);
        self::assertStringContainsString('aria-live="polite"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-target="summary"', $html);
    }

    public function test_search_input_has_accessible_label(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition(), [
            'search' => true,
        ]);

        self::assertStringContainsString('aria-label="Search"', $html);
        self::assertStringContainsString('class="visually-hidden"', $html);
    }

    public function test_page_size_selector_has_accessible_label(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition());

        self::assertStringContainsString('aria-label="Rows per page"', $html);
        self::assertStringContainsString('for="zhortein-datatable-users_page_size"', $html);
    }

    public function test_sort_button_has_accessible_label(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition());

        self::assertStringContainsString('aria-label="Sort by Email.', $html);
        self::assertStringContainsString('Hold Shift while activating columns', $html);
    }

    public function test_pagination_buttons_have_accessible_labels(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->renderPagination(
            $this->createDefinition(),
            new DatatableResult(
                rows: [
                    ['email' => 'alice@example.test'],
                ],
                page: 2,
                pageSize: 10,
                totalItems: 30,
                filteredItems: 30,
            ),
        );

        self::assertStringContainsString('aria-label="Datatable pagination"', $html);
        self::assertStringContainsString('aria-label="Previous"', $html);
        self::assertStringContainsString('aria-label="Next"', $html);
        self::assertStringContainsString('aria-label="Go to page 2"', $html);
        self::assertStringContainsString('aria-current="page"', $html);
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
