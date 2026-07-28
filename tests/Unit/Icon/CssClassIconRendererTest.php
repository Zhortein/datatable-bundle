<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Icon;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Icon\CssClassIconRenderer;

final class CssClassIconRendererTest extends TestCase
{
    public function test_it_renders_a_decorative_css_icon(): void
    {
        $renderer = new CssClassIconRenderer();

        self::assertSame(
            '<span class="bi bi-eye me-1" data-icon="view" aria-hidden="true"></span>',
            $renderer->render('bi bi-eye', ['class' => 'me-1', 'data-icon' => 'view']),
        );
    }

    public function test_it_renders_a_labelled_meaningful_icon(): void
    {
        $renderer = new CssClassIconRenderer();

        self::assertSame(
            '<span class="status-icon" role="img" aria-label="Enabled"></span>',
            $renderer->render('status-icon', label: 'Enabled'),
        );
    }

    public function test_it_escapes_classes_attributes_and_labels(): void
    {
        $renderer = new CssClassIconRenderer();
        $html = $renderer->render(
            'icon&quot;" onmouseover="alert(1)',
            ['title' => '<unsafe>', 'onclick' => 'ignored'],
            'A "label"',
        );

        self::assertStringNotContainsString('onclick', $html);
        self::assertStringContainsString('title="&lt;unsafe&gt;"', $html);
        self::assertStringContainsString('aria-label="A &quot;label&quot;"', $html);
    }

    public function test_it_ignores_an_empty_icon(): void
    {
        self::assertSame('', (new CssClassIconRenderer())->render('  '));
    }
}
