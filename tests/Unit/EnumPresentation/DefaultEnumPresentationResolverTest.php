<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\EnumPresentation;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;
use Zhortein\DatatableBundle\EnumPresentation\DefaultEnumPresentationResolver;
use Zhortein\DatatableBundle\EnumPresentation\EnumPresentation;

final class DefaultEnumPresentationResolverTest extends TestCase
{
    public function test_it_resolves_backed_string_and_integer_enum_values(): void
    {
        $resolver = new DefaultEnumPresentationResolver();

        self::assertSame('Active', $resolver->resolve(StringStatus::Active)?->getLabel());
        self::assertSame('Suspended', $resolver->resolve('suspended', StringStatus::class)?->getLabel());
        self::assertSame('High', $resolver->resolve('2', IntegerPriority::class)?->getLabel());
    }

    public function test_it_resolves_pure_enum_cases(): void
    {
        $presentation = new DefaultEnumPresentationResolver()->resolve(PureState::Draft);

        self::assertSame('Draft', $presentation?->getLabel());
    }

    public function test_it_prefers_explicit_translated_presentation(): void
    {
        $translator = new Translator('fr');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', ['status.enabled' => 'Activé'], 'fr', 'orders');
        $resolver = new DefaultEnumPresentationResolver($translator);

        $presentation = $resolver->resolve(
            value: StringStatus::Active,
            presentations: [
                StringStatus::Active->value => new EnumPresentation(
                    label: 'status.enabled',
                    badgeVariant: 'success',
                    color: '#198754',
                    icon: 'bi bi-check-circle',
                ),
            ],
            translationDomain: 'orders',
        );

        self::assertNotNull($presentation);
        self::assertSame('Activé', $presentation->getLabel());
        self::assertSame('success', $presentation->getBadgeVariant());
        self::assertSame('#198754', $presentation->getColor());
        self::assertSame('bi bi-check-circle', $presentation->getIcon());
    }

    public function test_it_uses_translated_case_name_then_case_name_as_fallback(): void
    {
        $translator = new Translator('fr');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', ['Active' => 'Actif'], 'fr', 'orders');
        $resolver = new DefaultEnumPresentationResolver($translator);

        self::assertSame(
            'Actif',
            $resolver->resolve(StringStatus::Active, translationDomain: 'orders')?->getLabel(),
        );
        self::assertSame(
            'Suspended',
            $resolver->resolve(StringStatus::Suspended, translationDomain: 'orders')?->getLabel(),
        );
    }

    public function test_it_resolves_choice_labels_and_submission_values(): void
    {
        $resolver = new DefaultEnumPresentationResolver();

        self::assertSame(
            ['Active' => 'active', 'Suspended' => 'suspended'],
            $resolver->resolveChoices(StringStatus::class),
        );
        self::assertSame(
            ['Draft' => 'Draft', 'Published' => 'Published'],
            $resolver->resolveChoices(PureState::class),
        );
    }
}

enum StringStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
}

enum IntegerPriority: int
{
    case Low = 1;
    case High = 2;
}

enum PureState
{
    case Draft;
    case Published;
}
