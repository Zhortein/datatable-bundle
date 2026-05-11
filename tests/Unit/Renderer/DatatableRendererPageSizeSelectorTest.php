<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final class DatatableRendererPageSizeSelectorTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_renders_page_size_selector_by_default(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition());

        self::assertStringContainsString('Rows per page', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-target="pageSizeInput"', $html);
        self::assertStringContainsString('data-action="change->zhortein--datatable-bundle--datatable#changePageSize"', $html);
        self::assertStringContainsString('<option value="25" selected>', $html);
    }

    public function test_it_uses_runtime_page_size_as_selected_value(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition(), [
            'pageSize' => 50,
        ]);

        self::assertStringContainsString('<option value="50" selected>', $html);
    }

    public function test_it_renders_custom_allowed_page_sizes(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition(), [
            'pageSize' => 20,
            'allowedPageSizes' => [20, 40],
        ]);

        self::assertStringContainsString('<option value="20" selected>', $html);
        self::assertStringContainsString('<option value="40"', $html);
        self::assertStringNotContainsString('<option value="25"', $html);
    }

    public function test_it_can_disable_page_size_selector(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition(), [
            'pageSizeSelector' => false,
        ]);

        self::assertStringNotContainsString('data-zhortein--datatable-bundle--datatable-target="pageSizeInput"', $html);
        self::assertStringNotContainsString('Rows per page', $html);
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
