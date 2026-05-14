<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\Response;
use Zhortein\DatatableBundle\Contract\ExportWriterInterface;
use Zhortein\DatatableBundle\Definition\ColumnDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\ExportFormat;
use Zhortein\DatatableBundle\Result\DatatableResult;

final readonly class XlsxExportWriter implements ExportWriterInterface
{
    public const string WRITER_NAME = 'xlsx';

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

            $columns = $this->getExportableColumns($request, $definition);

            $writer->addRow(Row::fromValues(array_map(
                static fn (ColumnDefinition $column): string => $column->getLabel() ?? $column->getName(),
                $columns,
            )));

            foreach ($result->getRows() as $row) {
                $writer->addRow(new Row(array_map(
                    static fn (mixed $value): Cell => Cell::fromValue($value),
                    $this->normalizeRow($columns, $row),
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
     * @return list<ColumnDefinition>
     */
    private function getExportableColumns(
        DatatableExportRequest $request,
        DatatableDefinition $definition,
    ): array {
        $datatableRequest = $request->getDatatableRequest();

        $visibleColumns = $datatableRequest?->getVisibleColumns() ?? [];
        $hiddenColumns = $datatableRequest?->getHiddenColumns() ?? [];

        return array_values(array_filter(
            $definition->getColumns(),
            static function (ColumnDefinition $column) use ($visibleColumns, $hiddenColumns): bool {
                if (!$column->isVisible()) {
                    return false;
                }

                if ([] !== $visibleColumns && !in_array($column->getName(), $visibleColumns, true)) {
                    return false;
                }

                return !in_array($column->getName(), $hiddenColumns, true);
            },
        ));
    }

    /**
     * @param list<ColumnDefinition> $columns
     * @param array<string, mixed>   $row
     *
     * @return list<\DateInterval|\DateTimeInterface|bool|float|int|string|null>
     */
    private function normalizeRow(array $columns, array $row): array
    {
        $values = [];

        foreach ($columns as $column) {
            $values[] = $this->normalizeValue($this->readColumnValue($row, $column));
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function readColumnValue(array $row, ColumnDefinition $column): mixed
    {
        foreach ($this->getColumnValueCandidateKeys($column->getName()) as $candidateKey) {
            if (array_key_exists($candidateKey, $row)) {
                return $row[$candidateKey];
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function getColumnValueCandidateKeys(string $columnName): array
    {
        $candidateKeys = [$columnName];

        if (str_contains($columnName, '.')) {
            $candidateKeys[] = str_replace('.', '_', $columnName);

            $parts = explode('.', $columnName);
            $lastPart = $parts[array_key_last($parts)];

            if ('' !== $lastPart) {
                $candidateKeys[] = $lastPart;
            }
        }

        return array_values(array_unique($candidateKeys));
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
