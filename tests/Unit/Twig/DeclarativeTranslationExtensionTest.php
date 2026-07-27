<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Twig;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;
use Zhortein\DatatableBundle\Twig\DeclarativeTranslationExtension;
use Zhortein\DatatableBundle\EnumPresentation\EnumPresentation;

final class DeclarativeTranslationExtensionTest extends TestCase
{
    public function test_it_translates_a_declared_message_with_parameters(): void
    {
        $translator = new Translator('en');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', [
            'user.greeting' => 'Hello %name%',
        ], 'en', 'datatable');

        $extension = new DeclarativeTranslationExtension($translator);

        self::assertSame(
            'Hello Alice',
            $extension->translate('user.greeting', 'datatable', parameters: ['%name%' => 'Alice']),
        );
    }

    public function test_it_keeps_literal_text_when_no_domain_is_declared(): void
    {
        $translator = new Translator('en');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', [
            'Already translated' => 'Unexpected translation',
        ], 'en');

        $extension = new DeclarativeTranslationExtension($translator);

        self::assertSame('Already translated', $extension->translate('Already translated', null));
    }

    public function test_it_returns_the_fallback_without_translating_it(): void
    {
        $translator = new Translator('en');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', [
            'email' => 'Unexpected translation',
        ], 'en', 'datatable');

        $extension = new DeclarativeTranslationExtension($translator);

        self::assertSame('email', $extension->translate(null, 'datatable', 'email'));
    }

    public function test_it_translates_choice_labels_and_keeps_values(): void
    {
        $translator = new Translator('fr');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', [
            'status.enabled' => 'Activé',
            'status.disabled' => 'Désactivé',
        ], 'fr', 'datatable');

        $extension = new DeclarativeTranslationExtension($translator);

        self::assertSame([
            'Activé' => 'enabled',
            'Désactivé' => 'disabled',
        ], $extension->translateChoices([
            'status.enabled' => 'enabled',
            'status.disabled' => 'disabled',
        ], 'datatable'));
    }

    public function test_it_resolves_enum_choices_with_rich_presentations(): void
    {
        $translator = new Translator('fr');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', ['status.enabled' => 'Activé'], 'fr', 'datatable');
        $extension = new DeclarativeTranslationExtension($translator);

        self::assertSame(
            ['Activé' => 'enabled', 'Disabled' => 'disabled'],
            $extension->resolveEnumChoices(
                choices: [],
                enumClass: TwigStatus::class,
                presentations: [
                    TwigStatus::Enabled->value => new EnumPresentation('status.enabled'),
                ],
                translationDomain: 'datatable',
            ),
        );
    }
}

enum TwigStatus: string
{
    case Enabled = 'enabled';
    case Disabled = 'disabled';
}
