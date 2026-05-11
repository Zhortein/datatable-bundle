<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final class DatatableRendererLoadingErrorStateTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_renders_accessible_error_target(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition());

        self::assertStringContainsString('role="alert"', $html);
        self::assertStringContainsString('aria-live="polite"', $html);
        self::assertStringContainsString('aria-hidden="true"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-target="error"', $html);
        self::assertStringContainsString('alert alert-danger', $html);
    }

    public function test_it_renders_accessible_loading_target(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition());

        self::assertStringContainsString('role="status"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-target="loading"', $html);
        self::assertStringContainsString('spinner-border spinner-border-sm', $html);
        self::assertStringContainsString('Loading...', $html);
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
