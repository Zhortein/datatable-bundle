<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Export;

use OpenSpout\Reader\XLSX\Reader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;
use Zhortein\DatatableBundle\Cell\CellContext;
use Zhortein\DatatableBundle\Cell\CellContextFactory;
use Zhortein\DatatableBundle\Cell\CellValueResolverRegistry;
use Zhortein\DatatableBundle\Contract\CellValueResolverInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\ExportFormat;
use Zhortein\DatatableBundle\Export\DatatableExportRequest;
use Zhortein\DatatableBundle\Export\ExportColumnLabelResolver;
use Zhortein\DatatableBundle\Export\ExportWriterRegistry;
use Zhortein\DatatableBundle\Export\XlsxExportWriter;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Result\DatatableResult;

final class XlsxExportWriterTest extends TestCase
{
    public function test_it_supports_xlsx_format_only(): void
    {
        $writer = new XlsxExportWriter();

        self::assertTrue($writer->supports(ExportFormat::Xlsx));
        self::assertFalse($writer->supports(ExportFormat::Csv));
    }

    public function test_it_writes_xlsx_response(): void
    {
        $writer = new XlsxExportWriter();

        $definition = new DatatableDefinition('users');
        $definition
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.displayName', label: 'Display name')
            ->addColumn('e.enabled', label: 'Enabled', type: 'boolean')
        ;

        $response = $writer->write(
            request: new DatatableExportRequest('users', format: ExportFormat::Xlsx),
            definition: $definition,
            result: new DatatableResult(
                rows: [
                    [
                        'e_email' => 'alice@example.test',
                        'e_displayName' => 'Alice',
                        'e_enabled' => true,
                    ],
                ],
                totalItems: 1,
            ),
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type'),
        );
        self::assertSame(
            'attachment; filename="users.xlsx"',
            $response->headers->get('Content-Disposition'),
        );

        $rows = $this->readXlsxRows((string) $response->getContent());

        self::assertSame([
            ['Email', 'Display name', 'Enabled'],
            ['alice@example.test', 'Alice', true],
        ], $rows);
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
        $writer = new XlsxExportWriter(
            columnLabelResolver: new ExportColumnLabelResolver($translator),
        );

        $response = $writer->write(
            request: new DatatableExportRequest('users', format: ExportFormat::Xlsx),
            definition: $definition,
            result: new DatatableResult(
                rows: [['e_email' => 'alice@example.test']],
                totalItems: 1,
            ),
        );

        self::assertSame([
            ['Adresse e-mail'],
            ['alice@example.test'],
        ], $this->readXlsxRows((string) $response->getContent()));
    }

    public function test_it_respects_runtime_column_visibility(): void
    {
        $writer = new XlsxExportWriter();

        $definition = new DatatableDefinition('users');
        $definition
            ->addColumn('e.id', label: 'Identifier', visible: false)
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.displayName', label: 'Display name')
            ->addColumn('e.enabled', label: 'Enabled')
        ;

        $request = new DatatableExportRequest(
            datatableName: 'users',
            format: ExportFormat::Xlsx,
            datatableRequest: DatatableRequest::create(
                visibleColumns: ['e.email', 'e.displayName'],
                hiddenColumns: ['e.displayName'],
            ),
        );

        $response = $writer->write(
            request: $request,
            definition: $definition,
            result: new DatatableResult(
                rows: [
                    [
                        'e_id' => 1,
                        'e_email' => 'alice@example.test',
                        'e_displayName' => 'Alice',
                        'e_enabled' => true,
                    ],
                ],
                totalItems: 1,
            ),
        );

        self::assertSame([
            ['Email'],
            ['alice@example.test'],
        ], $this->readXlsxRows((string) $response->getContent()));
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
            format: ExportFormat::Xlsx,
            datatableRequest: DatatableRequest::create(hiddenColumns: ['e.email']),
        );

        $response = new XlsxExportWriter()->write(
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

        self::assertSame([
            ['Identifier'],
            [1],
        ], $this->readXlsxRows((string) $response->getContent()));
    }

    public function test_it_exports_computed_values(): void
    {
        $resolver = new class implements CellValueResolverInterface {
            public function getName(): string
            {
                return 'summary';
            }

            public function resolve(CellContext $context): mixed
            {
                $email = $context->getRow()['e_email'] ?? null;

                if (!is_string($email)) {
                    throw new \UnexpectedValueException('Expected the email to be a string.');
                }

                return strtoupper($email);
            }
        };
        $definition = new DatatableDefinition('users');
        $definition
            ->addColumn('e.email', visible: false)
            ->addComputedColumn('summary', valueResolver: 'summary', label: 'Summary')
        ;
        $writer = new XlsxExportWriter(
            cellContextFactory: new CellContextFactory(new CellValueResolverRegistry([$resolver])),
        );

        $response = $writer->write(
            request: new DatatableExportRequest('users', format: ExportFormat::Xlsx),
            definition: $definition,
            result: new DatatableResult(
                rows: [['e_email' => 'alice@example.test']],
                totalItems: 1,
            ),
        );

        self::assertSame([
            ['Summary'],
            ['ALICE@EXAMPLE.TEST'],
        ], $this->readXlsxRows((string) $response->getContent()));
    }

    /**
     * @return list<list<mixed>>
     */
    private function readXlsxRows(string $content): array
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'zhortein_datatable_xlsx_test_');

        self::assertIsString($temporaryFile);

        file_put_contents($temporaryFile, $content);

        $reader = new Reader();
        $reader->open($temporaryFile);

        $rows = [];
        $firstSheetRead = false;

        foreach ($reader->getSheetIterator() as $sheet) {
            if ($firstSheetRead) {
                break;
            }

            foreach ($sheet->getRowIterator() as $row) {
                $rowValues = [];

                foreach ($row->toArray() as $cellValue) {
                    $rowValues[] = $cellValue;
                }

                $rows[] = $rowValues;
            }

            $firstSheetRead = true;
        }

        $reader->close();
        @unlink($temporaryFile);

        return $rows;
    }

    public function test_it_resolves_xlsx_writer_when_registered(): void
    {
        $registry = new ExportWriterRegistry([
            XlsxExportWriter::WRITER_NAME => new XlsxExportWriter(),
        ]);

        self::assertInstanceOf(
            XlsxExportWriter::class,
            $registry->resolve(ExportFormat::Xlsx),
        );
    }
}
