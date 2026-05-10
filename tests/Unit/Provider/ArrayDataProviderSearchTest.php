<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;
use Zhortein\DatatableBundle\Request\DatatableRequest;

final class ArrayDataProviderSearchTest extends TestCase
{
    public function test_it_searches_with_contains_matching(): void
    {
        $result = new ArrayDataProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(searchQuery: 'ice'),
        );

        self::assertSame(1, $result->getFilteredItems());
        self::assertSame(['alice@example.test'], array_column($result->getRows(), 'email'));
    }

    public function test_it_searches_case_insensitively(): void
    {
        $result = new ArrayDataProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(searchQuery: 'CHARLIE'),
        );

        self::assertSame(1, $result->getFilteredItems());
        self::assertSame(['charlie@example.test'], array_column($result->getRows(), 'email'));
    }

    public function test_it_matches_middle_of_display_name(): void
    {
        $result = new ArrayDataProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(searchQuery: 'lic'),
        );

        self::assertSame(1, $result->getFilteredItems());
        self::assertSame(['alice@example.test'], array_column($result->getRows(), 'email'));
    }

    public function test_it_does_not_search_non_searchable_columns(): void
    {
        $result = new ArrayDataProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(searchQuery: 'internal-001'),
        );

        self::assertSame(0, $result->getFilteredItems());
        self::assertSame([], $result->getRows());
    }

    public function test_it_returns_no_rows_when_no_value_matches(): void
    {
        $result = new ArrayDataProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(searchQuery: 'missing'),
        );

        self::assertSame(0, $result->getFilteredItems());
        self::assertSame([], $result->getRows());
    }

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('id', visible: false, sortable: false, searchable: false)
            ->addColumn('email', label: 'Email')
            ->addColumn('displayName', label: 'Display name')
            ->addColumn('internalCode', label: 'Internal code', searchable: false)
            ->setOption(ArrayDataProvider::OPTION_ROWS, [
                [
                    'id' => 1,
                    'email' => 'alice@example.test',
                    'displayName' => 'Alice Cooper',
                    'internalCode' => 'internal-001',
                ],
                [
                    'id' => 2,
                    'email' => 'bob@example.test',
                    'displayName' => 'Bob Dylan',
                    'internalCode' => 'internal-002',
                ],
                [
                    'id' => 3,
                    'email' => 'charlie@example.test',
                    'displayName' => 'Charlie Parker',
                    'internalCode' => 'internal-003',
                ],
            ])
        ;

        return $definition;
    }
}
