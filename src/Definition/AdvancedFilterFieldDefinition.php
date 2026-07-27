<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Definition;

use Zhortein\DatatableBundle\Enum\FilterOperator;
use Zhortein\DatatableBundle\Enum\FilterType;
use Zhortein\DatatableBundle\Filter\Expression\ComparisonOperator;
use Zhortein\DatatableBundle\Filter\Expression\OperatorCompatibility;
use Zhortein\DatatableBundle\EnumPresentation\EnumPresentation;

final readonly class AdvancedFilterFieldDefinition
{
    /**
     * @var array<string, string>
     */
    private array $resolvedChoices;

    /**
     * @var class-string<\BackedEnum>|null
     */
    private ?string $resolvedEnumClass;

    /**
     * @var list<ComparisonOperator>
     */
    private array $normalizedAllowedOperators;

    private bool $explicitChoices;

    /**
     * @param list<FilterOperator|ComparisonOperator> $allowedOperators
     * @param array<string, string>                   $choices
     * @param class-string<\BackedEnum>|null          $enumClass
     * @param array<int|string, EnumPresentation>     $enumPresentations
     */
    public function __construct(
        private string $name,
        private string $field,
        private ?string $label = null,
        private FilterType $type = FilterType::Text,
        array $allowedOperators = [],
        array $choices = [],
        ?string $enumClass = null,
        private bool $nullable = false,
        private array $enumPresentations = [],
    ) {
        if ('' === trim($this->name)) {
            throw new \InvalidArgumentException('The advanced filter field name cannot be empty.');
        }

        if ('' === trim($this->field)) {
            throw new \InvalidArgumentException('The advanced filter field cannot be empty.');
        }

        $this->normalizedAllowedOperators = $this->normalizeAllowedOperators($allowedOperators);

        $this->resolvedEnumClass = $this->resolveEnumClass($enumClass);

        $this->explicitChoices = [] !== $choices;

        if (!$this->explicitChoices && null !== $this->resolvedEnumClass) {
            $choices = $this->deriveChoicesFromEnum($this->resolvedEnumClass);
        }

        $this->resolvedChoices = $choices;

    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getType(): FilterType
    {
        return $this->type;
    }

    /**
     * Returns the developer-declared allowed operators, normalized to the advanced
     * comparison operator model.
     *
     * An empty list means "no restriction beyond type-compatibility".
     *
     * @return list<ComparisonOperator>
     */
    public function getAllowedOperators(): array
    {
        return $this->normalizedAllowedOperators;
    }

    /**
     * @return array<string, string>
     */
    public function getChoices(): array
    {
        return $this->resolvedChoices;
    }

    public function hasExplicitChoices(): bool
    {
        return $this->explicitChoices;
    }

    /**
     * @return class-string<\BackedEnum>|null
     */
    public function getEnumClass(): ?string
    {
        return $this->resolvedEnumClass;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * @return array<int|string, EnumPresentation>
     */
    public function getEnumPresentations(): array
    {
        return $this->enumPresentations;
    }

    /**
     * Returns the list of ComparisonOperator string values effectively allowed for this field,
     * computed as the intersection of type-compatible operators and per-field allowed operators.
     *
     * Incompatible developer-declared operators are silently filtered out so they are
     * never displayed in the UI nor accepted from the frontend.
     *
     * @return list<string>
     */
    public function getEffectiveOperatorValues(): array
    {
        $typeOperators = OperatorCompatibility::operatorsFor($this->type, $this->nullable);

        if ([] === $this->normalizedAllowedOperators) {
            return array_map(static fn (ComparisonOperator $op): string => $op->value, $typeOperators);
        }

        $result = [];

        foreach ($typeOperators as $operator) {
            if (in_array($operator, $this->normalizedAllowedOperators, true)) {
                $result[] = $operator->value;
            }
        }

        return $result;
    }

    /**
     * Returns the list of ComparisonOperator effectively allowed for this field.
     *
     * @return list<ComparisonOperator>
     */
    public function getEffectiveOperators(): array
    {
        $typeOperators = OperatorCompatibility::operatorsFor($this->type, $this->nullable);

        if ([] === $this->normalizedAllowedOperators) {
            return $typeOperators;
        }

        $result = [];

        foreach ($typeOperators as $operator) {
            if (in_array($operator, $this->normalizedAllowedOperators, true)) {
                $result[] = $operator;
            }
        }

        return $result;
    }

    /**
     * @param list<FilterOperator|ComparisonOperator> $allowedOperators
     *
     * @return list<ComparisonOperator>
     */
    private function normalizeAllowedOperators(array $allowedOperators): array
    {
        $normalized = [];

        foreach ($allowedOperators as $operator) {
            if ($operator instanceof ComparisonOperator) {
                if (!in_array($operator, $normalized, true)) {
                    $normalized[] = $operator;
                }
                continue;
            }

            foreach (self::mapFilterToComparison($operator) as $mapped) {
                if (!in_array($mapped, $normalized, true)) {
                    $normalized[] = $mapped;
                }
            }
        }

        return $normalized;
    }

    /**
     * Maps a legacy FilterOperator to one or more advanced ComparisonOperator values.
     *
     * @return list<ComparisonOperator>
     */
    private static function mapFilterToComparison(FilterOperator $operator): array
    {
        return match ($operator) {
            FilterOperator::Equals => [ComparisonOperator::Equals],
            FilterOperator::NotEquals => [ComparisonOperator::NotEquals],
            FilterOperator::GreaterThan => [ComparisonOperator::GreaterThan],
            FilterOperator::GreaterThanOrEquals => [ComparisonOperator::GreaterThanOrEquals],
            FilterOperator::LessThan => [ComparisonOperator::LessThan],
            FilterOperator::LessThanOrEquals => [ComparisonOperator::LessThanOrEquals],
            FilterOperator::In => [ComparisonOperator::In],
            FilterOperator::NotIn => [ComparisonOperator::NotIn],
            FilterOperator::IsNull => [ComparisonOperator::IsNull],
            FilterOperator::IsNotNull => [ComparisonOperator::IsNotNull],
            FilterOperator::Between => [ComparisonOperator::Between],
            FilterOperator::Like => [
                ComparisonOperator::Contains,
                ComparisonOperator::StartsWith,
                ComparisonOperator::EndsWith,
            ],
            FilterOperator::NotLike => [ComparisonOperator::NotContains],
        };
    }

    /**
     * @param class-string<\BackedEnum>|string|null $enumClass
     *
     * @return class-string<\BackedEnum>|null
     */
    private function resolveEnumClass(?string $enumClass): ?string
    {
        if (null === $enumClass || '' === trim($enumClass)) {
            return null;
        }

        if (!is_subclass_of($enumClass, \BackedEnum::class)) {
            throw new \InvalidArgumentException(sprintf('Class "%s" must be a backed enum.', $enumClass));
        }

        /** @var class-string<\BackedEnum> $enumClass */
        return $enumClass;
    }

    /**
     * @param class-string<\BackedEnum> $enumClass
     *
     * @return array<string, string>
     */
    private function deriveChoicesFromEnum(string $enumClass): array
    {
        $choices = [];

        foreach ($enumClass::cases() as $case) {
            $label = $case->name;
            $value = (string) $case->value;
            $choices[$label] = $value;
        }

        return $choices;
    }
}
