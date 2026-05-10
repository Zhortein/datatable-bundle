<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export;

use Symfony\Component\HttpFoundation\Response;
use Zhortein\DatatableBundle\Contract\ExportWriterInterface;
use Zhortein\DatatableBundle\Definition\ColumnDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\ExportFormat;
use Zhortein\DatatableBundle\Result\DatatableResult;

final readonly class CsvExportWriter implements ExportWriterInterface
{
    public const string WRITER_NAME = 'csv';

    public function supports(ExportFormat $format): bool
    {
        return match ($format) {
            ExportFormat::Csv => true,
        };
    }

    public function write(
        DatatableExportRequest $request,
        DatatableDefinition $definition,
        DatatableResult $result,
    ): Response {
        $content = $this->createCsvContent($request, $definition, $result);

        return new Response(
            content: $content,
            status: Response::HTTP_OK,
            headers: [
                'Content-Type' => ExportFormat::Csv->getContentType(),
                'Content-Disposition' => sprintf('attachment; filename="%s"', $request->getFilename()),
            ],
        );
    }

    private function createCsvContent(
        DatatableExportRequest $request,
        DatatableDefinition $definition,
        DatatableResult $result,
    ): string {
        $handle = fopen('php://temp', 'r+');

        if (false === $handle) {
            throw new \RuntimeException('Unable to open temporary CSV stream.');
        }

        $columns = $this->getExportableColumns($request, $definition);

        $this->writeRow($handle, array_map(
            static fn (ColumnDefinition $column): string => $column->getLabel() ?? $column->getName(),
            $columns,
        ));

        foreach ($result->getRows() as $row) {
            $this->writeRow($handle, $this->normalizeRow($columns, $row));
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        if (false === $content) {
            throw new \RuntimeException('Unable to read temporary CSV stream.');
        }

        return $content;
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
     * @return list<string>
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

    private function normalizeValue(mixed $value): string
    {
        if (null === $value) {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    /**
     * @param resource     $handle
     * @param list<string> $values
     */
    private function writeRow(mixed $handle, array $values): void
    {
        if (false === fputcsv($handle, $values, ',', '"', '\\')) {
            throw new \RuntimeException('Unable to write CSV row.');
        }
    }
}
