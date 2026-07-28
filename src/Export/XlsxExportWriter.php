<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Zhortein\DatatableBundle\Cell\CellContextFactory;
use Zhortein\DatatableBundle\Contract\EnumPresentationResolverInterface;
use Zhortein\DatatableBundle\Contract\ExportWriterInterface;
use Zhortein\DatatableBundle\Contract\StreamingExportWriterInterface;
use Zhortein\DatatableBundle\Definition\ColumnDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\ExportFormat;
use Zhortein\DatatableBundle\EnumPresentation\DefaultEnumPresentationResolver;
use Zhortein\DatatableBundle\Result\DatatableResult;

final readonly class XlsxExportWriter implements ExportWriterInterface, StreamingExportWriterInterface
{
    public const string WRITER_NAME = 'xlsx';

    private CellContextFactory $cellContextFactory;

    private EnumPresentationResolverInterface $enumPresentationResolver;

    public function __construct(
        private ExportableColumnResolver $columnResolver = new ExportableColumnResolver(),
        ?CellContextFactory $cellContextFactory = null,
        private ExportColumnLabelResolver $columnLabelResolver = new ExportColumnLabelResolver(),
        ?EnumPresentationResolverInterface $enumPresentationResolver = null,
    ) {
        $this->cellContextFactory = $cellContextFactory ?? new CellContextFactory();
        $this->enumPresentationResolver = $enumPresentationResolver ?? new DefaultEnumPresentationResolver();
    }

    public function supports(ExportFormat $format): bool
    {
        return ExportFormat::Xlsx === $format;
    }

    public function write(
        DatatableExportRequest $request,
        DatatableDefinition $definition,
        DatatableResult $result,
    ): Response {
        if (!class_exists(Writer::class)) {
            throw new \RuntimeException('XLSX export requires the optional "openspout/openspout" package.');
        }

        $temporaryFile = tempnam(sys_get_temp_dir(), 'zhortein_datatable_xlsx_');

        if (false === $temporaryFile) {
            throw new \RuntimeException('Unable to create temporary XLSX file.');
        }

        try {
            $writer = new Writer();
            $writer->openToFile($temporaryFile);

            $columns = $this->columnResolver->resolve($request, $definition);

            $writer->addRow(Row::fromValues(array_map(
                fn (ColumnDefinition $column): string => $this->columnLabelResolver->resolve($definition, $column),
                $columns,
            )));

            foreach ($result->getRows() as $rowIndex => $row) {
                $writer->addRow(new Row(array_map(
                    static fn (mixed $value): Cell => Cell::fromValue($value),
                    $this->normalizeRow(
                        definition: $definition,
                        columns: $columns,
                        row: $row,
                        source: $result->getSource($rowIndex),
                    ),
                )));
            }

            $writer->close();

            $content = file_get_contents($temporaryFile);

            if (false === $content) {
                throw new \RuntimeException('Unable to read generated XLSX file.');
            }

            return new Response(
                content: $content,
                status: Response::HTTP_OK,
                headers: [
                    'Content-Type' => ExportFormat::Xlsx->getContentType(),
                    'Content-Disposition' => sprintf('attachment; filename="%s"', $request->getFilename()),
                    'Content-Length' => (string) strlen($content),
                ],
            );
        } finally {
            if (is_file($temporaryFile)) {
                @unlink($temporaryFile);
            }
        }
    }

    /**
     * @param iterable<ExportRow> $rows
     */
    public function writeStream(
        DatatableExportRequest $request,
        DatatableDefinition $definition,
        iterable $rows,
        ExportStreamContext $context,
    ): Response {
        if (!class_exists(Writer::class)) {
            throw new \RuntimeException('XLSX export requires the optional "openspout/openspout" package.');
        }

        return new StreamedResponse(
            function () use ($request, $definition, $rows, $context): void {
                if ($context->isCancelled()) {
                    return;
                }

                $temporaryFile = tempnam(sys_get_temp_dir(), 'zhortein_datatable_xlsx_');

                if (false === $temporaryFile) {
                    throw new \RuntimeException('Unable to create temporary XLSX file.');
                }

                $writer = null;

                try {
                    $writer = new Writer();
                    $writer->openToFile($temporaryFile);

                    $columns = $this->columnResolver->resolve($request, $definition);

                    $writer->addRow(Row::fromValues(array_map(
                        fn (ColumnDefinition $column): string => $this->columnLabelResolver->resolve($definition, $column),
                        $columns,
                    )));

                    foreach ($rows as $row) {
                        if ($context->isCancelled()) {
                            break;
                        }

                        $writer->addRow(new Row(array_map(
                            static fn (mixed $value): Cell => Cell::fromValue($value),
                            $this->normalizeRow(
                                definition: $definition,
                                columns: $columns,
                                row: $row->getValues(),
                                source: $row->getSource(),
                            ),
                        )));
                    }

                    $writer->close();
                    $writer = null;

                    if ($context->isCancelled()) {
                        return;
                    }

                    $handle = fopen($temporaryFile, 'rb');

                    if (false === $handle) {
                        throw new \RuntimeException('Unable to open generated XLSX file.');
                    }

                    try {
                        while (!feof($handle) && !$context->isCancelled()) {
                            $chunk = fread($handle, 8192);

                            if (false === $chunk) {
                                throw new \RuntimeException('Unable to read generated XLSX file.');
                            }

                            echo $chunk;
                        }
                    } finally {
                        fclose($handle);
                    }
                } finally {
                    if ($writer instanceof Writer) {
                        try {
                            $writer->close();
                        } catch (\Throwable) {
                            // Preserve the exception that interrupted generation.
                        }
                    }

                    if (is_file($temporaryFile)) {
                        @unlink($temporaryFile);
                    }
                }
            },
            Response::HTTP_OK,
            [
                'Content-Type' => ExportFormat::Xlsx->getContentType(),
                'Content-Disposition' => sprintf('attachment; filename="%s"', $request->getFilename()),
            ],
        );
    }

    /**
     * @param list<ColumnDefinition> $columns
     * @param array<string, mixed>   $row
     *
     * @return list<\DateInterval|\DateTimeInterface|bool|float|int|string|null>
     */
    private function normalizeRow(
        DatatableDefinition $definition,
        array $columns,
        array $row,
        mixed $source,
    ): array {
        $values = [];

        foreach ($columns as $column) {
            $value = $this->cellContextFactory
                ->create($definition, $column, $row, $source)
                ->getValue();

            if ('enum' === $column->getType() || null !== $column->getEnumClass()) {
                $presentation = $this->enumPresentationResolver->resolve(
                    value: $value,
                    enumClass: $column->getEnumClass(),
                    presentations: $column->getEnumPresentations(),
                    translationDomain: $definition->getTranslationDomain(),
                );

                if (null !== $presentation) {
                    $values[] = $presentation->getLabel();

                    continue;
                }
            }

            $values[] = $this->normalizeValue($value);
        }

        return $values;
    }

    private function normalizeValue(mixed $value): \DateInterval|\DateTimeInterface|bool|float|int|string|null
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value) || is_string($value)) {
            return $value;
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        if (is_array($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }

        return get_debug_type($value);
    }
}
