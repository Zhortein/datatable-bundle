<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Result\DatatableResult;

final class DatatableRendererRuntimeColumnVisibilityTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_hides_runtime_hidden_columns_in_shell(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition(), [
            'hiddenColumns' => ['e.displayName'],
            'columnVisibility' => false,
        ]);

        self::assertStringContainsString('Email', $html);
        self::assertStringNotContainsString('Display name', $html);
    }

    public function test_it_renders_only_runtime_visible_columns_in_shell(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition(), [
            'visibleColumns' => ['e.email'],
            'columnVisibility' => false,
        ]);

        self::assertStringContainsString('Email', $html);
        self::assertStringNotContainsString('Display name', $html);
        self::assertStringNotContainsString('Created at', $html);
    }

    public function test_runtime_visibility_cannot_show_definition_hidden_columns(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition(), [
            'visibleColumns' => ['e.id', 'e.email'],
            'columnVisibility' => false,
        ]);

        self::assertStringContainsString('Email', $html);
        self::assertStringNotContainsString('Identifier', $html);
    }

    public function test_it_hides_runtime_hidden_columns_in_body(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->renderBody(
            $this->createDefinition(),
            $this->createResult(),
            [
                'hiddenColumns' => ['e.displayName'],
            ],
        );

        self::assertStringContainsString('alice@example.test', $html);
        self::assertStringNotContainsString('Alice', $html);
        self::assertStringContainsString('2026-05-09', $html);
    }

    public function test_it_renders_only_runtime_visible_columns_in_body(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->renderBody(
            $this->createDefinition(),
            $this->createResult(),
            [
                'visibleColumns' => ['e.email'],
            ],
        );

        self::assertStringContainsString('alice@example.test', $html);
        self::assertStringNotContainsString('Alice', $html);
        self::assertStringNotContainsString('2026-05-09', $html);
    }

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.id', label: 'Identifier', visible: false, sortable: false, searchable: false)
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.displayName', label: 'Display name')
            ->addColumn('e.createdAt', label: 'Created at')
        ;

        return $definition;
    }

    private function createResult(): DatatableResult
    {
        return new DatatableResult(
            rows: [
                [
                    'e_id' => 1,
                    'e_email' => 'alice@example.test',
                    'e_displayName' => 'Alice',
                    'e_createdAt' => '2026-05-09',
                ],
            ],
            totalItems: 1,
        );
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
