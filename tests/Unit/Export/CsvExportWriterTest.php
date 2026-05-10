<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Export;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\ExportFormat;
use Zhortein\DatatableBundle\Export\CsvExportWriter;
use Zhortein\DatatableBundle\Export\DatatableExportRequest;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Result\DatatableResult;

final class CsvExportWriterTest extends TestCase
{
    public function test_it_supports_csv_format(): void
    {
        $writer = new CsvExportWriter();

        self::assertTrue($writer->supports(ExportFormat::Csv));
    }

    public function test_it_respects_runtime_column_visibility(): void
    {
        $writer = new CsvExportWriter();

        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.id', label: 'Identifier', visible: false)
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.displayName', label: 'Display name')
            ->addColumn('e.enabled', label: 'Enabled')
        ;

        $datatableRequest = DatatableRequest::create(
            visibleColumns: ['e.email', 'e.displayName'],
            hiddenColumns: ['e.displayName'],
        );

        $exportRequest = new DatatableExportRequest(
            datatableName: 'users',
            datatableRequest: $datatableRequest,
        );

        $result = new DatatableResult(
            rows: [
                [
                    'e_id' => 1,
                    'e_email' => 'alice@example.test',
                    'e_displayName' => 'Alice',
                    'e_enabled' => true,
                ],
            ],
            totalItems: 1,
        );

        $response = $writer->write(
            request: $exportRequest,
            definition: $definition,
            result: $result,
        );

        $content = (string) $response->getContent();

        self::assertStringContainsString('Email', $content);
        self::assertStringContainsString('alice@example.test', $content);

        self::assertStringNotContainsString('Identifier', $content);
        self::assertStringNotContainsString('Display name', $content);
        self::assertStringNotContainsString('Enabled', $content);
        self::assertStringNotContainsString('Alice', $content);
    }

    public function test_it_writes_csv_response(): void
    {
        $writer = new CsvExportWriter();

        $response = $writer->write(
            request: new DatatableExportRequest('users'),
            definition: $this->createDefinition(),
            result: $this->createResult(),
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
        self::assertSame('attachment; filename="users.csv"', $response->headers->get('Content-Disposition'));

        $content = (string) $response->getContent();

        self::assertStringContainsString('Email,"Display name",Enabled,"Created at"', $content);
        self::assertStringContainsString('alice@example.test,Alice,1,2026-05-09T14:30:00+00:00', $content);
        self::assertStringContainsString('bob@example.test,"Bob, Jr.",0,', $content);
        self::assertStringNotContainsString('Identifier', $content);
        self::assertStringNotContainsString('e_id', $content);
    }

    public function test_it_reads_rows_using_full_or_normalized_column_names(): void
    {
        $writer = new CsvExportWriter();

        $definition = new DatatableDefinition('users');
        $definition
            ->addColumn('e.email', label: 'Email')
            ->addColumn('organization.name', label: 'Organization')
        ;

        $result = new DatatableResult(
            rows: [
                [
                    'email' => 'alice@example.test',
                    'organization_name' => 'Acme Corp',
                ],
            ],
            totalItems: 1,
        );

        $response = $writer->write(
            request: new DatatableExportRequest('users'),
            definition: $definition,
            result: $result,
        );

        $content = (string) $response->getContent();

        self::assertStringContainsString('Email,Organization', $content);
        self::assertStringContainsString('alice@example.test,"Acme Corp"', $content);
    }

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.id', label: 'Identifier', visible: false)
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.displayName', label: 'Display name')
            ->addColumn('e.enabled', label: 'Enabled')
            ->addColumn('e.createdAt', label: 'Created at')
        ;

        return $definition;
    }

    private function createResult(): DatatableResult
    {
        return new DatatableResult(
            rows: [
                [
                    'e_id' => 1,
                    'e_email' => 'alice@example.test',
                    'e_displayName' => 'Alice',
                    'e_enabled' => true,
                    'e_createdAt' => new \DateTimeImmutable('2026-05-09 14:30:00', new \DateTimeZone('UTC')),
                ],
                [
                    'e_id' => 2,
                    'e_email' => 'bob@example.test',
                    'e_displayName' => 'Bob, Jr.',
                    'e_enabled' => false,
                    'e_createdAt' => null,
                ],
            ],
            totalItems: 2,
        );
    }
}
