<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Icon;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Icon\SymfonyUxIconRenderer;

final class SymfonyUxIconRendererTest extends TestCase
{
    public function test_it_delegates_to_symfony_ux_icons_with_decorative_accessibility(): void
    {
        $uxRenderer = new RecordingUxIconRenderer();
        $renderer = new SymfonyUxIconRenderer($uxRenderer);

        self::assertSame(
            '<svg data-icon="bi:eye"></svg>',
            $renderer->render('bi:eye', ['class' => 'me-1']),
        );
        self::assertSame('bi:eye', $uxRenderer->name);
        self::assertSame(['class' => 'me-1', 'aria-hidden' => 'true'], $uxRenderer->attributes);
    }

    public function test_it_delegates_a_meaningful_accessible_label(): void
    {
        $uxRenderer = new RecordingUxIconRenderer();
        $renderer = new SymfonyUxIconRenderer($uxRenderer);

        $renderer->render('status:enabled', label: 'Enabled');

        self::assertSame(
            ['role' => 'img', 'aria-label' => 'Enabled'],
            $uxRenderer->attributes,
        );
    }

    public function test_it_converts_legacy_bootstrap_icon_classes_for_backward_compatibility(): void
    {
        $uxRenderer = new RecordingUxIconRenderer();
        $renderer = new SymfonyUxIconRenderer($uxRenderer);

        $renderer->render('bi bi-check-lg text-success', ['class' => 'me-1']);

        self::assertSame('bi:check-lg', $uxRenderer->name);
        self::assertSame(
            ['class' => 'text-success me-1', 'aria-hidden' => 'true'],
            $uxRenderer->attributes,
        );
    }

    public function test_it_drops_unsafe_svg_event_attributes(): void
    {
        $uxRenderer = new RecordingUxIconRenderer();
        $renderer = new SymfonyUxIconRenderer($uxRenderer);

        $renderer->render('bi:eye', ['onclick' => 'alert(1)', 'data-icon' => 'view']);

        self::assertSame(
            ['data-icon' => 'view', 'aria-hidden' => 'true'],
            $uxRenderer->attributes,
        );
    }

    public function test_it_rejects_an_incompatible_renderer(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new SymfonyUxIconRenderer(new \stdClass());
    }

    public function test_it_reports_an_unavailable_optional_service(): void
    {
        $renderer = new SymfonyUxIconRenderer(null);

        $this->expectException(\LogicException::class);
        $renderer->render('bi:eye');
    }
}

final class RecordingUxIconRenderer
{
    public ?string $name = null;

    /**
     * @var array<string, string|bool>
     */
    public array $attributes = [];

    /**
     * @param array<string, string|bool> $attributes
     */
    public function renderIcon(string $name, array $attributes = []): string
    {
        $this->name = $name;
        $this->attributes = $attributes;

        return sprintf('<svg data-icon="%s"></svg>', $name);
    }
}
