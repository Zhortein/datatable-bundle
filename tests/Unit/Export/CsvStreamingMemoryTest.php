<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Export;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Zhortein\DatatableBundle\Contract\ExportCancellationInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Export\CsvExportWriter;
use Zhortein\DatatableBundle\Export\DatatableExportRequest;
use Zhortein\DatatableBundle\Export\ExportRow;
use Zhortein\DatatableBundle\Export\ExportStreamContext;

final class CsvStreamingMemoryTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_large_synthetic_export_keeps_memory_bounded(): void
    {
        $definition = new DatatableDefinition('large-export');
        $definition
            ->addColumn('id', label: 'Identifier')
            ->addColumn('payload', label: 'Payload')
        ;
        $rowCount = 25000;
        $baselineMemory = memory_get_usage(true);
        $maximumMemory = $baselineMemory;
        $rows = (static function () use ($rowCount, &$maximumMemory): \Generator {
            for ($index = 0; $index < $rowCount; ++$index) {
                $maximumMemory = max($maximumMemory, memory_get_usage(true));

                yield new ExportRow([
                    'id' => $index,
                    'payload' => str_repeat('x', 2048),
                ]);
            }
        })();
        $context = new ExportStreamContext(
            batchSize: 500,
            expectedRowCount: $rowCount,
            cancellation: new class implements ExportCancellationInterface {
                public function isCancelled(): bool
                {
                    return false;
                }
            },
        );
        $response = new CsvExportWriter()->writeStream(
            new DatatableExportRequest('large-export'),
            $definition,
            $rows,
            $context,
        );

        self::assertInstanceOf(StreamedResponse::class, $response);

        ob_start(static fn (string $buffer): string => '', 4096);

        try {
            $response->sendContent();
        } finally {
            ob_end_clean();
        }

        self::assertLessThan(
            16 * 1024 * 1024,
            $maximumMemory - $baselineMemory,
            'Streaming a synthetic dataset must not retain all exported rows in memory.',
        );
    }
}
