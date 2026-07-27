<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\State;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\State\DatatableState;

final class DatatableStateTest extends TestCase
{
    public function test_it_normalizes_the_canonical_shareable_state(): void
    {
        $state = DatatableState::create(
            page: 3,
            pageSize: 50,
            searchQuery: ' alice ',
            sortField: ' email ',
            sortDirection: 'DESC',
            filters: [
                'email' => ' @example.test ',
                'empty' => ' ',
                'range' => [
                    'from' => ' 2026-01-01 ',
                    'to' => null,
                ],
            ],
            advancedFilters: [
                'logic' => 'and',
                'conditions' => [
                    ['field' => 'email', 'operator' => 'contains', 'value' => ' '],
                ],
            ],
            visibleColumns: ['email', 'email', 'createdAt'],
            hiddenColumns: [' internal '],
        );

        self::assertSame(3, $state->getPage());
        self::assertSame(50, $state->getPageSize());
        self::assertSame('alice', $state->getSearchQuery());
        self::assertSame('email', $state->getSortField());
        self::assertSame(SortDirection::Desc, $state->getSortDirection());
        self::assertSame([
            'email' => '@example.test',
            'range' => ['from' => '2026-01-01'],
        ], $state->getFilters());
        self::assertSame(['email', 'createdAt'], $state->getVisibleColumns());
        self::assertSame(['internal'], $state->getHiddenColumns());

        $advancedFilters = $state->getAdvancedFilters();
        self::assertIsArray($advancedFilters['conditions']);
        self::assertIsArray($advancedFilters['conditions'][0]);
        self::assertSame(' ', $advancedFilters['conditions'][0]['value']);
        self::assertSame(DatatableState::VERSION, $state->toArray()['version']);
    }

    public function test_it_rejects_invalid_pagination(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('page must be greater than or equal to 1');

        DatatableState::create(page: 0);
    }
}
