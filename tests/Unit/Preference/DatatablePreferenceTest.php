<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Preference;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Preference\DatatablePreference;

final class DatatablePreferenceTest extends TestCase
{
    public function test_empty_preference_has_no_render_options(): void
    {
        $preference = DatatablePreference::empty();

        self::assertTrue($preference->isEmpty());
        self::assertNull($preference->getPageSize());
        self::assertNull($preference->getSortField());
        self::assertNull($preference->getSortDirection());
        self::assertSame([], $preference->getVisibleColumns());
        self::assertSame([], $preference->getHiddenColumns());
        self::assertSame([], $preference->toRenderOptions());
    }

    public function test_it_stores_preference_values(): void
    {
        $preference = DatatablePreference::create(
            pageSize: 50,
            sortField: 'e.email',
            sortDirection: SortDirection::Desc,
            visibleColumns: ['e.email', 'e.displayName'],
            hiddenColumns: ['e.createdAt'],
        );

        self::assertFalse($preference->isEmpty());
        self::assertSame(50, $preference->getPageSize());
        self::assertSame('e.email', $preference->getSortField());
        self::assertSame(SortDirection::Desc, $preference->getSortDirection());
        self::assertSame(['e.email', 'e.displayName'], $preference->getVisibleColumns());
        self::assertSame(['e.createdAt'], $preference->getHiddenColumns());
        self::assertSame([
            'pageSize' => 50,
            'sortField' => 'e.email',
            'sortDirection' => 'desc',
            'visibleColumns' => ['e.email', 'e.displayName'],
            'hiddenColumns' => ['e.createdAt'],
        ], $preference->toRenderOptions());
    }

    public function test_it_normalizes_column_lists_and_sort_field(): void
    {
        $preference = DatatablePreference::create(
            sortField: ' ',
            visibleColumns: [' e.email ', '', 'e.email', 'e.displayName'],
            hiddenColumns: [' ', 'e.createdAt', 'e.createdAt'],
        );

        self::assertNull($preference->getSortField());
        self::assertSame(['e.email', 'e.displayName'], $preference->getVisibleColumns());
        self::assertSame(['e.createdAt'], $preference->getHiddenColumns());
    }

    public function test_it_rejects_invalid_page_size(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The datatable preference page size must be greater than or equal to 1.');

        DatatablePreference::create(pageSize: 0);
    }
}
