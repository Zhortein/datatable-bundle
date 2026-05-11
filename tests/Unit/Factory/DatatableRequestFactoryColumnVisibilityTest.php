<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Factory;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Zhortein\DatatableBundle\Factory\DatatableRequestFactory;

final class DatatableRequestFactoryColumnVisibilityTest extends TestCase
{
    public function test_it_reads_column_visibility_from_query_parameters(): void
    {
        $factory = new DatatableRequestFactory();

        $datatableRequest = $factory->createFromRequest(new Request([
            'visibleColumns' => ['e.email', 'e.displayName'],
            'hiddenColumns' => ['e.createdAt'],
        ]));

        self::assertSame(['e.email', 'e.displayName'], $datatableRequest->getVisibleColumns());
        self::assertSame(['e.createdAt'], $datatableRequest->getHiddenColumns());
        self::assertTrue($datatableRequest->hasColumnVisibilityState());
    }

    public function test_it_reads_single_column_visibility_values(): void
    {
        $factory = new DatatableRequestFactory();

        $datatableRequest = $factory->createFromRequest(new Request([
            'visibleColumns' => 'e.email',
            'hiddenColumns' => 'e.createdAt',
        ]));

        self::assertSame(['e.email'], $datatableRequest->getVisibleColumns());
        self::assertSame(['e.createdAt'], $datatableRequest->getHiddenColumns());
    }

    public function test_request_payload_overrides_query_column_visibility(): void
    {
        $factory = new DatatableRequestFactory();

        $datatableRequest = $factory->createFromRequest(new Request(
            query: [
                'visibleColumns' => ['e.email'],
                'hiddenColumns' => ['e.createdAt'],
            ],
            request: [
                'visibleColumns' => ['e.displayName'],
                'hiddenColumns' => ['e.email'],
            ],
        ));

        self::assertSame(['e.displayName'], $datatableRequest->getVisibleColumns());
        self::assertSame(['e.email'], $datatableRequest->getHiddenColumns());
    }

    public function test_it_ignores_invalid_column_visibility_values(): void
    {
        $factory = new DatatableRequestFactory();

        $datatableRequest = $factory->createFromRequest(new Request([
            'visibleColumns' => ['e.email', '', new \stdClass()],
            'hiddenColumns' => new \stdClass(),
        ]));

        self::assertSame(['e.email'], $datatableRequest->getVisibleColumns());
        self::assertSame([], $datatableRequest->getHiddenColumns());
    }
}
