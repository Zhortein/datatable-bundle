<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final class DatatableRendererTest extends TestCase
{
    public function test_it_renders_bootstrap_datatable_shell(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.id', visible: false)
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.createdAt', label: 'Created at', searchable: false, className: 'text-end')
        ;

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($definition);

        self::assertStringContainsString('id="zhortein-datatable-users"', $html);
        self::assertStringContainsString('data-controller="zhortein-datatable"', $html);
        self::assertStringContainsString('data-zhortein-datatable-name-value="users"', $html);
        self::assertStringContainsString('class="table table-striped table-hover align-middle mb-0"', $html);
        self::assertStringContainsString('Email', $html);
        self::assertStringContainsString('Created at', $html);
        self::assertStringNotContainsString('e.id', $html);
        self::assertStringContainsString('No data available.', $html);
        self::assertStringContainsString('colspan="2"', $html);
    }

    public function test_it_renders_optional_search_input(): void
    {
        $definition = new DatatableDefinition('users');
        $definition->addColumn('e.email', label: 'Email');

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($definition, [
            'search' => true,
        ]);

        self::assertStringContainsString('type="search"', $html);
        self::assertStringContainsString('data-zhortein-datatable-target="searchInput"', $html);
    }

    private function createTwigEnvironment(): Environment
    {
        $loader = new FilesystemLoader();
        $loader->addPath(__DIR__.'/../../../templates', 'ZhorteinDatatable');

        return new Environment($loader, [
            'strict_variables' => true,
        ]);
    }
}
