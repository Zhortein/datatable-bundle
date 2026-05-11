<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Factory;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Factory\DatatableRequestFactory;

final class DatatableRequestFactoryTest extends TestCase
{
    public function test_it_creates_request_from_query_parameters(): void
    {
        $factory = new DatatableRequestFactory();

        $datatableRequest = $factory->createFromRequest(new Request([
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
        $factory = new DatatableRequestFactory();

        $datatableRequest = $factory->createFromRequest(new Request(
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
        $factory = new DatatableRequestFactory();

        $datatableRequest = $factory->createFromRequest(new Request());

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
        $factory = new DatatableRequestFactory();

        $datatableRequest = $factory->createFromRequest(new Request([
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
        $factory = new DatatableRequestFactory();

        $datatableRequest = $factory->createFromRequest(new Request([
            'pageSize' => '999999',
        ]));

        self::assertSame(DatatableRequestFactory::MAX_PAGE_SIZE, $datatableRequest->getPageSize());
    }

    public function test_it_normalizes_empty_strings(): void
    {
        $factory = new DatatableRequestFactory();

        $datatableRequest = $factory->createFromRequest(new Request([
            'search' => '   ',
            'sortField' => '',
        ]));

        self::assertNull($datatableRequest->getSearchQuery());
        self::assertNull($datatableRequest->getSortField());
    }
}
