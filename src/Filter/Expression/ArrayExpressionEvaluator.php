<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Filter\Expression;

final readonly class ArrayExpressionEvaluator
{
    /**
     * @param array<string, mixed> $row
     */
    public function evaluate(AdvancedFilterExpression $expression, array $row): bool
    {
        return $this->evaluateExpression($expression->root, $row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function evaluateExpression(ExpressionInterface $expression, array $row): bool
    {
        if ($expression instanceof Group) {
            return $this->evaluateGroup($expression, $row);
        }

        if ($expression instanceof Condition) {
            return $this->evaluateCondition($expression, $row);
        }

        return false;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function evaluateGroup(Group $group, array $row): bool
    {
        if (LogicOperator::And === $group->logic) {
            foreach ($group->children as $child) {
                if (!$this->evaluateExpression($child, $row)) {
                    return false;
                }
            }

            return true;
        }

        foreach ($group->children as $child) {
            if ($this->evaluateExpression($child, $row)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function evaluateCondition(Condition $condition, array $row): bool
    {
        $rowValue = $this->readFieldValue($row, $condition->field);

        return match ($condition->operator) {
            ComparisonOperator::Equals => $this->compareEquals($rowValue, $condition->value),
            ComparisonOperator::NotEquals => !$this->compareEquals($rowValue, $condition->value),
            ComparisonOperator::Contains => $this->compareContains($rowValue, $condition->value),
            ComparisonOperator::NotContains => !$this->compareContains($rowValue, $condition->value),
            ComparisonOperator::StartsWith => $this->compareStartsWith($rowValue, $condition->value),
            ComparisonOperator::EndsWith => $this->compareEndsWith($rowValue, $condition->value),
            ComparisonOperator::GreaterThan => $this->compareGreaterThan($rowValue, $condition->value),
            ComparisonOperator::GreaterThanOrEquals => $this->compareGreaterThanOrEquals($rowValue, $condition->value),
            ComparisonOperator::LessThan => $this->compareLessThan($rowValue, $condition->value),
            ComparisonOperator::LessThanOrEquals => $this->compareLessThanOrEquals($rowValue, $condition->value),
            ComparisonOperator::Between => $this->compareBetween($rowValue, $condition->value),
            ComparisonOperator::IsNull => null === $rowValue,
            ComparisonOperator::IsNotNull => null !== $rowValue,
            ComparisonOperator::In => $this->compareIn($rowValue, $condition->value),
            ComparisonOperator::NotIn => !$this->compareIn($rowValue, $condition->value),
        };
    }

    private function compareEquals(mixed $rowValue, mixed $conditionValue): bool
    {
        if (is_numeric($rowValue) && is_numeric($conditionValue)) {
            return (float) $rowValue === (float) $conditionValue;
        }

        if ($rowValue instanceof \DateTimeInterface || $this->isDateString($rowValue)) {
            $rowDate = $this->normalizeDateString($rowValue);
            $conditionDate = $this->normalizeDateString($conditionValue);

            return null !== $rowDate && $rowDate === $conditionDate;
        }

        if (is_bool($rowValue) || $this->isBooleanRepresentable($conditionValue)) {
            return $this->normalizeBooleanValue($rowValue) === $this->normalizeBooleanValue($conditionValue);
        }

        return mb_strtolower(is_scalar($rowValue) ? (string) $rowValue : '') === mb_strtolower(is_scalar($conditionValue) ? (string) $conditionValue : '');
    }

    private function compareContains(mixed $rowValue, mixed $conditionValue): bool
    {
        if (!is_scalar($rowValue) || !is_scalar($conditionValue)) {
            return false;
        }

        return str_contains(
            mb_strtolower((string) $rowValue),
            mb_strtolower((string) $conditionValue),
        );
    }

    private function compareStartsWith(mixed $rowValue, mixed $conditionValue): bool
    {
        if (!is_scalar($rowValue) || !is_scalar($conditionValue)) {
            return false;
        }

        return str_starts_with(
            mb_strtolower((string) $rowValue),
            mb_strtolower((string) $conditionValue),
        );
    }

    private function compareEndsWith(mixed $rowValue, mixed $conditionValue): bool
    {
        if (!is_scalar($rowValue) || !is_scalar($conditionValue)) {
            return false;
        }

        return str_ends_with(
            mb_strtolower((string) $rowValue),
            mb_strtolower((string) $conditionValue),
        );
    }

    private function compareGreaterThan(mixed $rowValue, mixed $conditionValue): bool
    {
        if (is_numeric($rowValue) && is_numeric($conditionValue)) {
            return (float) $rowValue > (float) $conditionValue;
        }

        $rowDate = $this->normalizeDateString($rowValue);
        $conditionDate = $this->normalizeDateString($conditionValue);

        return null !== $rowDate && null !== $conditionDate && $rowDate > $conditionDate;
    }

    private function compareGreaterThanOrEquals(mixed $rowValue, mixed $conditionValue): bool
    {
        if (is_numeric($rowValue) && is_numeric($conditionValue)) {
            return (float) $rowValue >= (float) $conditionValue;
        }

        $rowDate = $this->normalizeDateString($rowValue);
        $conditionDate = $this->normalizeDateString($conditionValue);

        return null !== $rowDate && null !== $conditionDate && $rowDate >= $conditionDate;
    }

    private function compareLessThan(mixed $rowValue, mixed $conditionValue): bool
    {
        if (is_numeric($rowValue) && is_numeric($conditionValue)) {
            return (float) $rowValue < (float) $conditionValue;
        }

        $rowDate = $this->normalizeDateString($rowValue);
        $conditionDate = $this->normalizeDateString($conditionValue);

        return null !== $rowDate && null !== $conditionDate && $rowDate < $conditionDate;
    }

    private function compareLessThanOrEquals(mixed $rowValue, mixed $conditionValue): bool
    {
        if (is_numeric($rowValue) && is_numeric($conditionValue)) {
            return (float) $rowValue <= (float) $conditionValue;
        }

        $rowDate = $this->normalizeDateString($rowValue);
        $conditionDate = $this->normalizeDateString($conditionValue);

        return null !== $rowDate && null !== $conditionDate && $rowDate <= $conditionDate;
    }

    private function compareBetween(mixed $rowValue, mixed $conditionValue): bool
    {
        if (!is_array($conditionValue) || 2 !== count($conditionValue)) {
            return false;
        }

        [$min, $max] = array_values($conditionValue);

        return $this->compareGreaterThanOrEquals($rowValue, $min) && $this->compareLessThanOrEquals($rowValue, $max);
    }

    private function compareIn(mixed $rowValue, mixed $conditionValue): bool
    {
        if (!is_array($conditionValue)) {
            return false;
        }

        foreach ($conditionValue as $value) {
            if ($this->compareEquals($rowValue, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function readFieldValue(array $row, string $field): mixed
    {
        foreach ($this->getFieldCandidateKeys($field) as $candidateKey) {
            if (array_key_exists($candidateKey, $row)) {
                return $row[$candidateKey];
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function getFieldCandidateKeys(string $field): array
    {
        $candidateKeys = [$field];

        if (str_contains($field, '.')) {
            $candidateKeys[] = str_replace('.', '_', $field);

            $parts = explode('.', $field);
            $lastPart = $parts[array_key_last($parts)];

            if ('' !== $lastPart) {
                $candidateKeys[] = $lastPart;
            }
        }

        return array_values(array_unique($candidateKeys));
    }

    private function normalizeBooleanValue(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return match ($value) {
                1 => true,
                0 => false,
                default => null,
            };
        }

        if (!is_string($value)) {
            return null;
        }

        return match (mb_strtolower(trim($value))) {
            '1', 'true', 'yes', 'on' => true,
            '0', 'false', 'no', 'off' => false,
            default => null,
        };
    }

    private function isBooleanRepresentable(mixed $value): bool
    {
        return null !== $this->normalizeBooleanValue($value);
    }

    private function normalizeDateString(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        if ('' === $value) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        if (!$date instanceof \DateTimeImmutable) {
            return null;
        }

        return $date->format('Y-m-d');
    }

    private function isDateString(mixed $value): bool
    {
        return null !== $this->normalizeDateString($value);
    }
}
