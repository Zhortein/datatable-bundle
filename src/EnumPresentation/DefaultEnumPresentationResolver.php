<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\EnumPresentation;

use Symfony\Contracts\Translation\TranslatorInterface;
use Zhortein\DatatableBundle\Contract\EnumPresentationResolverInterface;

final readonly class DefaultEnumPresentationResolver implements EnumPresentationResolverInterface
{
    public function __construct(
        private ?TranslatorInterface $translator = null,
    ) {
    }

    public function resolve(
        mixed $value,
        ?string $enumClass = null,
        array $presentations = [],
        ?string $translationDomain = null,
    ): ?EnumPresentation {
        if (null === $value) {
            return null;
        }

        $case = $value instanceof \UnitEnum
            ? $value
            : $this->resolveCase($value, $enumClass);

        if (null === $case) {
            if (!is_scalar($value) && !$value instanceof \Stringable) {
                return null;
            }

            return new EnumPresentation((string) $value);
        }

        $presentation = $this->findExplicitPresentation($case, $presentations);

        if (null !== $presentation) {
            return $presentation->withLabel(
                $this->translate($presentation->getLabel(), $translationDomain),
            );
        }

        $translatedCaseName = $this->translateCaseName($case->name, $translationDomain);

        return new EnumPresentation($translatedCaseName ?? $case->name);
    }

    public function resolveChoices(
        string $enumClass,
        array $presentations = [],
        ?string $translationDomain = null,
    ): array {
        if (!enum_exists($enumClass)) {
            throw new \InvalidArgumentException(sprintf('Class "%s" must be an enum.', $enumClass));
        }

        $choices = [];

        foreach ($enumClass::cases() as $case) {
            $presentation = $this->resolve(
                value: $case,
                enumClass: $enumClass,
                presentations: $presentations,
                translationDomain: $translationDomain,
            );

            if (null === $presentation) {
                continue;
            }

            $value = $case instanceof \BackedEnum ? (string) $case->value : $case->name;
            $choices[$presentation->getLabel()] = $value;
        }

        return $choices;
    }

    /**
     * @param class-string<\UnitEnum>|null $enumClass
     */
    private function resolveCase(mixed $value, ?string $enumClass): ?\UnitEnum
    {
        if (null === $enumClass || !enum_exists($enumClass) || !is_subclass_of($enumClass, \BackedEnum::class)) {
            return null;
        }

        if (!is_int($value) && !is_string($value)) {
            return null;
        }

        $reflection = new \ReflectionEnum($enumClass);
        $backingType = $reflection->getBackingType()?->getName();
        $backedValue = $value;

        if ('int' === $backingType) {
            if (is_string($backedValue) && preg_match('/^-?\d+$/D', $backedValue)) {
                $backedValue = (int) $backedValue;
            }

            if (!is_int($backedValue)) {
                return null;
            }
        } elseif ('string' === $backingType && is_int($backedValue)) {
            $backedValue = (string) $backedValue;
        }

        return $enumClass::tryFrom($backedValue);
    }

    /**
     * @param array<int|string, EnumPresentation> $presentations
     */
    private function findExplicitPresentation(\UnitEnum $case, array $presentations): ?EnumPresentation
    {
        $presentation = $presentations[$case->name] ?? null;

        if (null === $presentation && $case instanceof \BackedEnum) {
            $presentation = $presentations[$case->value] ?? $presentations[(string) $case->value] ?? null;
        }

        if (null !== $presentation && !$presentation instanceof EnumPresentation) {
            throw new \InvalidArgumentException('Enum presentations must contain EnumPresentation instances.');
        }

        return $presentation;
    }

    private function translate(string $label, ?string $translationDomain): string
    {
        if (null === $translationDomain || null === $this->translator) {
            return $label;
        }

        return $this->translator->trans($label, [], $translationDomain);
    }

    private function translateCaseName(string $caseName, ?string $translationDomain): ?string
    {
        if (null === $translationDomain || null === $this->translator) {
            return null;
        }

        $translated = $this->translator->trans($caseName, [], $translationDomain);

        return $translated === $caseName ? null : $translated;
    }
}
