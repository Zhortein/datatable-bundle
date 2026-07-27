<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Factory;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Factory\AdvancedFilterExpressionFactory;
use Zhortein\DatatableBundle\Factory\DatatableRequestFactory;
use Zhortein\DatatableBundle\State\DatatableState;

final class DatatableRequestFactoryTest extends TestCase
{
    private DatatableRequestFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new DatatableRequestFactory(new AdvancedFilterExpressionFactory());
    }

    public function test_it_creates_request_from_query_parameters(): void
    {
        $datatableRequest = $this->factory->createFromRequest(new Request([
            'page' => '3',
            'pageSize' => '50',
            'search' => ' john ',
            'sortField' => ' e.email ',
            'sortDirection' => 'desc',
            'filters' => [
                'status' => 'enabled',
            ],
            'options' => [
                'foo' => 'bar',
            ],
        ]));

        self::assertSame(3, $datatableRequest->getPage());
        self::assertSame(50, $datatableRequest->getPageSize());
        self::assertSame(100, $datatableRequest->getOffset());
        self::assertSame('john', $datatableRequest->getSearchQuery());
        self::assertSame('e.email', $datatableRequest->getSortField());
        self::assertSame(SortDirection::Desc, $datatableRequest->getSortDirection());
        self::assertSame(['status' => 'enabled'], $datatableRequest->getFilters());
        self::assertSame(['foo' => 'bar'], $datatableRequest->getOptions());
    }

    public function test_request_payload_overrides_query_parameters(): void
    {
        $datatableRequest = $this->factory->createFromRequest(new Request(
            query: [
                'page' => '1',
                'pageSize' => '10',
                'search' => 'query',
                'sortDirection' => 'asc',
                'filters' => [
                    'status' => 'query',
                ],
            ],
            request: [
                'page' => '2',
                'pageSize' => '25',
                'search' => 'payload',
                'sortDirection' => 'desc',
                'filters' => [
                    'status' => 'payload',
                ],
            ],
        ));

        self::assertSame(2, $datatableRequest->getPage());
        self::assertSame(25, $datatableRequest->getPageSize());
        self::assertSame('payload', $datatableRequest->getSearchQuery());
        self::assertSame(SortDirection::Desc, $datatableRequest->getSortDirection());
        self::assertSame(['status' => 'payload'], $datatableRequest->getFilters());
    }

    public function test_it_uses_defaults_when_parameters_are_missing(): void
    {
        $datatableRequest = $this->factory->createFromRequest(new Request());

        self::assertSame(DatatableRequestFactory::DEFAULT_PAGE, $datatableRequest->getPage());
        self::assertSame(DatatableRequestFactory::DEFAULT_PAGE_SIZE, $datatableRequest->getPageSize());
        self::assertNull($datatableRequest->getSearchQuery());
        self::assertNull($datatableRequest->getSortField());
        self::assertSame(SortDirection::Asc, $datatableRequest->getSortDirection());
        self::assertSame([], $datatableRequest->getFilters());
        self::assertSame([], $datatableRequest->getOptions());
    }

    public function test_it_falls_back_to_defaults_for_invalid_values(): void
    {
        $datatableRequest = $this->factory->createFromRequest(new Request([
            'page' => '-10',
            'pageSize' => 'invalid',
            'search' => [],
            'sortField' => [],
            'sortDirection' => 'invalid',
            'filters' => 'invalid',
            'options' => 'invalid',
        ]));

        self::assertSame(DatatableRequestFactory::DEFAULT_PAGE, $datatableRequest->getPage());
        self::assertSame(DatatableRequestFactory::DEFAULT_PAGE_SIZE, $datatableRequest->getPageSize());
        self::assertNull($datatableRequest->getSearchQuery());
        self::assertNull($datatableRequest->getSortField());
        self::assertSame(SortDirection::Asc, $datatableRequest->getSortDirection());
        self::assertSame([], $datatableRequest->getFilters());
        self::assertSame([], $datatableRequest->getOptions());
    }

    public function test_it_caps_page_size(): void
    {
        $datatableRequest = $this->factory->createFromRequest(new Request([
            'pageSize' => '999999',
        ]));

        self::assertSame(DatatableRequestFactory::MAX_PAGE_SIZE, $datatableRequest->getPageSize());
    }

    public function test_it_normalizes_empty_strings(): void
    {
        $datatableRequest = $this->factory->createFromRequest(new Request([
            'search' => '   ',
            'sortField' => '',
        ]));

        self::assertNull($datatableRequest->getSearchQuery());
        self::assertNull($datatableRequest->getSortField());
    }

    public function test_it_reads_advanced_filters_from_query_parameters(): void
    {
        $payload = [
            'logic' => 'AND',
            'children' => [
                [
                    'field' => 'email',
                    'operator' => 'contains',
                    'value' => 'gmail.com',
                ],
            ],
        ];

        $datatableRequest = $this->factory->createFromRequest(new Request([
            'advancedFilters' => $payload,
        ]));

        self::assertTrue($datatableRequest->hasAdvancedFilters());
        self::assertNotNull($datatableRequest->getAdvancedFilterExpression());

        /** @var \Zhortein\DatatableBundle\Filter\Expression\Condition $condition */
        $condition = $datatableRequest->getAdvancedFilterExpression()->root->children[0];
        self::assertSame('email', $condition->field);
    }

    public function test_it_reads_advanced_filters_from_alternative_key(): void
    {
        $payload = [
            'logic' => 'AND',
            'children' => [
                [
                    'field' => 'email',
                    'operator' => 'contains',
                    'value' => 'gmail.com',
                ],
            ],
        ];

        $datatableRequest = $this->factory->createFromRequest(new Request([
            'filterExpression' => $payload,
        ]));

        self::assertTrue($datatableRequest->hasAdvancedFilters());
        self::assertNotNull($datatableRequest->getAdvancedFilterExpression());
    }

    public function test_it_exposes_the_canonical_state_read_from_the_request(): void
    {
        $state = $this->factory->createStateFromRequest(new Request([
            'page' => '2',
            'pageSize' => '50',
            'search' => 'alice',
            'sortField' => 'email',
            'sortDirection' => 'desc',
            'filters' => ['status' => 'active'],
            'advancedFilters' => [
                'logic' => 'and',
                'conditions' => [
                    ['field' => 'email', 'operator' => 'contains', 'value' => '@example.test'],
                ],
            ],
            'visibleColumns' => ['email'],
            'hiddenColumns' => ['internal'],
        ]));

        self::assertSame(2, $state->getPage());
        self::assertSame(50, $state->getPageSize());
        self::assertSame('alice', $state->getSearchQuery());
        self::assertSame(['status' => 'active'], $state->getFilters());
        self::assertSame(['email'], $state->getVisibleColumns());
        self::assertSame(['internal'], $state->getHiddenColumns());

        $advancedFilters = $state->getAdvancedFilters();
        self::assertIsArray($advancedFilters['conditions']);
        self::assertIsArray($advancedFilters['conditions'][0]);
        self::assertSame('email', $advancedFilters['conditions'][0]['field']);
    }

    public function test_it_creates_an_execution_request_from_canonical_state(): void
    {
        $state = DatatableState::create(
            page: 3,
            pageSize: 1000,
            searchQuery: 'alice',
            sortField: 'email',
            sortDirection: 'desc',
            filters: ['status' => 'active'],
            advancedFilters: [
                'logic' => 'and',
                'conditions' => [
                    ['field' => 'email', 'operator' => 'contains', 'value' => '@example.test'],
                ],
            ],
        );

        $request = $this->factory->createFromState($state, options: ['source' => 'url']);

        self::assertSame(3, $request->getPage());
        self::assertSame(DatatableRequestFactory::MAX_PAGE_SIZE, $request->getPageSize());
        self::assertSame('alice', $request->getSearchQuery());
        self::assertSame(SortDirection::Desc, $request->getSortDirection());
        self::assertSame(['status' => 'active'], $request->getFilters());
        self::assertTrue($request->hasAdvancedFilters());
        self::assertSame('url', $request->getOption('source'));
    }
}
