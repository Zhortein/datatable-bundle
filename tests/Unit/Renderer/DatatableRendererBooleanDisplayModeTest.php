<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Contract\IconResolverInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\BooleanDisplayMode;
use Zhortein\DatatableBundle\Icon\IconResolver;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Result\DatatableResult;

final class DatatableRendererBooleanDisplayModeTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_renders_boolean_as_badge_by_default(): void
    {
        $html = $this->renderBoolean(true);

        self::assertStringContainsString('badge text-bg-success', $html);
        self::assertStringContainsString('Yes', $html);
    }

    public function test_it_renders_boolean_false_as_badge_by_default(): void
    {
        $html = $this->renderBoolean(false);

        self::assertStringContainsString('badge text-bg-secondary', $html);
        self::assertStringContainsString('No', $html);
    }

    public function test_it_renders_boolean_as_icon_fallback_when_no_resolver(): void
    {
        $html = $this->renderBoolean(true, BooleanDisplayMode::Icon);

        self::assertStringContainsString('text-success', $html);
        self::assertStringContainsString('✓', $html);
        self::assertStringContainsString('visually-hidden', $html);
        self::assertStringContainsString('Yes', $html);
        self::assertStringNotContainsString('badge text-bg-success', $html);
    }

    public function test_it_renders_boolean_false_as_icon_fallback_when_no_resolver(): void
    {
        $html = $this->renderBoolean(false, BooleanDisplayMode::Icon);

        self::assertStringContainsString('text-danger', $html);
        self::assertStringContainsString('×', $html);
        self::assertStringContainsString('No', $html);
    }

    public function test_it_renders_boolean_as_icon_with_resolver(): void
    {
        $html = $this->renderBoolean(true, BooleanDisplayMode::Icon, new IconResolver());

        self::assertStringContainsString('bi bi-check-lg', $html);
        self::assertStringContainsString('text-success', $html);
        self::assertStringContainsString('visually-hidden', $html);
        self::assertStringContainsString('Yes', $html);
    }

    public function test_it_renders_boolean_false_as_icon_with_resolver(): void
    {
        $html = $this->renderBoolean(false, BooleanDisplayMode::Icon, new IconResolver());

        self::assertStringContainsString('bi bi-x-lg', $html);
        self::assertStringContainsString('text-danger', $html);
        self::assertStringContainsString('visually-hidden', $html);
        self::assertStringContainsString('No', $html);
    }

    public function test_it_renders_boolean_as_custom_icon(): void
    {
        $resolver = new IconResolver([
            'boolean_true' => 'custom-true-icon',
            'boolean_false' => 'custom-false-icon',
        ]);

        $html = $this->renderBoolean(true, BooleanDisplayMode::Icon, $resolver);
        self::assertStringContainsString('custom-true-icon', $html);

        $html = $this->renderBoolean(false, BooleanDisplayMode::Icon, $resolver);
        self::assertStringContainsString('custom-false-icon', $html);
    }

    public function test_it_renders_boolean_as_switch(): void
    {
        $html = $this->renderBoolean(true, BooleanDisplayMode::Switch);

        self::assertStringContainsString('form-check form-switch', $html);
        self::assertStringContainsString('role="switch"', $html);
        self::assertStringContainsString('disabled', $html);
        self::assertStringContainsString('checked', $html);
        self::assertStringContainsString('aria-label="Yes"', $html);
    }

    public function test_it_renders_boolean_false_as_switch(): void
    {
        $html = $this->renderBoolean(false, BooleanDisplayMode::Switch);

        self::assertStringContainsString('form-check form-switch', $html);
        self::assertStringContainsString('aria-label="No"', $html);
        self::assertStringNotContainsString('checked', $html);
    }

    public function test_it_renders_boolean_as_text(): void
    {
        $html = $this->renderBoolean(true, BooleanDisplayMode::Text);

        self::assertStringContainsString('Yes', $html);
        self::assertStringNotContainsString('badge', $html);
        self::assertStringNotContainsString('form-check', $html);
    }

    public function test_it_negates_true_boolean_values(): void
    {
        $html = $this->renderBoolean(true, BooleanDisplayMode::Text, negate: true);

        self::assertStringContainsString('No', $html);
        self::assertStringNotContainsString('Yes', $html);
    }

    public function test_it_negates_false_boolean_values(): void
    {
        $html = $this->renderBoolean(false, BooleanDisplayMode::Text, negate: true);

        self::assertStringContainsString('Yes', $html);
        self::assertStringNotContainsString('No', $html);
    }

    public function test_it_does_not_negate_null_boolean_values(): void
    {
        $html = $this->renderBoolean(null, BooleanDisplayMode::Text, negate: true);

        self::assertStringContainsString('No', $html);
        self::assertStringNotContainsString('Yes', $html);
    }

    public function test_it_renders_boolean_from_string_option(): void
    {
        $definition = new DatatableDefinition('users');
        $definition->addColumn('enabled', type: 'boolean');

        $html = new DatatableRenderer($this->createTwigEnvironment())->renderBody(
            $definition,
            new DatatableResult([['enabled' => true]], 1),
            ['booleanDisplayMode' => 'switch']
        );

        self::assertStringContainsString('form-check form-switch', $html);
    }

    private function renderBoolean(
        ?bool $value,
        ?BooleanDisplayMode $mode = null,
        ?IconResolverInterface $iconResolver = null,
        bool $negate = false,
    ): string {
        $definition = new DatatableDefinition('users');

        $definition->addColumn(
            name: 'enabled',
            label: 'Enabled',
            type: 'boolean',
            negate: $negate,
        );

        $options = [];

        if (null !== $mode) {
            $options['booleanDisplayMode'] = $mode->value;
        }

        return new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            iconResolver: $iconResolver,
        )->renderBody(
            $definition,
            new DatatableResult(
                rows: [
                    [
                        'enabled' => $value,
                    ],
                ],
                totalItems: 1,
            ),
            $options,
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
