<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Export;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Enum\ExportFormat;
use Zhortein\DatatableBundle\Enum\ExportMode;
use Zhortein\DatatableBundle\Export\DatatableExportRequest;

final class DatatableExportRequestFormatTest extends TestCase
{
    public function test_it_uses_csv_by_default(): void
    {
        $request = new DatatableExportRequest('users');

        self::assertSame(ExportFormat::Csv, $request->getFormat());
        self::assertSame('users.csv', $request->getFilename());
    }

    public function test_it_supports_xlsx_format(): void
    {
        $request = new DatatableExportRequest(
            datatableName: 'users',
            format: ExportFormat::Xlsx,
            mode: ExportMode::Full,
        );

        self::assertSame(ExportFormat::Xlsx, $request->getFormat());
        self::assertSame(ExportMode::Full, $request->getMode());
        self::assertSame('users.xlsx', $request->getFilename());
    }

    public function test_custom_filename_keeps_extension_when_matching_format(): void
    {
        $request = new DatatableExportRequest(
            datatableName: 'users',
            format: ExportFormat::Xlsx,
            filename: 'custom-users.xlsx',
        );

        self::assertSame('custom-users.xlsx', $request->getFilename());
    }

    public function test_custom_filename_gets_format_extension_when_missing(): void
    {
        $request = new DatatableExportRequest(
            datatableName: 'users',
            format: ExportFormat::Xlsx,
            filename: 'custom-users',
        );

        self::assertSame('custom-users.xlsx', $request->getFilename());
    }
}
