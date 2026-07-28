<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Icon;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Icon\ConfiguredIconRenderer;
use Zhortein\DatatableBundle\Icon\CssClassIconRenderer;
use Zhortein\DatatableBundle\Icon\SymfonyUxIconRenderer;

final class ConfiguredIconRendererTest extends TestCase
{
    public function test_css_is_the_dependency_free_default(): void
    {
        $renderer = new ConfiguredIconRenderer(new CssClassIconRenderer());

        self::assertSame(
            '<span class="bi bi-eye" aria-hidden="true"></span>',
            $renderer->render('bi bi-eye'),
        );
    }

    public function test_it_uses_the_available_ux_icons_adapter(): void
    {
        $renderer = new ConfiguredIconRenderer(
            new CssClassIconRenderer(),
            'ux_icons',
            new SymfonyUxIconRenderer(new ConfiguredRecordingUxIconRenderer()),
        );

        self::assertSame('<svg data-icon="bi:eye"></svg>', $renderer->render('bi:eye'));
    }

    public function test_it_falls_back_when_the_ux_icons_adapter_is_unavailable(): void
    {
        $renderer = new ConfiguredIconRenderer(new CssClassIconRenderer(), 'ux_icons');

        self::assertSame(
            '<span class="bi bi-eye" aria-hidden="true"></span>',
            $renderer->render('bi:eye'),
        );
    }

    public function test_it_falls_back_when_ux_icons_cannot_render_the_icon(): void
    {
        $uxRenderer = new class {
            /**
             * @param array<string, string|bool> $attributes
             */
            public function renderIcon(string $name, array $attributes = []): string
            {
                throw new \RuntimeException(sprintf('Missing icon "%s".', $name));
            }
        };
        $renderer = new ConfiguredIconRenderer(
            new CssClassIconRenderer(),
            'ux_icons',
            new SymfonyUxIconRenderer($uxRenderer),
        );

        self::assertSame(
            '<span class="missing-icon" aria-hidden="true"></span>',
            $renderer->render('missing-icon'),
        );
    }
}

final class ConfiguredRecordingUxIconRenderer
{
    /**
     * @param array<string, string|bool> $attributes
     */
    public function renderIcon(string $name, array $attributes = []): string
    {
        return sprintf('<svg data-icon="%s"></svg>', $name);
    }
}
