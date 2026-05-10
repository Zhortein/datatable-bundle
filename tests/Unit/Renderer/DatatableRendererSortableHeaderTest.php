<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final class DatatableRendererSortableHeaderTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_renders_sortable_columns_as_buttons(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition());

        self::assertStringContainsString('data-action="zhortein-datatable#sort"', $html);
        self::assertStringContainsString('data-zhortein-datatable-field-param="e.email"', $html);
        self::assertStringContainsString('Email', $html);
    }

    public function test_it_does_not_render_non_sortable_columns_as_sort_buttons(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition());

        self::assertStringNotContainsString('data-zhortein-datatable-field-param="e.createdAt"', $html);
        self::assertStringContainsString('Created at', $html);
    }

    public function test_it_preserves_header_css_class(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition());

        self::assertStringContainsString('<th class="text-end" scope="col">', $html);
    }

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.id', label: 'Id', visible: false, sortable: false, searchable: false)
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.createdAt', label: 'Created at', sortable: false, searchable: false, className: 'text-end')
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
