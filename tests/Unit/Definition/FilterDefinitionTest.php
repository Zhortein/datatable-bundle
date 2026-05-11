<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Definition;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\FilterDefinition;
use Zhortein\DatatableBundle\Enum\FilterOperator;

final class FilterDefinitionTest extends TestCase
{
    public function test_it_stores_filter_metadata(): void
    {
        $filter = new FilterDefinition(
            field: 'e.status',
            operator: FilterOperator::Equals,
            value: 'enabled',
        );

        self::assertSame('e.status', $filter->getField());
        self::assertSame(FilterOperator::Equals, $filter->getOperator());
        self::assertSame('enabled', $filter->getValue());
        self::assertNull($filter->getSecondValue());
        self::assertFalse($filter->isUnary());
    }

    public function test_it_detects_unary_operator(): void
    {
        $filter = new FilterDefinition(
            field: 'e.deletedAt',
            operator: FilterOperator::IsNull,
        );

        self::assertTrue($filter->isUnary());
    }
}
