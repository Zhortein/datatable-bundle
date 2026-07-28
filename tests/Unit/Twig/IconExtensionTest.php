<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Twig;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Zhortein\DatatableBundle\Icon\CssClassIconRenderer;
use Zhortein\DatatableBundle\Icon\IconResolver;
use Zhortein\DatatableBundle\Twig\IconExtension;

final class IconExtensionTest extends TestCase
{
    public function test_it_renders_an_explicit_icon(): void
    {
        $extension = new IconExtension(new CssClassIconRenderer(), new IconResolver());

        self::assertSame(
            '<span class="fa fa-eye" aria-hidden="true"></span>',
            (string) $extension->render('fa fa-eye'),
        );
    }

    public function test_it_resolves_and_renders_an_icon_key(): void
    {
        $extension = new IconExtension(new CssClassIconRenderer(), new IconResolver());

        self::assertSame(
            '<span class="bi bi-eye" aria-hidden="true"></span>',
            (string) $extension->renderKey('action_view'),
        );
        self::assertSame('', (string) $extension->renderKey('missing'));
    }

    public function test_a_custom_template_can_render_a_labelled_french_icon(): void
    {
        $twig = new Environment(new ArrayLoader([
            'custom.html.twig' => "{{ zhortein_datatable_icon_key('action_view', {}, 'Voir le détail') }}",
        ]));
        $twig->addExtension(new IconExtension(new CssClassIconRenderer(), new IconResolver()));

        self::assertSame(
            '<span class="bi bi-eye" role="img" aria-label="Voir le détail"></span>',
            $twig->render('custom.html.twig'),
        );
    }
}
