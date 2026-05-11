<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Request;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Request\DatatableRequest;

final class DatatableRequestTest extends TestCase
{
    public function test_it_uses_default_values(): void
    {
        $request = new DatatableRequest();

        self::assertSame(1, $request->getPage());
        self::assertSame(25, $request->getPageSize());
        self::assertSame(0, $request->getOffset());
        self::assertNull($request->getSearchQuery());
        self::assertFalse($request->hasSearchQuery());
        self::assertNull($request->getSortField());
        self::assertFalse($request->hasSort());
        self::assertSame(SortDirection::Asc, $request->getSortDirection());
        self::assertSame([], $request->getFilters());
        self::assertFalse($request->hasFilters());
        self::assertSame([], $request->getOptions());
    }

    public function test_it_stores_request_metadata(): void
    {
        $request = new DatatableRequest(
            page: 3,
            pageSize: 50,
            searchQuery: 'john',
            sortField: 'e.email',
            sortDirection: SortDirection::Desc,
            filters: ['status' => 'enabled'],
            options: ['foo' => 'bar'],
        );

        self::assertSame(3, $request->getPage());
        self::assertSame(50, $request->getPageSize());
        self::assertSame(100, $request->getOffset());
        self::assertSame('john', $request->getSearchQuery());
        self::assertTrue($request->hasSearchQuery());
        self::assertSame('e.email', $request->getSortField());
        self::assertTrue($request->hasSort());
        self::assertSame(SortDirection::Desc, $request->getSortDirection());
        self::assertSame(['status' => 'enabled'], $request->getFilters());
        self::assertTrue($request->hasFilters());
        self::assertTrue($request->hasFilter('status'));
        self::assertSame('enabled', $request->getFilter('status'));
        self::assertSame('fallback', $request->getFilter('missing', 'fallback'));
        self::assertSame(['foo' => 'bar'], $request->getOptions());
        self::assertSame('bar', $request->getOption('foo'));
        self::assertSame('fallback', $request->getOption('missing', 'fallback'));
    }

    public function test_it_creates_request_from_string_sort_direction(): void
    {
        $request = DatatableRequest::create(
            page: 2,
            pageSize: 10,
            searchQuery: '  john  ',
            sortField: '  e.email  ',
            sortDirection: 'desc',
        );

        self::assertSame(2, $request->getPage());
        self::assertSame(10, $request->getPageSize());
        self::assertSame('john', $request->getSearchQuery());
        self::assertSame('e.email', $request->getSortField());
        self::assertSame(SortDirection::Desc, $request->getSortDirection());
    }

    public function test_it_normalizes_empty_search_and_sort_values(): void
    {
        $request = DatatableRequest::create(
            searchQuery: '   ',
            sortField: '',
        );

        self::assertNull($request->getSearchQuery());
        self::assertFalse($request->hasSearchQuery());
        self::assertNull($request->getSortField());
        self::assertFalse($request->hasSort());
    }

    public function test_it_normalizes_filter_values(): void
    {
        $request = DatatableRequest::create(filters: [
            'email' => '  alice@example.test  ',
            'status' => '',
            'roles' => ['admin', '', 'user'],
            'range' => [
                'from' => ' 10 ',
                'to' => ' ',
            ],
            'invalid' => new \stdClass(),
        ]);

        self::assertSame([
            'email' => 'alice@example.test',
            'roles' => ['admin', 'user'],
            'range' => ['from' => '10'],
        ], $request->getFilters());
    }

    public function test_it_rejects_invalid_page(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The datatable page must be greater than or equal to 1.');

        new DatatableRequest(page: 0);
    }

    public function test_it_rejects_invalid_page_size(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The datatable page size must be greater than or equal to 1.');

        new DatatableRequest(pageSize: 0);
    }
}
