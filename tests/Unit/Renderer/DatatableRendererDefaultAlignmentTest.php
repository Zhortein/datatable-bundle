<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Result\DatatableResult;

final class DatatableRendererDefaultAlignmentTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_right_aligns_numeric_cells_by_default(): void
    {
        $html = $this->renderSingleColumn(type: 'numeric', value: 1234);

        self::assertStringContainsString('<td class="text-end align-middle">', $html);
    }

    public function test_it_centers_boolean_cells_by_default(): void
    {
        $html = $this->renderSingleColumn(type: 'boolean', value: true);

        self::assertStringContainsString('<td class="text-center align-middle">', $html);
    }

    public function test_it_centers_enum_cells_by_default(): void
    {
        $html = $this->renderSingleColumn(type: 'enum', value: 'active');

        self::assertStringContainsString('<td class="text-center align-middle">', $html);
    }

    public function test_it_applies_default_alignment_to_boolean_headers(): void
    {
        $definition = new DatatableDefinition('users');
        $definition->addColumn('enabled', label: 'Enabled', type: 'boolean');

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->renderHeader($definition);

        self::assertStringContainsString('class="text-center align-middle"', $html);
        self::assertStringContainsString('Enabled', $html);
    }

    public function test_it_does_not_add_alignment_to_string_cells_by_default(): void
    {
        $html = $this->renderSingleColumn(type: 'string', value: 'Alice');

        self::assertStringContainsString('<td>', $html);
        self::assertStringNotContainsString('<td class=', $html);
    }

    public function test_explicit_class_name_overrides_default_alignment(): void
    {
        $definition = new DatatableDefinition('users');

        $definition->addColumn(
            name: 'amount',
            label: 'Amount',
            className: 'text-start custom-class',
            type: 'numeric',
        );

        $result = new DatatableResult(
            rows: [
                [
                    'amount' => 1234,
                ],
            ],
            totalItems: 1,
        );

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->renderBody($definition, $result);

        self::assertStringContainsString('<td class="text-start custom-class">', $html);
        self::assertStringNotContainsString('<td class="text-end">', $html);
    }

    public function test_switch_alignment_resets_bootstrap_form_check_offsets(): void
    {
        $definition = new DatatableDefinition('users');
        $definition->addColumn('enabled', label: 'Enabled', type: 'boolean');

        $result = new DatatableResult(
            rows: [
                [
                    'enabled' => true,
                ],
            ],
            totalItems: 1,
        );

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->renderBody($definition, $result, [
            'booleanDisplayMode' => 'switch',
        ]);

        self::assertStringContainsString(
            'class="form-check form-switch d-inline-flex align-items-center justify-content-center p-0 m-0"',
            $html,
        );
        self::assertStringContainsString('class="form-check-input m-0"', $html);
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

        $twig = new Environment($loader, [
            'strict_variables' => true,
            'autoescape' => 'html',
        ]);

        $this->addTranslationExtension($twig);

        return $twig;
    }
}
