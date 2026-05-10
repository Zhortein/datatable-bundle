<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Provider;

use Zhortein\DatatableBundle\Contract\DataProviderInterface;
use Zhortein\DatatableBundle\Definition\ColumnDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Result\DatatableResult;

final readonly class ArrayDataProvider implements DataProviderInterface
{
    public const string PROVIDER_NAME = 'array';
    public const string OPTION_PROVIDER = 'provider';
    public const string OPTION_ROWS = 'rows';

    public function supports(DatatableDefinition $definition): bool
    {
        return self::PROVIDER_NAME === $definition->getOption(self::OPTION_PROVIDER)
            || is_array($definition->getOption(self::OPTION_ROWS));
    }

    public function getData(DatatableDefinition $definition, DatatableRequest $request): DatatableResult
    {
        $rows = $this->normalizeRows($definition->getOption(self::OPTION_ROWS, []));
        $totalItems = count($rows);

        $rows = $this->applySearch($rows, $definition, $request);
        $filteredItems = count($rows);

        $rows = $this->applySorting($rows, $definition, $request);
        if ($request->isPaginationEnabled()) {
            $rows = array_slice($rows, $request->getOffset(), $request->getPageSize());
        }

        return new DatatableResult(
            rows: $rows,
            page: $request->getPage(),
            pageSize: $request->getPageSize(),
            totalItems: $totalItems,
            filteredItems: $filteredItems,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeRows(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $normalizedRows = [];

        foreach ($rows as $row) {
            if (is_array($row)) {
                /** @var array<string, mixed> $row */
                $normalizedRows[] = $row;
            }
        }

        return $normalizedRows;
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array<string, mixed>>
     */
    private function applySearch(array $rows, DatatableDefinition $definition, DatatableRequest $request): array
    {
        if (!$request->hasSearchQuery()) {
            return $rows;
        }

        $searchQuery = mb_strtolower((string) $request->getSearchQuery());

        return array_values(array_filter(
            $rows,
            function (array $row) use ($definition, $searchQuery): bool {
                foreach ($this->getSearchableColumns($definition) as $column) {
                    $value = $this->readColumnValue($row, $column);

                    if (!is_scalar($value)) {
                        continue;
                    }

                    if (str_contains(mb_strtolower((string) $value), $searchQuery)) {
                        return true;
                    }
                }

                return false;
            },
        ));
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array<string, mixed>>
     */
    private function applySorting(array $rows, DatatableDefinition $definition, DatatableRequest $request): array
    {
        if (!$request->hasSort()) {
            return $rows;
        }

        $sortField = $request->getSortField();

        if (null === $sortField || !$this->isSortableColumn($definition, $sortField)) {
            return $rows;
        }

        usort($rows, function (array $leftRow, array $rightRow) use ($definition, $request, $sortField): int {
            $column = $definition->getColumns()[$sortField] ?? null;

            if (!$column instanceof ColumnDefinition) {
                return 0;
            }

            $leftValue = $this->readColumnValue($leftRow, $column);
            $rightValue = $this->readColumnValue($rightRow, $column);

            $comparison = $this->compareValues($leftValue, $rightValue);

            return SortDirection::Desc === $request->getSortDirection() ? -$comparison : $comparison;
        });

        return $rows;
    }

    /**
     * @return list<ColumnDefinition>
     */
    private function getSearchableColumns(DatatableDefinition $definition): array
    {
        return array_values(array_filter(
            $definition->getColumns(),
            static fn (ColumnDefinition $column): bool => $column->isSearchable(),
        ));
    }

    private function isSortableColumn(DatatableDefinition $definition, string $name): bool
    {
        $column = $definition->getColumns()[$name] ?? null;

        return $column instanceof ColumnDefinition && $column->isSortable();
    }

    /**
     * @param array<string, mixed> $row
     */
    private function readColumnValue(array $row, ColumnDefinition $column): mixed
    {
        $columnName = $column->getName();

        if (array_key_exists($columnName, $row)) {
            return $row[$columnName];
        }

        $normalizedName = $this->normalizeColumnName($columnName);

        return $row[$normalizedName] ?? null;
    }

    private function normalizeColumnName(string $columnName): string
    {
        if (!str_contains($columnName, '.')) {
            return $columnName;
        }

        $parts = explode('.', $columnName);
        $lastPart = $parts[array_key_last($parts)];

        return '' !== $lastPart ? $lastPart : $columnName;
    }

    private function compareValues(mixed $leftValue, mixed $rightValue): int
    {
        if ($leftValue === $rightValue) {
            return 0;
        }

        if (null === $leftValue) {
            return -1;
        }

        if (null === $rightValue) {
            return 1;
        }

        if (is_numeric($leftValue) && is_numeric($rightValue)) {
            return (float) $leftValue <=> (float) $rightValue;
        }

        if ($leftValue instanceof \DateTimeInterface && $rightValue instanceof \DateTimeInterface) {
            return $leftValue->getTimestamp() <=> $rightValue->getTimestamp();
        }

        if (is_scalar($leftValue) && is_scalar($rightValue)) {
            return strnatcasecmp((string) $leftValue, (string) $rightValue);
        }

        return strnatcasecmp(get_debug_type($leftValue), get_debug_type($rightValue));
    }
}
