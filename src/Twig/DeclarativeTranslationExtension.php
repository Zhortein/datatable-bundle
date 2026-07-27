<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Twig;

use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Zhortein\DatatableBundle\Contract\EnumPresentationResolverInterface;
use Zhortein\DatatableBundle\EnumPresentation\DefaultEnumPresentationResolver;
use Zhortein\DatatableBundle\EnumPresentation\EnumPresentation;

final class DeclarativeTranslationExtension extends AbstractExtension
{
    private EnumPresentationResolverInterface $enumPresentationResolver;

    public function __construct(
        private readonly TranslatorInterface $translator,
        ?EnumPresentationResolverInterface $enumPresentationResolver = null,
    ) {
        $this->enumPresentationResolver = $enumPresentationResolver ?? new DefaultEnumPresentationResolver($translator);
    }

    /**
     * @return list<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('zhortein_datatable_translate', $this->translate(...)),
            new TwigFunction('zhortein_datatable_translate_choices', $this->translateChoices(...)),
            new TwigFunction('zhortein_datatable_enum_choices', $this->resolveEnumChoices(...)),
        ];
    }

    /**
     * @param array<string, scalar|\Stringable|null> $parameters
     */
    public function translate(
        ?string $message,
        ?string $translationDomain,
        ?string $fallback = null,
        array $parameters = [],
    ): string {
        if (null === $message) {
            return $fallback ?? '';
        }

        if (null === $translationDomain) {
            return $message;
        }

        return $this->translator->trans($message, $parameters, $translationDomain);
    }

    /**
     * @param array<string, string> $choices
     *
     * @return array<string, string>
     */
    public function translateChoices(array $choices, ?string $translationDomain): array
    {
        if (null === $translationDomain) {
            return $choices;
        }

        $translatedChoices = [];

        foreach ($choices as $label => $value) {
            $translatedChoices[$this->translator->trans($label, [], $translationDomain)] = $value;
        }

        return $translatedChoices;
    }

    /**
     * @param array<string, string>               $choices
     * @param class-string<\UnitEnum>|null        $enumClass
     * @param array<int|string, EnumPresentation> $presentations
     *
     * @return array<string, string>
     */
    public function resolveEnumChoices(
        array $choices,
        ?string $enumClass,
        array $presentations,
        ?string $translationDomain,
    ): array {
        if ([] !== $choices || null === $enumClass) {
            return $this->translateChoices($choices, $translationDomain);
        }

        return $this->enumPresentationResolver->resolveChoices(
            enumClass: $enumClass,
            presentations: $presentations,
            translationDomain: $translationDomain,
        );
    }
}
