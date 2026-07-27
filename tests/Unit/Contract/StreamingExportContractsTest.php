<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Contract;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Zhortein\DatatableBundle\Contract\ExportCancellationInterface;
use Zhortein\DatatableBundle\Contract\StreamingDataProviderInterface;
use Zhortein\DatatableBundle\Contract\StreamingExportWriterInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Export\DatatableExportRequest;
use Zhortein\DatatableBundle\Export\ExportRow;
use Zhortein\DatatableBundle\Export\ExportStreamContext;
use Zhortein\DatatableBundle\Request\DatatableRequest;

final class StreamingExportContractsTest extends TestCase
{
    public function test_provider_and_writer_share_the_same_stream_context(): void
    {
        $context = new ExportStreamContext(
            batchSize: 10,
            expectedRowCount: 1,
            cancellation: new class implements ExportCancellationInterface {
                public function isCancelled(): bool
                {
                    return false;
                }
            },
        );
        $provider = new class implements StreamingDataProviderInterface {
            public ?ExportStreamContext $context = null;

            public function streamExportRows(
                DatatableDefinition $definition,
                DatatableRequest $request,
                ExportStreamContext $context,
            ): iterable {
                $this->context = $context;

                yield new ExportRow(['id' => 1]);
            }
        };
        $writer = new class implements StreamingExportWriterInterface {
            public ?ExportStreamContext $context = null;

            public function writeStream(
                DatatableExportRequest $request,
                DatatableDefinition $definition,
                iterable $rows,
                ExportStreamContext $context,
            ): Response {
                $this->context = $context;

                return new Response((string) iterator_count(
                    (static function () use ($rows): \Generator {
                        yield from $rows;
                    })(),
                ));
            }
        };

        $rows = $provider->streamExportRows(
            new DatatableDefinition('users'),
            new DatatableRequest(),
            $context,
        );
        $response = $writer->writeStream(
            new DatatableExportRequest('users'),
            new DatatableDefinition('users'),
            $rows,
            $context,
        );

        self::assertSame('1', $response->getContent());
        self::assertSame($context, $provider->context);
        self::assertSame($context, $writer->context);
    }
}
