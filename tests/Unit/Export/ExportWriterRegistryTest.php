<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Export;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Zhortein\DatatableBundle\Contract\ExportWriterInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\ExportFormat;
use Zhortein\DatatableBundle\Exception\ExportWriterNotFoundException;
use Zhortein\DatatableBundle\Export\DatatableExportRequest;
use Zhortein\DatatableBundle\Export\ExportWriterRegistry;
use Zhortein\DatatableBundle\Result\DatatableResult;

final class ExportWriterRegistryTest extends TestCase
{
    public function test_it_resolves_registered_writer_by_name(): void
    {
        $writer = new TestExportWriter(true);

        $registry = new ExportWriterRegistry([
            'csv' => $writer,
        ]);

        self::assertTrue($registry->has('csv'));
        self::assertSame($writer, $registry->get('csv'));
        self::assertSame(['csv'], $registry->getNames());
    }

    public function test_it_throws_when_named_writer_is_missing(): void
    {
        $registry = new ExportWriterRegistry();

        $this->expectException(ExportWriterNotFoundException::class);
        $this->expectExceptionMessage('The export writer "missing" is not registered.');

        $registry->get('missing');
    }

    public function test_it_resolves_first_supporting_writer(): void
    {
        $unsupportedWriter = new TestExportWriter(false);
        $supportedWriter = new TestExportWriter(true);

        $registry = new ExportWriterRegistry([
            'unsupported' => $unsupportedWriter,
            'supported' => $supportedWriter,
        ]);

        self::assertSame($supportedWriter, $registry->resolve(ExportFormat::Csv));
    }

    public function test_it_throws_when_no_writer_supports_format(): void
    {
        $registry = new ExportWriterRegistry([
            'unsupported' => new TestExportWriter(false),
        ]);

        $this->expectException(ExportWriterNotFoundException::class);
        $this->expectExceptionMessage('No export writer supports format "csv".');

        $registry->resolve(ExportFormat::Csv);
    }

    public function test_it_rejects_empty_writer_name(): void
    {
        $this->expectException(ExportWriterNotFoundException::class);
        $this->expectExceptionMessage('An export writer cannot be registered with an empty name.');

        new ExportWriterRegistry([
            '' => new TestExportWriter(true),
        ]);
    }
}

final readonly class TestExportWriter implements ExportWriterInterface
{
    public function __construct(
        private bool $supports,
    ) {
    }

    public function supports(ExportFormat $format): bool
    {
        return $this->supports;
    }

    public function write(
        DatatableExportRequest $request,
        DatatableDefinition $definition,
        DatatableResult $result,
    ): Response {
        return new Response('export');
    }
}
