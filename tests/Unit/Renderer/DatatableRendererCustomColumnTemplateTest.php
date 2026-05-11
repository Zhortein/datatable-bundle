<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Result\DatatableResult;

final class DatatableRendererCustomColumnTemplateTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_uses_custom_column_template_when_configured(): void
    {
        $definition = new DatatableDefinition('users');

        $definition->addColumn(
            name: 'status',
            label: 'Status',
            template: '@RendererTest/custom_status_cell.html.twig',
            type: 'boolean',
        );

        $result = new DatatableResult(
            rows: [
                [
                    'status' => true,
                ],
            ],
            totalItems: 1,
        );

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->renderBody($definition, $result);

        self::assertStringContainsString('data-custom-cell="status"', $html);
        self::assertStringContainsString('CUSTOM STATUS: enabled', $html);
        self::assertStringNotContainsString('text-bg-success', $html);
        self::assertStringNotContainsString('Yes', $html);
    }

    public function test_it_passes_column_and_value_to_custom_template(): void
    {
        $definition = new DatatableDefinition('users');

        $definition->addColumn(
            name: 'email',
            label: 'Email address',
            template: '@RendererTest/custom_debug_cell.html.twig',
            type: 'string',
        );

        $result = new DatatableResult(
            rows: [
                [
                    'email' => 'alice@example.test',
                ],
            ],
            totalItems: 1,
        );

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->renderBody($definition, $result);

        self::assertStringContainsString('Column: email', $html);
        self::assertStringContainsString('Label: Email address', $html);
        self::assertStringContainsString('Value: alice@example.test', $html);
    }

    private function createTwigEnvironment(): Environment
    {
        $loader = new FilesystemLoader();
        $loader->addPath(__DIR__.'/../../../templates', 'ZhorteinDatatable');
        $loader->addPath(__DIR__.'/templates', 'RendererTest');

        $twig = new Environment($loader, [
            'strict_variables' => true,
            'autoescape' => 'html',
        ]);

        $this->addTranslationExtension($twig);

        return $twig;
    }
}
