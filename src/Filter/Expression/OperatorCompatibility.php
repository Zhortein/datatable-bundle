<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Filter\Expression;

use Zhortein\DatatableBundle\Enum\FilterType;

/**
 * Defines which ComparisonOperator values are compatible with each FilterType.
 *
 * Used by both backend normalization and frontend operator list serialization
 * to ensure incompatible operators are never displayed nor accepted.
 */
final class OperatorCompatibility
{
    /**
     * @return list<ComparisonOperator>
     */
    public static function operatorsFor(FilterType $type, bool $nullable = true): array
    {
        $operators = match ($type) {
            FilterType::Text => [
                ComparisonOperator::Equals,
                ComparisonOperator::NotEquals,
                ComparisonOperator::Contains,
                ComparisonOperator::NotContains,
                ComparisonOperator::StartsWith,
                ComparisonOperator::EndsWith,
                ComparisonOperator::In,
                ComparisonOperator::NotIn,
                ComparisonOperator::IsNull,
                ComparisonOperator::IsNotNull,
            ],
            FilterType::Choice => [
                ComparisonOperator::Equals,
                ComparisonOperator::NotEquals,
                ComparisonOperator::In,
                ComparisonOperator::NotIn,
                ComparisonOperator::IsNull,
                ComparisonOperator::IsNotNull,
            ],
            FilterType::Enum => [
                ComparisonOperator::Equals,
                ComparisonOperator::NotEquals,
                ComparisonOperator::In,
                ComparisonOperator::NotIn,
                ComparisonOperator::IsNull,
                ComparisonOperator::IsNotNull,
            ],
            FilterType::Boolean => [
                ComparisonOperator::Equals,
                ComparisonOperator::NotEquals,
                ComparisonOperator::IsNull,
                ComparisonOperator::IsNotNull,
            ],
            FilterType::Number => [
                ComparisonOperator::Equals,
                ComparisonOperator::NotEquals,
                ComparisonOperator::GreaterThan,
                ComparisonOperator::GreaterThanOrEquals,
                ComparisonOperator::LessThan,
                ComparisonOperator::LessThanOrEquals,
                ComparisonOperator::Between,
                ComparisonOperator::In,
                ComparisonOperator::NotIn,
                ComparisonOperator::IsNull,
                ComparisonOperator::IsNotNull,
            ],
            FilterType::NumberRange => [
                ComparisonOperator::Between,
            ],
            FilterType::Date => [
                ComparisonOperator::Equals,
                ComparisonOperator::NotEquals,
                ComparisonOperator::GreaterThan,
                ComparisonOperator::GreaterThanOrEquals,
                ComparisonOperator::LessThan,
                ComparisonOperator::LessThanOrEquals,
                ComparisonOperator::Between,
                ComparisonOperator::IsNull,
                ComparisonOperator::IsNotNull,
            ],
            FilterType::DateRange => [
                ComparisonOperator::Between,
            ],
        };

        if (!$nullable) {
            $filtered = [];
            foreach ($operators as $operator) {
                if (ComparisonOperator::IsNull !== $operator && ComparisonOperator::IsNotNull !== $operator) {
                    $filtered[] = $operator;
                }
            }

            return $filtered;
        }

        return $operators;
    }

    public static function isCompatible(FilterType $type, ComparisonOperator $operator, bool $nullable = true): bool
    {
        return in_array($operator, self::operatorsFor($type, $nullable), true);
    }
}
