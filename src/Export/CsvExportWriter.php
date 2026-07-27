<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export;

use Symfony\Component\HttpFoundation\Response;
use Zhortein\DatatableBundle\Cell\CellContextFactory;
use Zhortein\DatatableBundle\Contract\ExportWriterInterface;
use Zhortein\DatatableBundle\Contract\EnumPresentationResolverInterface;
use Zhortein\DatatableBundle\Definition\ColumnDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\ExportFormat;
use Zhortein\DatatableBundle\EnumPresentation\DefaultEnumPresentationResolver;
use Zhortein\DatatableBundle\Result\DatatableResult;

final readonly class CsvExportWriter implements ExportWriterInterface
{
    public const string WRITER_NAME = 'csv';

    private CellContextFactory $cellContextFactory;

    private EnumPresentationResolverInterface $enumPresentationResolver;

    public function __construct(
        private string $delimiter = ',',
        private string $enclosure = '"',
        private string $escape = '\\',
        private bool $withBom = false,
        private ExportableColumnResolver $columnResolver = new ExportableColumnResolver(),
        ?CellContextFactory $cellContextFactory = null,
        private ExportColumnLabelResolver $columnLabelResolver = new ExportColumnLabelResolver(),
        ?EnumPresentationResolverInterface $enumPresentationResolver = null,
    ) {
        if (1 !== strlen($this->delimiter)) {
            throw new \InvalidArgumentException('The CSV delimiter must be exactly one character.');
        }

        if (1 !== strlen($this->enclosure)) {
            throw new \InvalidArgumentException('The CSV enclosure must be exactly one character.');
        }

        if ('' !== $this->escape && 1 !== strlen($this->escape)) {
            throw new \InvalidArgumentException('The CSV escape character must be empty or exactly one character.');
        }

        $this->cellContextFactory = $cellContextFactory ?? new CellContextFactory();
        $this->enumPresentationResolver = $enumPresentationResolver ?? new DefaultEnumPresentationResolver();
    }

    public function supports(ExportFormat $format): bool
    {
        return ExportFormat::Csv === $format;
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
        $handle = fopen('php://temp', 'rb+');

        if (false === $handle) {
            throw new \RuntimeException('Unable to open temporary CSV stream.');
        }

        if ($this->withBom) {
            fwrite($handle, "\xEF\xBB\xBF");
        }

        $columns = $this->columnResolver->resolve($request, $definition);

        $this->writeRow($handle, array_map(
            fn (ColumnDefinition $column): string => $this->columnLabelResolver->resolve($definition, $column),
            $columns,
        ));

        foreach ($result->getRows() as $rowIndex => $row) {
            $this->writeRow($handle, $this->normalizeRow(
                definition: $definition,
                columns: $columns,
                row: $row,
                source: $result->getSource($rowIndex),
            ));
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
     * @param list<ColumnDefinition> $columns
     * @param array<string, mixed>   $row
     *
     * @return list<string>
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
        if (false === fputcsv($handle, $values, $this->delimiter, $this->enclosure, $this->escape)) {
            throw new \RuntimeException('Unable to write CSV row.');
        }
    }
}
