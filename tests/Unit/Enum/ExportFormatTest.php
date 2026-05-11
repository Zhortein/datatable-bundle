<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Enum\ExportFormat;

final class ExportFormatTest extends TestCase
{
    public function test_it_rejects_invalid_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid export format "xlsx". Supported formats: csv.');

        ExportFormat::fromString('xlsx');
    }

    public function test_it_exposes_content_type_and_extension(): void
    {
        self::assertSame('text/csv; charset=UTF-8', ExportFormat::Csv->getContentType());
        self::assertSame('csv', ExportFormat::Csv->getFileExtension());
    }
}
