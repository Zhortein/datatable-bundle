<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Filter\Expression;

use Zhortein\DatatableBundle\Exception\InvalidExpressionException;

final readonly class Condition implements ExpressionInterface
{
    public function __construct(
        public string $field,
        public ComparisonOperator $operator,
        public mixed $value = null,
    ) {
        if ('' === trim($field)) {
            throw new InvalidExpressionException('Condition field cannot be empty.');
        }

        $this->validateValue($operator, $value);
    }

    public function getDepth(): int
    {
        return 0;
    }

    private function validateValue(ComparisonOperator $operator, mixed $value): void
    {
        switch ($operator) {
            case ComparisonOperator::IsNull:
            case ComparisonOperator::IsNotNull:
                // No value expected
                break;
            case ComparisonOperator::Between:
                if (!is_array($value) || 2 !== count($value)) {
                    throw new InvalidExpressionException('Between operator requires an array of exactly 2 values.');
                }
                break;
            case ComparisonOperator::In:
            case ComparisonOperator::NotIn:
                if (!is_array($value)) {
                    throw new InvalidExpressionException(sprintf('"%s" operator requires an array of values.', $operator->value));
                }
                break;
            default:
                if (null === $value) {
                    throw new InvalidExpressionException(sprintf('"%s" operator requires a non-null value.', $operator->value));
                }
                break;
        }
    }
}
