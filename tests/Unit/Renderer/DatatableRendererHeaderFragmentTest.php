<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final class DatatableRendererHeaderFragmentTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_renders_header_fragment(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->renderHeader($this->createDefinition());

        self::assertStringContainsString('<thead', $html);
        self::assertStringContainsString('data-zhortein-datatable-target="header"', $html);
        self::assertStringContainsString('Email', $html);
        self::assertStringContainsString('Display name', $html);
    }

    public function test_it_renders_header_fragment_with_runtime_visible_columns(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->renderHeader($this->createDefinition(), [
            'visibleColumns' => ['e.email'],
        ]);

        self::assertStringContainsString('Email', $html);
        self::assertStringNotContainsString('Display name', $html);
    }

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.displayName', label: 'Display name')
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
