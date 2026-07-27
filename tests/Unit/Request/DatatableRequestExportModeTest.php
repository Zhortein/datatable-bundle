<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Request;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Sorting\SortCriterion;

final class DatatableRequestExportModeTest extends TestCase
{
    public function test_pagination_is_enabled_by_default(): void
    {
        $request = new DatatableRequest();

        self::assertTrue($request->isPaginationEnabled());
    }

    public function test_without_pagination_disables_pagination_and_keeps_state(): void
    {
        $request = DatatableRequest::create(
            page: 3,
            pageSize: 25,
            searchQuery: 'alice',
            sortField: 'e.email',
            sortDirection: 'desc',
            filters: ['enabled' => '1'],
            visibleColumns: ['e.email'],
            hiddenColumns: ['e.createdAt'],
            options: ['foo' => 'bar'],
            sorts: [
                SortCriterion::create('e.email', 'desc'),
                SortCriterion::create('e.createdAt'),
            ],
        );

        $withoutPagination = $request->withoutPagination();

        self::assertSame(1, $withoutPagination->getPage());
        self::assertSame(25, $withoutPagination->getPageSize());
        self::assertFalse($withoutPagination->isPaginationEnabled());
        self::assertSame('alice', $withoutPagination->getSearchQuery());
        self::assertSame('e.email', $withoutPagination->getSortField());
        self::assertSame('desc', $withoutPagination->getSortDirection()->value);
        self::assertSame([
            ['field' => 'e.email', 'direction' => 'desc'],
            ['field' => 'e.createdAt', 'direction' => 'asc'],
        ], array_map(
            static fn (SortCriterion $criterion): array => $criterion->toArray(),
            $withoutPagination->getSorts(),
        ));
        self::assertSame(['enabled' => '1'], $withoutPagination->getFilters());
        self::assertSame(['e.email'], $withoutPagination->getVisibleColumns());
        self::assertSame(['e.createdAt'], $withoutPagination->getHiddenColumns());
        self::assertSame('bar', $withoutPagination->getOption('foo'));
        self::assertTrue($withoutPagination->getOption('disablePagination'));
    }
}
