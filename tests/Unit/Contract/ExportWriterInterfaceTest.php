<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Contract;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Zhortein\DatatableBundle\Contract\ExportWriterInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\ExportFormat;
use Zhortein\DatatableBundle\Export\DatatableExportRequest;
use Zhortein\DatatableBundle\Result\DatatableResult;

final class ExportWriterInterfaceTest extends TestCase
{
    public function test_export_writer_contract_can_be_implemented(): void
    {
        $writer = new class implements ExportWriterInterface {
            public function supports(ExportFormat $format): bool
            {
                return true;
            }

            public function write(
                DatatableExportRequest $request,
                DatatableDefinition $definition,
                DatatableResult $result,
            ): Response {
                return new Response('content');
            }
        };

        self::assertTrue($writer->supports(ExportFormat::Csv));
        self::assertSame('content', $writer->write(
            new DatatableExportRequest('users'),
            new DatatableDefinition('users'),
            new DatatableResult(),
        )->getContent());
    }
}
