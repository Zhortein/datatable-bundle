<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterType;
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;
use Zhortein\DatatableBundle\Request\DatatableRequest;

final class ArrayDataProviderUserFiltersTest extends TestCase
{
    public function test_it_applies_text_filter(): void
    {
        $result = new ArrayDataProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(filters: [
                'email' => 'alice',
            ]),
        );

        self::assertSame(['alice@example.test'], array_column($result->getRows(), 'email'));
        self::assertSame(1, $result->getFilteredItems());
    }

    public function test_it_applies_choice_filter(): void
    {
        $result = new ArrayDataProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(filters: [
                'status' => 'enabled',
            ]),
        );

        self::assertSame(['alice@example.test', 'charlie@example.test'], array_column($result->getRows(), 'email'));
        self::assertSame(2, $result->getFilteredItems());
    }

    public function test_it_applies_boolean_filter(): void
    {
        $result = new ArrayDataProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(filters: [
                'enabled' => '0',
            ]),
        );

        self::assertSame(['bob@example.test'], array_column($result->getRows(), 'email'));
        self::assertSame(1, $result->getFilteredItems());
    }

    public function test_it_applies_enum_filter_to_enum_instances(): void
    {
        $definition = new DatatableDefinition('users');
        $definition
            ->addColumn('status', enumClass: ArrayStatus::class)
            ->addFilter('status', 'status', enumClass: ArrayStatus::class)
            ->setOption(ArrayDataProvider::OPTION_ROWS, [
                ['status' => ArrayStatus::Enabled],
                ['status' => ArrayStatus::Disabled],
            ])
        ;

        $result = new ArrayDataProvider()->getData(
            $definition,
            DatatableRequest::create(filters: ['status' => 'enabled']),
        );

        self::assertSame([ArrayStatus::Enabled], array_column($result->getRows(), 'status'));
    }

    public function test_it_ignores_unknown_filters(): void
    {
        $result = new ArrayDataProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(filters: [
                'unknown' => 'alice',
            ]),
        );

        self::assertSame(3, $result->getFilteredItems());
        self::assertCount(3, $result->getRows());
    }

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('id', visible: false, sortable: false, searchable: false)
            ->addColumn('email', label: 'Email')
            ->addColumn('displayName', label: 'Display name')
            ->addColumn('enabled', label: 'Enabled', type: 'boolean')
            ->addColumn('status', label: 'Status')
            ->addFilter('email', 'email', type: FilterType::Text)
            ->addFilter('status', 'status', type: FilterType::Choice)
            ->addFilter('enabled', 'enabled', type: FilterType::Boolean)
            ->setOption(ArrayDataProvider::OPTION_ROWS, [
                [
                    'id' => 1,
                    'email' => 'alice@example.test',
                    'displayName' => 'Alice',
                    'enabled' => true,
                    'status' => 'enabled',
                ],
                [
                    'id' => 2,
                    'email' => 'bob@example.test',
                    'displayName' => 'Bob',
                    'enabled' => false,
                    'status' => 'disabled',
                ],
                [
                    'id' => 3,
                    'email' => 'charlie@example.test',
                    'displayName' => 'Charlie',
                    'enabled' => true,
                    'status' => 'enabled',
                ],
            ])
        ;

        return $definition;
    }
}

enum ArrayStatus: string
{
    case Enabled = 'enabled';
    case Disabled = 'disabled';
}
