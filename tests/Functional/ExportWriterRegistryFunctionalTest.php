<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Zhortein\DatatableBundle\Export\CsvExportWriter;
use Zhortein\DatatableBundle\Export\ExportWriterRegistry;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class ExportWriterRegistryFunctionalTest extends FunctionalTestCase
{
    public function test_csv_export_writer_is_registered_in_container(): void
    {
        self::bootKernel();

        $registry = self::getContainer()->get('test.'.ExportWriterRegistry::class);

        self::assertInstanceOf(ExportWriterRegistry::class, $registry);
        self::assertTrue($registry->has(CsvExportWriter::WRITER_NAME));
        self::assertInstanceOf(CsvExportWriter::class, $registry->get(CsvExportWriter::WRITER_NAME));
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }
}
