<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Factory;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Factory\AdvancedFilterExpressionFactory;
use Zhortein\DatatableBundle\Factory\DatatableRequestFactory;

final class DatatableRequestFactoryConfiguredDefaultsTest extends TestCase
{
    public function test_it_uses_configured_defaults(): void
    {
        $factory = new DatatableRequestFactory(
            advancedFilterExpressionFactory: new AdvancedFilterExpressionFactory(),
            defaultPage: 1,
            defaultPageSize: 50,
            maxPageSize: 200,
        );

        $datatableRequest = $factory->createFromRequest(new Request());

        self::assertSame(1, $datatableRequest->getPage());
        self::assertSame(50, $datatableRequest->getPageSize());
        self::assertSame(SortDirection::Asc, $datatableRequest->getSortDirection());
    }

    public function test_it_caps_page_size_with_configured_maximum(): void
    {
        $factory = new DatatableRequestFactory(
            advancedFilterExpressionFactory: new AdvancedFilterExpressionFactory(),
            defaultPage: 1,
            defaultPageSize: 50,
            maxPageSize: 100,
        );

        $datatableRequest = $factory->createFromRequest(new Request([
            'pageSize' => '500',
        ]));

        self::assertSame(100, $datatableRequest->getPageSize());
    }

    public function test_runtime_request_values_override_configured_defaults(): void
    {
        $factory = new DatatableRequestFactory(
            advancedFilterExpressionFactory: new AdvancedFilterExpressionFactory(),
            defaultPage: 1,
            defaultPageSize: 50,
            maxPageSize: 100,
        );

        $datatableRequest = $factory->createFromRequest(new Request([
            'page' => '2',
            'pageSize' => '25',
        ]));

        self::assertSame(2, $datatableRequest->getPage());
        self::assertSame(25, $datatableRequest->getPageSize());
    }

    public function test_it_rejects_invalid_constructor_defaults(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The default datatable page size must be greater than or equal to 1.');

        new DatatableRequestFactory(
            advancedFilterExpressionFactory: new AdvancedFilterExpressionFactory(),
            defaultPageSize: 0,
        );
    }
}
