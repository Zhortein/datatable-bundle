<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Result\DatatableResult;

final class DatatableRendererTypedCellsTest extends TestCase
{
    public function test_it_renders_string_cell_template(): void
    {
        $html = $this->renderSingleColumn(type: 'string', value: 'Alice');

        self::assertStringContainsString('Alice', $html);
    }

    public function test_it_renders_numeric_cell_template(): void
    {
        $html = $this->renderSingleColumn(type: 'numeric', value: 1234);

        self::assertStringContainsString('1234', $html);
    }

    public function test_it_renders_boolean_true_cell_template(): void
    {
        $html = $this->renderSingleColumn(type: 'boolean', value: true);

        self::assertStringContainsString('text-bg-success', $html);
        self::assertStringContainsString('Yes', $html);
    }

    public function test_it_renders_boolean_false_cell_template(): void
    {
        $html = $this->renderSingleColumn(type: 'boolean', value: false);

        self::assertStringContainsString('text-bg-secondary', $html);
        self::assertStringContainsString('No', $html);
    }

    public function test_it_renders_datetime_cell_template(): void
    {
        $html = $this->renderSingleColumn(
            type: 'datetime',
            value: new \DateTimeImmutable('2026-05-09 14:30:00'),
        );

        self::assertStringContainsString('2026-05-09 14:30', $html);
    }

    public function test_it_renders_array_cell_template(): void
    {
        $html = $this->renderSingleColumn(type: 'array', value: ['foo' => 'bar']);

        self::assertStringContainsString('<code>', $html);
        self::assertStringContainsString('{&quot;foo&quot;:&quot;bar&quot;}', $html);
    }

    public function test_it_falls_back_to_default_template_for_unknown_type(): void
    {
        $html = $this->renderSingleColumn(type: 'unknown', value: 'Fallback');

        self::assertStringContainsString('Fallback', $html);
    }

    private function renderSingleColumn(string $type, mixed $value): string
    {
        $definition = new DatatableDefinition('users');

        $definition->addColumn(
            name: 'value',
            label: 'Value',
            type: $type,
        );

        $result = new DatatableResult(
            rows: [
                [
                    'value' => $value,
                ],
            ],
            totalItems: 1,
        );

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        return $renderer->renderBody($definition, $result);
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
