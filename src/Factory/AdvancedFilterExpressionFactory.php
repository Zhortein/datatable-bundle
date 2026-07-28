<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Factory;

use Zhortein\DatatableBundle\Definition\AdvancedFilterFieldDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Exception\InvalidExpressionException;
use Zhortein\DatatableBundle\Filter\Expression\AdvancedFilterExpression;
use Zhortein\DatatableBundle\Filter\Expression\ComparisonOperator;
use Zhortein\DatatableBundle\Filter\Expression\Condition;
use Zhortein\DatatableBundle\Filter\Expression\Group;
use Zhortein\DatatableBundle\Filter\Expression\LogicOperator;
use Zhortein\DatatableBundle\Filter\Expression\OperatorCompatibility;

final readonly class AdvancedFilterExpressionFactory
{
    public const int MAX_CONDITIONS = 50;
    public const int MAX_DEPTH = 3;
    public const int MAX_VALUES_PER_CONDITION = 100;
    public const int MAX_VALUE_LENGTH = 2048;

    /**
     * @param array<string, mixed> $payload
     */
    public function createFromArray(array $payload, ?DatatableDefinition $definition = null): ?AdvancedFilterExpression
    {
        if ([] === $payload) {
            return null;
        }

        try {
            $conditionCount = 0;
            $root = $this->parseGroup($payload, $definition, $conditionCount, 1);

            return new AdvancedFilterExpression($root);
        } catch (InvalidExpressionException) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function parseGroup(
        array $payload,
        ?DatatableDefinition $definition,
        int &$conditionCount,
        int $depth,
    ): Group
    {
        if ($depth > self::MAX_DEPTH) {
            throw new InvalidExpressionException(sprintf(
                'Expression tree depth exceeds maximum allowed depth of %d.',
                self::MAX_DEPTH,
            ));
        }

        $logicValue = $payload['logic'] ?? 'AND';
        $logic = is_string($logicValue) ? LogicOperator::tryFrom(strtoupper($logicValue)) : null;

        if (null === $logic) {
            throw new InvalidExpressionException('Group logic must be AND or OR.');
        }

        // Accept both "conditions" (spec) and "children" (legacy) as the group's children key.
        $childrenPayload = $payload['conditions'] ?? $payload['children'] ?? [];

        if (!is_array($childrenPayload) || [] === $childrenPayload) {
            throw new InvalidExpressionException('Group must have at least one child.');
        }

        $children = [];

        foreach ($childrenPayload as $childPayload) {
            if (!is_array($childPayload)) {
                continue;
            }

            /** @var array<string, mixed> $childPayload */
            if (isset($childPayload['conditions']) || isset($childPayload['children'])) {
                try {
                    $children[] = $this->parseGroup($childPayload, $definition, $conditionCount, $depth + 1);
                } catch (InvalidExpressionException) {
                    continue;
                }
            } else {
                ++$conditionCount;

                if ($conditionCount > self::MAX_CONDITIONS) {
                    throw new InvalidExpressionException(sprintf(
                        'Expression contains more than %d conditions.',
                        self::MAX_CONDITIONS,
                    ));
                }

                $condition = $this->parseCondition($childPayload, $definition);
                if (null !== $condition) {
                    $children[] = $condition;
                }
            }
        }

        return new Group($logic, $children);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function parseCondition(array $payload, ?DatatableDefinition $definition): ?Condition
    {
        $field = $payload['field'] ?? null;
        $operatorValue = $payload['operator'] ?? null;
        $operator = is_string($operatorValue) ? ComparisonOperator::tryFrom($operatorValue) : null;
        $value = $payload['value'] ?? null;

        if (!is_string($field) || '' === trim($field) || null === $operator) {
            return null;
        }

        $fieldDefinition = null;

        if (null !== $definition) {
            $advancedFilterFields = $definition->getAdvancedFilterFields();
            if (!isset($advancedFilterFields[$field])) {
                return null;
            }

            $fieldDefinition = $advancedFilterFields[$field];

            if (!OperatorCompatibility::isCompatible($fieldDefinition->getType(), $operator, $fieldDefinition->isNullable())) {
                return null;
            }

            $allowedOperators = $fieldDefinition->getAllowedOperators();

            if ([] !== $allowedOperators && !in_array($operator, $allowedOperators, true)) {
                return null;
            }
        }

        $value = $this->normalizeValue($value, $operator, $fieldDefinition);

        if (!$this->isValueWithinLimits($value)) {
            return null;
        }

        try {
            return new Condition($field, $operator, $value);
        } catch (InvalidExpressionException) {
            return null;
        }
    }

    private function normalizeValue(mixed $value, ComparisonOperator $operator, ?AdvancedFilterFieldDefinition $fieldDefinition): mixed
    {
        if (ComparisonOperator::IsNull === $operator || ComparisonOperator::IsNotNull === $operator) {
            return null;
        }

        if (ComparisonOperator::Between === $operator) {
            if (!is_array($value)) {
                return $value;
            }

            $values = array_values($value);

            if (2 !== count($values)) {
                if (isset($value['from'], $value['to'])) {
                    $values = [$value['from'], $value['to']];
                }
            }

            return $values;
        }

        if (ComparisonOperator::In === $operator || ComparisonOperator::NotIn === $operator) {
            if (!is_array($value)) {
                $value = [$value];
            }

            return array_values($value);
        }

        if (null !== $fieldDefinition && null !== $fieldDefinition->getEnumClass() && is_scalar($value)) {
            $enumClass = $fieldDefinition->getEnumClass();
            $allowedValues = array_map(static fn (\BackedEnum $case): string => (string) $case->value, $enumClass::cases());

            if (!in_array((string) $value, $allowedValues, true)) {
                return $value;
            }
        }

        return $value;
    }

    private function isValueWithinLimits(mixed $value): bool
    {
        if (is_string($value)) {
            return mb_strlen($value) <= self::MAX_VALUE_LENGTH;
        }

        if (!is_array($value)) {
            return null === $value || is_scalar($value);
        }

        if (count($value) > self::MAX_VALUES_PER_CONDITION) {
            return false;
        }

        foreach ($value as $item) {
            if (is_array($item) || !$this->isValueWithinLimits($item)) {
                return false;
            }
        }

        return true;
    }
}
