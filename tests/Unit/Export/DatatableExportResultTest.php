<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Export;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Enum\ExportFormat;
use Zhortein\DatatableBundle\Export\DatatableExportResult;

final class DatatableExportResultTest extends TestCase
{
    public function test_it_stores_export_result_metadata(): void
    {
        $result = new DatatableExportResult(
            content: "email\nalice@example.test\n",
            format: ExportFormat::Csv,
            filename: 'users.csv',
        );

        self::assertSame("email\nalice@example.test\n", $result->getContent());
        self::assertSame('users.csv', $result->getFilename());
        self::assertSame('text/csv; charset=UTF-8', $result->getContentType());
    }

    public function test_it_rejects_empty_filename(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The export result filename cannot be empty.');

        new DatatableExportResult(
            content: '',
            format: ExportFormat::Csv,
            filename: '',
        );
    }
}
