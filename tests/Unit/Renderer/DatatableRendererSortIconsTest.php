<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Icon\IconResolver;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final class DatatableRendererSortIconsTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_renders_fallback_sort_indicators_when_no_resolver_is_provided(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition(), [
            'sortField' => 'e.email',
            'sortDirection' => 'asc',
        ]);

        self::assertStringContainsString('↑', $html);
        self::assertStringContainsString('↕', $html); // For the non-sorted column
    }

    public function test_it_renders_default_icons_when_default_resolver_is_provided(): void
    {
        $renderer = new DatatableRenderer(
            $this->createTwigEnvironment(),
            new IconResolver(),
        );

        $html = $renderer->render($this->createDefinition(), [
            'sortField' => 'e.email',
            'sortDirection' => 'asc',
        ]);

        self::assertStringContainsString('<span class="bi bi-arrow-up" aria-hidden="true"></span>', $html);
        self::assertStringContainsString('<span class="bi bi-arrow-down-up" aria-hidden="true"></span>', $html);
    }

    public function test_it_renders_overridden_icons(): void
    {
        $renderer = new DatatableRenderer(
            $this->createTwigEnvironment(),
            new IconResolver([
                'sort_neutral' => 'fa fa-sort',
                'sort_asc' => 'fa fa-sort-up',
                'sort_desc' => 'fa fa-sort-down',
            ]),
        );

        $html = $renderer->render($this->createDefinition(), [
            'sortField' => 'e.email',
            'sortDirection' => 'desc',
        ]);

        self::assertStringContainsString('<span class="fa fa-sort-down" aria-hidden="true"></span>', $html);
        self::assertStringContainsString('<span class="fa fa-sort" aria-hidden="true"></span>', $html);
    }

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.username', label: 'Username')
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
