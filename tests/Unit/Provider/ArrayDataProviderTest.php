<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;
use Zhortein\DatatableBundle\Request\DatatableRequest;

final class ArrayDataProviderTest extends TestCase
{
    public function test_it_supports_definition_with_array_provider_name(): void
    {
        $definition = new DatatableDefinition('users');
        $definition->setOption(ArrayDataProvider::OPTION_PROVIDER, ArrayDataProvider::PROVIDER_NAME);

        self::assertTrue(new ArrayDataProvider()->supports($definition));
    }

    public function test_it_supports_definition_with_rows_option(): void
    {
        $definition = new DatatableDefinition('users');
        $definition->setOption(ArrayDataProvider::OPTION_ROWS, []);

        self::assertTrue(new ArrayDataProvider()->supports($definition));
    }

    public function test_it_returns_paginated_result(): void
    {
        $result = new ArrayDataProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(page: 2, pageSize: 2),
        );

        self::assertSame(2, $result->getPage());
        self::assertSame(2, $result->getPageSize());
        self::assertSame(5, $result->getTotalItems());
        self::assertSame(5, $result->getFilteredItems());
        self::assertSame(3, $result->getTotalPages());
        self::assertSame([
            [
                'id' => 3,
                'email' => 'john@example.test',
                'displayName' => 'John',
            ],
            [
                'id' => 4,
                'email' => 'zoe@example.test',
                'displayName' => 'Zoe',
            ],
        ], $result->getRows());
    }

    public function test_it_applies_simple_search_on_searchable_columns(): void
    {
        $result = new ArrayDataProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(searchQuery: 'john'),
        );

        self::assertSame(5, $result->getTotalItems());
        self::assertSame(1, $result->getFilteredItems());
        self::assertSame([
            [
                'id' => 3,
                'email' => 'john@example.test',
                'displayName' => 'John',
            ],
        ], $result->getRows());
    }

    public function test_it_does_not_search_non_searchable_columns(): void
    {
        $result = new ArrayDataProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(searchQuery: '5'),
        );

        self::assertSame(5, $result->getTotalItems());
        self::assertSame(0, $result->getFilteredItems());
        self::assertSame([], $result->getRows());
    }

    public function test_it_sorts_rows_by_column(): void
    {
        $result = new ArrayDataProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(sortField: 'e.email', sortDirection: SortDirection::Asc),
        );

        self::assertSame([
            'alice@example.test',
            'bob@example.test',
            'john@example.test',
            'zoe.alt@example.test',
            'zoe@example.test',
        ], array_column($result->getRows(), 'email'));
    }

    public function test_it_sorts_rows_descending(): void
    {
        $result = new ArrayDataProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(sortField: 'e.email', sortDirection: SortDirection::Desc),
        );

        self::assertSame([
            'zoe@example.test',
            'zoe.alt@example.test',
            'john@example.test',
            'bob@example.test',
            'alice@example.test',
        ], array_column($result->getRows(), 'email'));
    }

    public function test_it_ignores_sort_on_non_sortable_column(): void
    {
        $result = new ArrayDataProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(sortField: 'e.id', sortDirection: SortDirection::Desc),
        );

        self::assertSame([1, 2, 3, 4, 5], array_column($result->getRows(), 'id'));
    }

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.id', visible: false, sortable: false, searchable: false)
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.displayName', label: 'Display name')
            ->setOption(ArrayDataProvider::OPTION_ROWS, [
                [
                    'id' => 1,
                    'email' => 'alice@example.test',
                    'displayName' => 'Alice',
                ],
                [
                    'id' => 2,
                    'email' => 'bob@example.test',
                    'displayName' => 'Bob',
                ],
                [
                    'id' => 3,
                    'email' => 'john@example.test',
                    'displayName' => 'John',
                ],
                [
                    'id' => 4,
                    'email' => 'zoe@example.test',
                    'displayName' => 'Zoe',
                ],
                [
                    'id' => 5,
                    'email' => 'zoe.alt@example.test',
                    'displayName' => 'Zoe Alt',
                ],
            ])
        ;

        return $definition;
    }
}
