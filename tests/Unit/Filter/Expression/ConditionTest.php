<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Filter\Expression;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Exception\InvalidExpressionException;
use Zhortein\DatatableBundle\Filter\Expression\ComparisonOperator;
use Zhortein\DatatableBundle\Filter\Expression\Condition;

final class ConditionTest extends TestCase
{
    public function test_valid_condition(): void
    {
        $condition = new Condition('email', ComparisonOperator::Contains, 'gmail.com');
        self::assertSame('email', $condition->field);
        self::assertSame(ComparisonOperator::Contains, $condition->operator);
        self::assertSame('gmail.com', $condition->value);
        self::assertSame(0, $condition->getDepth());
    }

    public function test_empty_field_throws_exception(): void
    {
        $this->expectException(InvalidExpressionException::class);
        $this->expectExceptionMessage('Condition field cannot be empty.');
        new Condition('', ComparisonOperator::Equals, 'value');
    }

    public function test_between_requires_two_values(): void
    {
        $this->expectException(InvalidExpressionException::class);
        $this->expectExceptionMessage('Between operator requires an array of exactly 2 values.');
        new Condition('age', ComparisonOperator::Between, [10]);
    }

    public function test_in_requires_array(): void
    {
        $this->expectException(InvalidExpressionException::class);
        $this->expectExceptionMessage('"in" operator requires an array of values.');
        new Condition('status', ComparisonOperator::In, 'active');
    }

    public function test_null_value_throws_exception_for_most_operators(): void
    {
        $this->expectException(InvalidExpressionException::class);
        $this->expectExceptionMessage('"eq" operator requires a non-null value.');
        new Condition('name', ComparisonOperator::Equals, null);
    }

    public function test_is_null_allows_null_value(): void
    {
        $condition = new Condition('deletedAt', ComparisonOperator::IsNull);
        self::assertNull($condition->value);
    }
}
