<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Provider\Http;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterOperator;
use Zhortein\DatatableBundle\Enum\FilterType;
use Zhortein\DatatableBundle\Enum\HttpPaginationStrategy;
use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Exception\HttpDataProviderException;
use Zhortein\DatatableBundle\Filter\Expression\AdvancedFilterExpression;
use Zhortein\DatatableBundle\Filter\Expression\ComparisonOperator;
use Zhortein\DatatableBundle\Filter\Expression\Condition;
use Zhortein\DatatableBundle\Filter\Expression\Group;
use Zhortein\DatatableBundle\Provider\Http\DefaultHttpRequestMapper;
use Zhortein\DatatableBundle\Provider\Http\HttpProviderCapabilities;
use Zhortein\DatatableBundle\Provider\Http\HttpProviderConfiguration;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Sorting\SortCriterion;

final class DefaultHttpRequestMapperTest extends TestCase
{
    public function test_it_maps_page_search_sorts_filters_advanced_filters_and_allowlisted_context(): void
    {
        $definition = (new DatatableDefinition('remote-users'))
            ->addFilter('status', 'status', type: FilterType::Choice)
            ->addPermanentFilter('tenantStatus', FilterOperator::Equals, 'enabled')
            ->setContext(new DatatableContext([
                'tenant' => 'acme',
                'secret' => 'must-not-leave',
            ]))
        ;
        $request = DatatableRequest::create(
            page: 3,
            pageSize: 20,
            searchQuery: 'alice',
            filters: ['status' => 'active'],
            sorts: [new SortCriterion('displayName', SortDirection::Desc)],
            advancedFilterExpression: new AdvancedFilterExpression(
                new Group(children: [
                    new Condition('age', ComparisonOperator::GreaterThan, 18),
                ]),
            ),
        );
        $configuration = new HttpProviderConfiguration(
            endpoint: 'https://api.example.test/users',
            capabilities: new HttpProviderCapabilities(
                search: true,
                sorting: true,
                simpleFilters: true,
                advancedFilters: true,
            ),
            fieldMap: [
                'displayName' => 'profile.name',
                'age' => 'profile.age',
            ],
            operatorMap: ['gt' => 'greater_than'],
            contextKeys: ['tenant'],
        );

        $transportRequest = (new DefaultHttpRequestMapper())->mapRequest($definition, $request, $configuration);
        $query = $transportRequest->getQuery();

        self::assertSame(3, $query['page']);
        self::assertSame(20, $query['page_size']);
        self::assertSame('alice', $query['search']);
        self::assertSame([
            ['field' => 'profile.name', 'direction' => 'desc'],
        ], $query['sort']);
        self::assertSame([
            ['field' => 'tenantStatus', 'operator' => 'eq', 'value' => 'enabled'],
            ['field' => 'status', 'operator' => 'eq', 'value' => 'active'],
        ], $query['filters']);
        self::assertSame([
            'logic' => 'and',
            'children' => [
                ['field' => 'profile.age', 'operator' => 'greater_than', 'value' => 18],
            ],
        ], $query['advanced_filters']);
        self::assertSame(['tenant' => 'acme'], $query['context']);
        self::assertStringNotContainsString('secret', json_encode($query, \JSON_THROW_ON_ERROR));
    }

    public function test_it_maps_offset_and_cursor_pagination(): void
    {
        $definition = new DatatableDefinition('remote-users');
        $capabilities = new HttpProviderCapabilities([
            HttpPaginationStrategy::Offset,
            HttpPaginationStrategy::Cursor,
        ]);
        $mapper = new DefaultHttpRequestMapper();

        $offsetRequest = $mapper->mapRequest(
            $definition,
            DatatableRequest::create(page: 3, pageSize: 10),
            new HttpProviderConfiguration(
                endpoint: 'https://api.example.test/users',
                capabilities: $capabilities,
                paginationStrategy: HttpPaginationStrategy::Offset,
            ),
        );
        $cursorRequest = $mapper->mapRequest(
            $definition,
            DatatableRequest::create(pageSize: 10, options: ['http_cursor' => 'next-42']),
            new HttpProviderConfiguration(
                endpoint: 'https://api.example.test/users',
                capabilities: $capabilities,
                paginationStrategy: HttpPaginationStrategy::Cursor,
            ),
        );

        self::assertSame(['offset' => 20, 'limit' => 10], $offsetRequest->getQuery());
        self::assertSame(['cursor' => 'next-42', 'limit' => 10], $cursorRequest->getQuery());
    }

    public function test_it_rejects_operations_not_declared_by_the_remote_api(): void
    {
        $this->expectException(HttpDataProviderException::class);
        $this->expectExceptionMessage('does not support search');

        (new DefaultHttpRequestMapper())->mapRequest(
            new DatatableDefinition('remote-users'),
            DatatableRequest::create(searchQuery: 'alice'),
            new HttpProviderConfiguration(
                endpoint: 'https://api.example.test/users',
                capabilities: new HttpProviderCapabilities(),
            ),
        );
    }
}
