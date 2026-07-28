<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Factory;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Factory\AdvancedFilterExpressionFactory;
use Zhortein\DatatableBundle\Factory\DatatableRequestFactory;
use Zhortein\DatatableBundle\Sorting\SortCriterion;
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
                'http_cursor' => ' next-page ',
                'disablePagination' => true,
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
        self::assertSame(['http_cursor' => 'next-page'], $datatableRequest->getOptions());
        self::assertTrue($datatableRequest->isPaginationEnabled());
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

    public function test_it_reads_bounded_deduplicated_sort_criteria(): void
    {
        $sorts = [
            ['field' => ' e.displayName ', 'direction' => 'asc'],
            ['field' => 'e.email', 'direction' => 'DESC'],
            ['field' => 'e.displayName', 'direction' => 'desc'],
            ['field' => '', 'direction' => 'asc'],
            ['field' => 'invalid-direction', 'direction' => 'sideways'],
            'invalid',
        ];

        for ($index = 0; $index < 10; ++$index) {
            $sorts[] = ['field' => sprintf('field_%d', $index), 'direction' => 'asc'];
        }

        $request = $this->factory->createFromRequest(new Request([
            'sortField' => 'legacy',
            'sortDirection' => 'desc',
            'sorts' => $sorts,
        ]));

        self::assertCount(SortCriterion::MAX_CRITERIA, $request->getSorts());
        self::assertSame([
            ['field' => 'e.displayName', 'direction' => 'asc'],
            ['field' => 'e.email', 'direction' => 'desc'],
            ['field' => 'field_0', 'direction' => 'asc'],
            ['field' => 'field_1', 'direction' => 'asc'],
            ['field' => 'field_2', 'direction' => 'asc'],
            ['field' => 'field_3', 'direction' => 'asc'],
            ['field' => 'field_4', 'direction' => 'asc'],
            ['field' => 'field_5', 'direction' => 'asc'],
        ], array_map(
            static fn (SortCriterion $criterion): array => $criterion->toArray(),
            $request->getSorts(),
        ));
        self::assertSame('e.displayName', $request->getSortField());
        self::assertSame(SortDirection::Asc, $request->getSortDirection());
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

    public function test_it_caps_page_and_search_length(): void
    {
        $datatableRequest = $this->factory->createFromRequest(new Request([
            'page' => (string) PHP_INT_MAX,
            'search' => str_repeat('a', DatatableRequestFactory::MAX_SEARCH_LENGTH + 1),
        ]));

        self::assertSame(DatatableRequestFactory::MAX_PAGE, $datatableRequest->getPage());
        self::assertSame(
            str_repeat('a', DatatableRequestFactory::MAX_SEARCH_LENGTH),
            $datatableRequest->getSearchQuery(),
        );
        self::assertSame(
            (DatatableRequestFactory::MAX_PAGE - 1) * DatatableRequestFactory::DEFAULT_PAGE_SIZE,
            $datatableRequest->getOffset(),
        );
    }

    public function test_it_drops_oversized_filter_payloads(): void
    {
        $datatableRequest = $this->factory->createFromRequest(new Request([
            'filters' => [
                'oversized_string' => str_repeat('a', DatatableRequestFactory::MAX_FILTER_VALUE_LENGTH + 1),
                'oversized_list' => range(1, DatatableRequestFactory::MAX_FILTER_VALUES + 1),
                'nested' => [['unexpected']],
                'allowed' => 'yes',
            ],
        ]));

        self::assertSame(['allowed' => 'yes'], $datatableRequest->getFilters());
    }

    public function test_it_accepts_a_bounded_top_level_http_cursor(): void
    {
        $datatableRequest = $this->factory->createFromRequest(new Request([
            'httpCursor' => 'next-page',
        ]));

        self::assertSame('next-page', $datatableRequest->getOption('http_cursor'));
    }

    public function test_it_keeps_only_server_declared_filters_sorts_and_columns(): void
    {
        $definition = new DatatableDefinition('users');
        $definition
            ->addColumn('e.email')
            ->addColumn('e.createdAt', sortable: false)
            ->addFilter('email', 'e.email')
        ;

        $datatableRequest = $this->factory->createFromRequest(new Request([
            'filters' => [
                'email' => 'alice@example.test',
                'arbitrary_dql' => 'injected',
            ],
            'sorts' => [
                ['field' => 'e.email', 'direction' => 'asc'],
                ['field' => 'e.createdAt', 'direction' => 'desc'],
                ['field' => 'e.unknown', 'direction' => 'desc'],
            ],
            'visibleColumns' => ['e.email', 'e.unknown'],
            'hiddenColumns' => ['e.createdAt', 'e.unknown'],
        ]), $definition);

        self::assertSame(['email' => 'alice@example.test'], $datatableRequest->getFilters());
        self::assertSame(
            [['field' => 'e.email', 'direction' => 'asc']],
            array_map(
                static fn (SortCriterion $criterion): array => $criterion->toArray(),
                $datatableRequest->getSorts(),
            ),
        );
        self::assertSame(['e.email'], $datatableRequest->getVisibleColumns());
        self::assertSame(['e.createdAt'], $datatableRequest->getHiddenColumns());
    }

    public function test_it_drops_excessively_nested_advanced_filter_transport(): void
    {
        $payload = ['field' => 'name', 'operator' => 'eq', 'value' => 'Alice'];

        for ($depth = 0; $depth <= DatatableRequestFactory::MAX_TRANSPORT_DEPTH; ++$depth) {
            $payload = ['logic' => 'AND', 'conditions' => [$payload]];
        }

        $datatableRequest = $this->factory->createFromRequest(new Request([
            'advancedFilters' => $payload,
        ]));

        self::assertFalse($datatableRequest->hasAdvancedFilters());
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
            sorts: [
                SortCriterion::create('email', SortDirection::Desc),
                SortCriterion::create('createdAt'),
            ],
        );

        $request = $this->factory->createFromState($state, options: ['source' => 'url']);

        self::assertSame(3, $request->getPage());
        self::assertSame(DatatableRequestFactory::MAX_PAGE_SIZE, $request->getPageSize());
        self::assertSame('alice', $request->getSearchQuery());
        self::assertSame(SortDirection::Desc, $request->getSortDirection());
        self::assertSame($state->getSorts(), $request->getSorts());
        self::assertSame(['status' => 'active'], $request->getFilters());
        self::assertTrue($request->hasAdvancedFilters());
        self::assertSame('url', $request->getOption('source'));
    }
}
