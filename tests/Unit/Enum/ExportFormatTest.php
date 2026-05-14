<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Enum\ExportFormat;

final class ExportFormatTest extends TestCase
{
    public function test_it_creates_export_format_from_valid_string(): void
    {
        self::assertSame(ExportFormat::Csv, ExportFormat::fromString('csv'));
        self::assertSame(ExportFormat::Xlsx, ExportFormat::fromString('xlsx'));
        self::assertSame(ExportFormat::Xlsx, ExportFormat::fromString(' XLSX '));
    }

    public function test_it_rejects_unknown_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported export format "pdf".');

        ExportFormat::fromString('pdf');
    }

    public function test_it_exposes_file_extension(): void
    {
        self::assertSame('csv', ExportFormat::Csv->getExtension());
        self::assertSame('xlsx', ExportFormat::Xlsx->getExtension());
    }

    public function test_it_exposes_content_type(): void
    {
        self::assertSame('text/csv; charset=UTF-8', ExportFormat::Csv->getContentType());
        self::assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ExportFormat::Xlsx->getContentType(),
        );
    }
}
