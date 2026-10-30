<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Doctrine;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Doctrine\DoctrineDayRange;

#[AllowMockObjectsWithoutExpectations]
final class DoctrineDayRangeTest extends TestCase
{
    public function test_it_binds_the_first_instant_of_the_day_and_of_the_next_one(): void
    {
        $queryBuilder = new QueryBuilder($this->createMock(EntityManagerInterface::class));
        $day = DoctrineDayRange::fromValue('2026-06-30', Types::DATETIME_IMMUTABLE);

        self::assertNotNull($day);
        self::assertSame(':start', $day->bindStart($queryBuilder, 'start'));
        self::assertSame(':end', $day->bindNextDay($queryBuilder, 'end'));
        self::assertEquals(new \DateTimeImmutable('2026-06-30'), $queryBuilder->getParameter('start')?->getValue());
        self::assertEquals(new \DateTimeImmutable('2026-07-01'), $queryBuilder->getParameter('end')?->getValue());
    }

    /**
     * @return iterable<string, array{string|null, string}>
     */
    public static function columnTypes(): iterable
    {
        yield 'date' => [Types::DATE_MUTABLE, Types::DATE_IMMUTABLE];
        yield 'immutable date' => [Types::DATE_IMMUTABLE, Types::DATE_IMMUTABLE];
        yield 'date and time' => [Types::DATETIME_MUTABLE, Types::DATETIME_IMMUTABLE];
        yield 'date and time with zone' => [Types::DATETIMETZ_MUTABLE, Types::DATETIMETZ_IMMUTABLE];
        yield 'unknown' => [null, Types::DATETIME_IMMUTABLE];
    }

    #[DataProvider('columnTypes')]
    public function test_it_binds_the_bounds_with_the_column_date_type(?string $columnType, string $parameterType): void
    {
        $queryBuilder = new QueryBuilder($this->createMock(EntityManagerInterface::class));

        DoctrineDayRange::fromValue('2026-06-30', $columnType)?->bindStart($queryBuilder, 'start');

        self::assertSame($parameterType, $queryBuilder->getParameter('start')?->getType());
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function valuesNamingNoDay(): iterable
    {
        yield 'impossible date' => ['2026-02-31'];
        yield 'date and time' => ['2026-06-30 12:00'];
        yield 'other format' => ['30/06/2026'];
        yield 'number' => [20260630];
        yield 'missing' => [null];
    }

    #[DataProvider('valuesNamingNoDay')]
    public function test_it_names_no_day_for_a_value_that_is_not_a_date(mixed $value): void
    {
        self::assertNull(DoctrineDayRange::fromValue($value, Types::DATETIME_IMMUTABLE));
    }
}
