<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Export;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Enum\ExportFormat;
use Zhortein\DatatableBundle\Enum\ExportMode;
use Zhortein\DatatableBundle\Export\DatatableExportRequest;
use Zhortein\DatatableBundle\Request\DatatableRequest;

final class DatatableExportRequestTest extends TestCase
{
    public function test_it_uses_default_export_values(): void
    {
        $request = new DatatableExportRequest('users');

        self::assertSame('users', $request->getDatatableName());
        self::assertSame(ExportMode::Current, $request->getMode());
        self::assertSame('users.csv', $request->getFilename());
        self::assertNull($request->getDatatableRequest());
        self::assertTrue($request->shouldKeepPagination());
    }

    public function test_it_accepts_custom_values(): void
    {
        $datatableRequest = new DatatableRequest(page: 2, pageSize: 10);

        $request = new DatatableExportRequest(
            datatableName: 'users',
            format: ExportFormat::Csv,
            mode: ExportMode::Full,
            filename: 'custom-users.csv',
            datatableRequest: $datatableRequest,
        );

        self::assertSame('custom-users.csv', $request->getFilename());
        self::assertSame(ExportMode::Full, $request->getMode());
        self::assertSame($datatableRequest, $request->getDatatableRequest());
        self::assertFalse($request->shouldKeepPagination());
    }

    public function test_it_creates_request_from_string_values(): void
    {
        $request = DatatableExportRequest::create(
            datatableName: 'users',
            format: 'csv',
            mode: 'full',
        );

        self::assertSame(ExportMode::Full, $request->getMode());
    }

    public function test_it_sanitizes_default_filename(): void
    {
        $request = new DatatableExportRequest('admin users/list');

        self::assertSame('admin-users-list.csv', $request->getFilename());
    }

    public function test_it_rejects_empty_datatable_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The export datatable name cannot be empty.');

        new DatatableExportRequest(' ');
    }
}
