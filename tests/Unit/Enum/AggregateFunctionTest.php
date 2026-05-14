<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Enum\AggregateFunction;

final class AggregateFunctionTest extends TestCase
{
    public function test_it_exposes_dql_function_name(): void
    {
        self::assertSame('COUNT', AggregateFunction::Count->getDqlFunction());
        self::assertSame('SUM', AggregateFunction::Sum->getDqlFunction());
        self::assertSame('MIN', AggregateFunction::Min->getDqlFunction());
        self::assertSame('MAX', AggregateFunction::Max->getDqlFunction());
        self::assertSame('AVG', AggregateFunction::Avg->getDqlFunction());
    }

    public function test_it_creates_function_from_valid_string(): void
    {
        self::assertSame(AggregateFunction::Count, AggregateFunction::fromNullableString('count'));
        self::assertSame(AggregateFunction::Sum, AggregateFunction::fromNullableString('sum'));
        self::assertSame(AggregateFunction::Min, AggregateFunction::fromNullableString('min'));
        self::assertSame(AggregateFunction::Max, AggregateFunction::fromNullableString('max'));
        self::assertSame(AggregateFunction::Avg, AggregateFunction::fromNullableString('avg'));
        self::assertSame(AggregateFunction::Sum, AggregateFunction::fromNullableString(' SUM '));
    }

    public function test_it_falls_back_to_count_for_null_empty_or_unknown_value(): void
    {
        self::assertSame(AggregateFunction::Count, AggregateFunction::fromNullableString(null));
        self::assertSame(AggregateFunction::Count, AggregateFunction::fromNullableString(''));
        self::assertSame(AggregateFunction::Count, AggregateFunction::fromNullableString('unknown'));
    }
}
