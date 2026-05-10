<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final class DatatableRendererConfiguredDefaultsTest extends TestCase
{
    public function test_it_uses_configured_default_page_size(): void
    {
        $renderer = new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            defaultPageSize: 50,
        );

        $html = $renderer->render($this->createDefinition());

        self::assertStringContainsString('data-zhortein-datatable-page-size-value="50"', $html);
    }

    public function test_runtime_options_override_configured_page_size(): void
    {
        $renderer = new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            defaultPageSize: 50,
        );

        $html = $renderer->render($this->createDefinition(), [
            'pageSize' => 10,
        ]);

        self::assertStringContainsString('data-zhortein-datatable-page-size-value="10"', $html);
    }

    public function test_it_uses_configured_search_default(): void
    {
        $renderer = new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            searchEnabled: true,
        );

        $html = $renderer->render($this->createDefinition());

        self::assertStringContainsString('type="search"', $html);
        self::assertStringContainsString('data-zhortein-datatable-target="searchInput"', $html);
    }

    public function test_runtime_options_override_configured_search_default(): void
    {
        $renderer = new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            searchEnabled: true,
        );

        $html = $renderer->render($this->createDefinition(), [
            'search' => false,
        ]);

        self::assertStringNotContainsString('type="search"', $html);
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

        return new Environment($loader, [
            'strict_variables' => true,
            'autoescape' => 'html',
        ]);
    }
}
