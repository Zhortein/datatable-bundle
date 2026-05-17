<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Definition;

use Zhortein\DatatableBundle\Enum\FilterOperator;
use Zhortein\DatatableBundle\Enum\FilterType;
use Zhortein\DatatableBundle\Filter\Expression\ComparisonOperator;
use Zhortein\DatatableBundle\Filter\Expression\OperatorCompatibility;

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
     * @param list<FilterOperator>           $allowedOperators
     * @param array<string, string>          $choices
     * @param class-string<\BackedEnum>|null $enumClass
     */
    public function __construct(
        private string $name,
        private string $field,
        private ?string $label = null,
        private FilterType $type = FilterType::Text,
        private array $allowedOperators = [],
        array $choices = [],
        ?string $enumClass = null,
        private bool $nullable = false,
    ) {
        if ('' === trim($this->name)) {
            throw new \InvalidArgumentException('The advanced filter field name cannot be empty.');
        }

        if ('' === trim($this->field)) {
            throw new \InvalidArgumentException('The advanced filter field cannot be empty.');
        }

        $this->resolvedEnumClass = $this->resolveEnumClass($enumClass);

        if ([] === $choices && null !== $this->resolvedEnumClass) {
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
     * @return list<FilterOperator>
     */
    public function getAllowedOperators(): array
    {
        return $this->allowedOperators;
    }

    /**
     * @return array<string, string>
     */
    public function getChoices(): array
    {
        return $this->resolvedChoices;
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
     * Returns the list of ComparisonOperator string values effectively allowed for this field,
     * computed as the intersection of type-compatible operators and per-field allowed operators.
     *
     * @return list<string>
     */
    public function getEffectiveOperatorValues(): array
    {
        $typeOperators = OperatorCompatibility::operatorsFor($this->type, $this->nullable);

        if ([] === $this->allowedOperators) {
            return array_map(static fn (ComparisonOperator $op): string => $op->value, $typeOperators);
        }

        $allowedFilterOperators = $this->allowedOperators;

        $result = [];

        foreach ($typeOperators as $operator) {
            if (in_array($this->mapComparisonToFilter($operator), $allowedFilterOperators, true)) {
                $result[] = $operator->value;
            }
        }

        return $result;
    }

    private function mapComparisonToFilter(ComparisonOperator $operator): FilterOperator
    {
        return match ($operator) {
            ComparisonOperator::Equals => FilterOperator::Equals,
            ComparisonOperator::NotEquals => FilterOperator::NotEquals,
            ComparisonOperator::Contains,
            ComparisonOperator::StartsWith,
            ComparisonOperator::EndsWith => FilterOperator::Like,
            ComparisonOperator::NotContains => FilterOperator::NotLike,
            ComparisonOperator::GreaterThan => FilterOperator::GreaterThan,
            ComparisonOperator::GreaterThanOrEquals => FilterOperator::GreaterThanOrEquals,
            ComparisonOperator::LessThan => FilterOperator::LessThan,
            ComparisonOperator::LessThanOrEquals => FilterOperator::LessThanOrEquals,
            ComparisonOperator::Between => FilterOperator::Between,
            ComparisonOperator::IsNull => FilterOperator::IsNull,
            ComparisonOperator::IsNotNull => FilterOperator::IsNotNull,
            ComparisonOperator::In => FilterOperator::In,
            ComparisonOperator::NotIn => FilterOperator::NotIn,
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
