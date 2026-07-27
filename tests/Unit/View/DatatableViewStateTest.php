<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\View;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Sorting\SortCriterion;
use Zhortein\DatatableBundle\State\DatatableState;
use Zhortein\DatatableBundle\View\DatatableViewState;

final class DatatableViewStateTest extends TestCase
{
    public function test_it_excludes_the_current_page_by_default(): void
    {
        $viewState = DatatableViewState::create($this->createState());

        self::assertFalse($viewState->includesPage());
        self::assertSame(1, $viewState->getState()->getPage());
        self::assertSame(50, $viewState->getState()->getPageSize());
        self::assertSame('alice', $viewState->getState()->getSearchQuery());
        self::assertSame('email', $viewState->getState()->getSortField());
        self::assertSame(SortDirection::Desc, $viewState->getState()->getSortDirection());
        self::assertSame([
            ['field' => 'email', 'direction' => 'desc'],
            ['field' => 'createdAt', 'direction' => 'asc'],
        ], array_map(
            static fn (SortCriterion $criterion): array => $criterion->toArray(),
            $viewState->getState()->getSorts(),
        ));
        self::assertSame(['status' => 'active'], $viewState->getState()->getFilters());
        self::assertSame(['email'], $viewState->getState()->getVisibleColumns());
    }

    public function test_the_current_page_can_be_included_explicitly(): void
    {
        $viewState = DatatableViewState::create($this->createState(), includePage: true);

        self::assertTrue($viewState->includesPage());
        self::assertSame(4, $viewState->getState()->getPage());
    }

    private function createState(): DatatableState
    {
        return DatatableState::create(
            page: 4,
            pageSize: 50,
            searchQuery: 'alice',
            sortField: 'email',
            sortDirection: SortDirection::Desc,
            sorts: [
                SortCriterion::create('email', SortDirection::Desc),
                SortCriterion::create('createdAt'),
            ],
            filters: ['status' => 'active'],
            advancedFilters: [
                'logic' => 'and',
                'conditions' => [],
            ],
            visibleColumns: ['email'],
            hiddenColumns: ['status'],
        );
    }
}
