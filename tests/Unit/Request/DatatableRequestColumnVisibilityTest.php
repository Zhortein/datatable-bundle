<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Request;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Request\DatatableRequest;

final class DatatableRequestColumnVisibilityTest extends TestCase
{
    public function test_it_stores_column_visibility_state(): void
    {
        $request = new DatatableRequest(
            visibleColumns: ['e.email', 'e.displayName'],
            hiddenColumns: ['e.createdAt'],
        );

        self::assertSame(['e.email', 'e.displayName'], $request->getVisibleColumns());
        self::assertSame(['e.createdAt'], $request->getHiddenColumns());
        self::assertTrue($request->hasColumnVisibilityState());
        self::assertSame([
            'visibleColumns' => ['e.email', 'e.displayName'],
            'hiddenColumns' => ['e.createdAt'],
            'sortField' => null,
            'sortDirection' => SortDirection::Asc->value,
            'sorts' => [],
        ], $request->getColumnVisibilityOptions());
    }

    public function test_it_includes_sort_state_in_column_visibility_options(): void
    {
        $request = new DatatableRequest(
            sortField: 'e.email',
            sortDirection: SortDirection::Desc,
        );

        self::assertSame([
            'visibleColumns' => [],
            'hiddenColumns' => [],
            'sortField' => 'e.email',
            'sortDirection' => 'desc',
            'sorts' => [
                ['field' => 'e.email', 'direction' => 'desc'],
            ],
        ], $request->getColumnVisibilityOptions());
    }

    public function test_it_normalizes_column_visibility_state(): void
    {
        $request = DatatableRequest::create(
            visibleColumns: [' e.email ', '', 'e.email', 'e.displayName'],
            hiddenColumns: [' ', 'e.createdAt', 'e.createdAt'],
        );

        self::assertSame(['e.email', 'e.displayName'], $request->getVisibleColumns());
        self::assertSame(['e.createdAt'], $request->getHiddenColumns());
    }

    public function test_it_reports_empty_column_visibility_state(): void
    {
        $request = new DatatableRequest();

        self::assertSame([], $request->getVisibleColumns());
        self::assertSame([], $request->getHiddenColumns());
        self::assertFalse($request->hasColumnVisibilityState());
    }

    public function test_it_keeps_existing_request_defaults(): void
    {
        $request = new DatatableRequest();

        self::assertSame(1, $request->getPage());
        self::assertSame(25, $request->getPageSize());
        self::assertSame(SortDirection::Asc, $request->getSortDirection());
    }
}
