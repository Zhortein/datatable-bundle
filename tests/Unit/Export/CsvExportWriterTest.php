<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Export;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;
use Zhortein\DatatableBundle\Cell\CellContext;
use Zhortein\DatatableBundle\Cell\CellContextFactory;
use Zhortein\DatatableBundle\Cell\CellValueResolverRegistry;
use Zhortein\DatatableBundle\Contract\CellValueResolverInterface;
use Zhortein\DatatableBundle\Contract\ExportCancellationInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\ExportFormat;
use Zhortein\DatatableBundle\EnumPresentation\DefaultEnumPresentationResolver;
use Zhortein\DatatableBundle\EnumPresentation\EnumPresentation;
use Zhortein\DatatableBundle\Export\CsvExportWriter;
use Zhortein\DatatableBundle\Export\DatatableExportRequest;
use Zhortein\DatatableBundle\Export\ExportColumnLabelResolver;
use Zhortein\DatatableBundle\Export\ExportRow;
use Zhortein\DatatableBundle\Export\ExportStreamContext;
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

    public function test_it_respects_explicit_column_export_policies(): void
    {
        $definition = new DatatableDefinition('users');
        $definition
            ->addColumn('e.id', label: 'Identifier', visible: false, exportable: true)
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.token', label: 'Token', exportable: false)
        ;

        $request = new DatatableExportRequest(
            datatableName: 'users',
            datatableRequest: DatatableRequest::create(hiddenColumns: ['e.email']),
        );

        $response = new CsvExportWriter()->write(
            request: $request,
            definition: $definition,
            result: new DatatableResult(
                rows: [[
                    'e_id' => 1,
                    'e_email' => 'alice@example.test',
                    'e_token' => 'secret',
                ]],
                totalItems: 1,
            ),
        );

        self::assertSame("Identifier\n1\n", $response->getContent());
    }

    public function test_it_can_use_semicolon_delimiter(): void
    {
        $writer = new CsvExportWriter(delimiter: ';');

        $definition = new DatatableDefinition('users');
        $definition
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.displayName', label: 'Display name')
        ;

        $result = new DatatableResult(
            rows: [
                [
                    'e_email' => 'alice@example.test',
                    'e_displayName' => 'Alice',
                ],
            ],
            totalItems: 1,
        );

        $response = $writer->write(
            request: new DatatableExportRequest('users'),
            definition: $definition,
            result: $result,
        );

        self::assertStringContainsString("Email;\"Display name\"\n", (string) $response->getContent());
        self::assertStringContainsString("alice@example.test;Alice\n", (string) $response->getContent());
    }

    public function test_it_can_prefix_csv_with_utf8_bom(): void
    {
        $writer = new CsvExportWriter(withBom: true);

        $definition = new DatatableDefinition('users');
        $definition->addColumn('e.email', label: 'Email');

        $result = new DatatableResult(
            rows: [
                ['e_email' => 'alice@example.test'],
            ],
            totalItems: 1,
        );

        $response = $writer->write(
            request: new DatatableExportRequest('users'),
            definition: $definition,
            result: $result,
        );

        self::assertStringStartsWith("\xEF\xBB\xBF", (string) $response->getContent());
    }

    public function test_it_neutralizes_spreadsheet_formulas_from_string_cells(): void
    {
        $definition = new DatatableDefinition('users');
        $definition
            ->addColumn('e.formula', label: 'Formula')
            ->addColumn('e.negativeNumber', label: 'Negative number')
        ;

        $response = new CsvExportWriter()->write(
            request: new DatatableExportRequest('users'),
            definition: $definition,
            result: new DatatableResult(
                rows: [[
                    'e_formula' => " \t=HYPERLINK(\"https://example.test\")",
                    'e_negativeNumber' => -42,
                ]],
                totalItems: 1,
            ),
        );

        $content = (string) $response->getContent();

        self::assertStringContainsString("' \t=HYPERLINK", $content);
        self::assertStringContainsString(',-42', $content);
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

    public function test_it_translates_column_headers_in_the_definition_domain(): void
    {
        $translator = new Translator('fr');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource(
            'array',
            ['users.columns.email' => 'Adresse e-mail'],
            'fr',
            'users',
        );
        $definition = new DatatableDefinition('users');
        $definition
            ->setTranslationDomain('users')
            ->addColumn('e.email', label: 'users.columns.email')
        ;
        $writer = new CsvExportWriter(
            columnLabelResolver: new ExportColumnLabelResolver($translator),
        );

        $response = $writer->write(
            request: new DatatableExportRequest('users'),
            definition: $definition,
            result: new DatatableResult(
                rows: [['e_email' => 'alice@example.test']],
                totalItems: 1,
            ),
        );

        self::assertSame("\"Adresse e-mail\"\nalice@example.test\n", $response->getContent());
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

    public function test_it_exports_computed_values_with_the_server_side_source(): void
    {
        $resolver = new class implements CellValueResolverInterface {
            public function getName(): string
            {
                return 'summary';
            }

            public function resolve(CellContext $context): mixed
            {
                $source = $context->getSource();
                $sourceLabel = is_array($source) && is_string($source['label'] ?? null)
                    ? $source['label']
                    : 'no-source';
                $email = $context->getRow()['e_email'] ?? null;

                if (!is_string($email)) {
                    throw new \UnexpectedValueException('Expected the email to be a string.');
                }

                return sprintf(
                    '%s / %s',
                    $email,
                    $sourceLabel,
                );
            }
        };
        $definition = new DatatableDefinition('users');
        $definition
            ->addColumn('e.email', visible: false)
            ->addComputedColumn('summary', valueResolver: 'summary', label: 'Summary')
        ;
        $source = ['label' => 'server-source'];
        $writer = new CsvExportWriter(
            cellContextFactory: new CellContextFactory(new CellValueResolverRegistry([$resolver])),
        );

        $response = $writer->write(
            request: new DatatableExportRequest('users'),
            definition: $definition,
            result: new DatatableResult(
                rows: [['e_email' => 'alice@example.test']],
                totalItems: 1,
                sources: [$source],
            ),
        );

        self::assertSame("Summary\n\"alice@example.test / server-source\"\n", $response->getContent());
    }

    public function test_it_exports_the_translated_enum_label_without_presentation_markup(): void
    {
        $translator = new Translator('fr');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', ['status.active' => 'Actif'], 'fr', 'users');
        $definition = new DatatableDefinition('users');
        $definition
            ->setTranslationDomain('users')
            ->addColumn(
                name: 'status',
                label: 'Status',
                type: 'enum',
                enumClass: ExportStatus::class,
                enumPresentations: [
                    ExportStatus::Active->value => new EnumPresentation(
                        label: 'status.active',
                        badgeVariant: 'success',
                        icon: 'bi bi-check-circle',
                    ),
                ],
            )
        ;
        $writer = new CsvExportWriter(
            enumPresentationResolver: new DefaultEnumPresentationResolver($translator),
        );

        $response = $writer->write(
            request: new DatatableExportRequest('users'),
            definition: $definition,
            result: new DatatableResult(
                rows: [['status' => ExportStatus::Active]],
                totalItems: 1,
            ),
        );

        self::assertSame("Status\nActif\n", $response->getContent());
        self::assertStringNotContainsString('badge', $response->getContent());
        self::assertStringNotContainsString('bi-check-circle', $response->getContent());
    }

    public function test_it_streams_rows_without_materializing_a_datatable_result(): void
    {
        $definition = new DatatableDefinition('users');
        $definition
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.displayName', label: 'Display name')
        ;
        $consumption = new CsvStreamConsumptionCounter();
        $rows = (static function () use ($consumption): \Generator {
            ++$consumption->rows;
            yield new ExportRow([
                'e_email' => 'alice@example.test',
                'e_displayName' => 'Alice',
            ]);

            ++$consumption->rows;
            yield new ExportRow([
                'e_email' => 'bob@example.test',
                'e_displayName' => 'Bob',
            ]);
        })();
        $response = new CsvExportWriter()->writeStream(
            request: new DatatableExportRequest('users'),
            definition: $definition,
            rows: $rows,
            context: $this->createStreamContext(2),
        );

        self::assertInstanceOf(StreamedResponse::class, $response);
        self::assertSame(0, $consumption->rows);
        self::assertSame(
            "Email,\"Display name\"\nalice@example.test,Alice\nbob@example.test,Bob\n",
            $this->captureStreamedResponse($response),
        );
        self::assertSame(2, $consumption->rows);
    }

    public function test_it_stops_streaming_cleanly_when_cancelled(): void
    {
        $definition = new DatatableDefinition('users');
        $definition->addColumn('email', label: 'Email');
        $response = new CsvExportWriter()->writeStream(
            request: new DatatableExportRequest('users'),
            definition: $definition,
            rows: [
                new ExportRow(['email' => 'alice@example.test']),
                new ExportRow(['email' => 'bob@example.test']),
            ],
            context: new ExportStreamContext(
                batchSize: 10,
                expectedRowCount: 2,
                cancellation: new CancelAfterChecks(2),
            ),
        );

        self::assertSame(
            "Email\nalice@example.test\n",
            $this->captureStreamedResponse($response),
        );
    }

    public function test_late_provider_errors_propagate_from_the_stream_callback(): void
    {
        $definition = new DatatableDefinition('users');
        $definition->addColumn('email', label: 'Email');
        $rows = (static function (): \Generator {
            yield new ExportRow(['email' => 'alice@example.test']);

            throw new \RuntimeException('Provider failed after streaming started.');
        })();
        $response = new CsvExportWriter()->writeStream(
            request: new DatatableExportRequest('users'),
            definition: $definition,
            rows: $rows,
            context: $this->createStreamContext(2),
        );

        ob_start();

        try {
            $response->sendContent();
            self::fail('The late provider exception should propagate.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Provider failed after streaming started.', $exception->getMessage());
        } finally {
            ob_end_clean();
        }
    }

    public function test_it_writes_a_bounded_background_artifact(): void
    {
        $definition = new DatatableDefinition('users');
        $definition->addColumn('email', label: 'Email');
        $artifact = new CsvExportWriter()->writeArtifact(
            request: new DatatableExportRequest('users'),
            definition: $definition,
            rows: [
                new ExportRow(['email' => 'alice@example.test']),
                new ExportRow(['email' => 'bob@example.test']),
            ],
            context: $this->createStreamContext(2),
        );

        try {
            self::assertSame('users.csv', $artifact->getFilename());
            self::assertSame('text/csv; charset=UTF-8', $artifact->getContentType());
            self::assertSame(
                "Email\nalice@example.test\nbob@example.test\n",
                file_get_contents($artifact->getPath()),
            );
        } finally {
            $artifact->delete();
        }
    }

    private function createStreamContext(int $expectedRowCount): ExportStreamContext
    {
        return new ExportStreamContext(
            batchSize: 100,
            expectedRowCount: $expectedRowCount,
            cancellation: new CancelAfterChecks(PHP_INT_MAX),
        );
    }

    private function captureStreamedResponse(Response $response): string
    {
        self::assertInstanceOf(StreamedResponse::class, $response);

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        self::assertIsString($content);

        return $content;
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

enum ExportStatus: string
{
    case Active = 'active';
}

final class CancelAfterChecks implements ExportCancellationInterface
{
    private int $checks = 0;

    public function __construct(
        private readonly int $allowedChecks,
    ) {
    }

    public function isCancelled(): bool
    {
        return ++$this->checks > $this->allowedChecks;
    }
}

final class CsvStreamConsumptionCounter
{
    public int $rows = 0;
}
