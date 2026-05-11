<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Result;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Result\DatatableResult;

final class DatatableResultTest extends TestCase
{
    public function test_it_uses_default_values(): void
    {
        $result = new DatatableResult();

        self::assertSame([], $result->getRows());
        self::assertSame(1, $result->getPage());
        self::assertSame(25, $result->getPageSize());
        self::assertSame(0, $result->getTotalItems());
        self::assertSame(0, $result->getFilteredItems());
        self::assertSame(0, $result->getTotalPages());
        self::assertFalse($result->hasFilteredItems());
        self::assertFalse($result->hasRows());
        self::assertTrue($result->isEmpty());
    }

    public function test_it_stores_result_metadata(): void
    {
        $rows = [
            [
                'id' => 1,
                'email' => 'john@example.test',
            ],
            [
                'id' => 2,
                'email' => 'jane@example.test',
            ],
        ];

        $result = new DatatableResult(
            rows: $rows,
            page: 2,
            pageSize: 10,
            totalItems: 100,
            filteredItems: 35,
        );

        self::assertSame($rows, $result->getRows());
        self::assertSame(2, $result->getPage());
        self::assertSame(10, $result->getPageSize());
        self::assertSame(100, $result->getTotalItems());
        self::assertSame(35, $result->getFilteredItems());
        self::assertSame(4, $result->getTotalPages());
        self::assertTrue($result->hasFilteredItems());
        self::assertTrue($result->hasRows());
        self::assertFalse($result->isEmpty());
    }

    public function test_it_uses_total_items_as_filtered_items_when_no_filtered_count_is_given(): void
    {
        $result = new DatatableResult(
            rows: [
                ['id' => 1],
            ],
            page: 1,
            pageSize: 25,
            totalItems: 50,
        );

        self::assertSame(50, $result->getFilteredItems());
        self::assertSame(2, $result->getTotalPages());
        self::assertFalse($result->hasFilteredItems());
    }

    public function test_it_can_be_created_through_static_factory(): void
    {
        $result = DatatableResult::create(
            rows: [
                ['id' => 1],
            ],
            page: 1,
            pageSize: 10,
            totalItems: 12,
            filteredItems: 11,
        );

        self::assertSame([['id' => 1]], $result->getRows());
        self::assertSame(1, $result->getPage());
        self::assertSame(10, $result->getPageSize());
        self::assertSame(12, $result->getTotalItems());
        self::assertSame(11, $result->getFilteredItems());
        self::assertSame(2, $result->getTotalPages());
    }

    public function test_it_rejects_invalid_page(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The datatable result page must be greater than or equal to 1.');

        new DatatableResult(page: 0);
    }

    public function test_it_rejects_invalid_page_size(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The datatable result page size must be greater than or equal to 1.');

        new DatatableResult(pageSize: 0);
    }

    public function test_it_rejects_invalid_total_items_count(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The datatable result total items count must be greater than or equal to 0.');

        new DatatableResult(totalItems: -1);
    }

    public function test_it_rejects_invalid_filtered_items_count(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The datatable result filtered items count must be greater than or equal to 0.');

        new DatatableResult(filteredItems: -1);
    }
}
