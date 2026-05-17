<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Provider;

use Zhortein\DatatableBundle\Contract\DataProviderInterface;
use Zhortein\DatatableBundle\Definition\ColumnDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Definition\UserFilterDefinition;
use Zhortein\DatatableBundle\Enum\FilterType;
use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Filter\Expression\ArrayExpressionEvaluator;
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

        $rows = $this->applyUserFilters($rows, $definition, $request);
        $rows = $this->applyAdvancedFilters($rows, $request);
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
    private function applyUserFilters(array $rows, DatatableDefinition $definition, DatatableRequest $request): array
    {
        if (!$request->hasFilters()) {
            return $rows;
        }

        foreach ($definition->getFilters() as $filter) {
            if (!$request->hasFilter($filter->getName())) {
                continue;
            }

            $filterValue = $request->getFilter($filter->getName());

            $rows = array_values(array_filter(
                $rows,
                fn (array $row): bool => $this->rowMatchesFilter($row, $filter, $filterValue),
            ));
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function rowMatchesFilter(array $row, UserFilterDefinition $filter, mixed $filterValue): bool
    {
        $rowValue = $this->readFieldValue($row, $filter->getField());

        return match ($filter->getType()) {
            FilterType::Text => $this->matchesTextFilter($rowValue, $filterValue),
            FilterType::Choice => $this->matchesChoiceFilter($rowValue, $filterValue),
            FilterType::Boolean => $this->matchesBooleanFilter($rowValue, $filterValue),
            FilterType::Date => $this->matchesDateFilter($rowValue, $filterValue),
            FilterType::DateRange => $this->matchesRangeFilter($rowValue, $filterValue),
            FilterType::Number => $this->matchesNumberFilter($rowValue, $filterValue),
            FilterType::NumberRange => $this->matchesRangeFilter($rowValue, $filterValue),
        };
    }

    private function matchesTextFilter(mixed $rowValue, mixed $filterValue): bool
    {
        if (!is_scalar($rowValue) || !is_scalar($filterValue)) {
            return false;
        }

        return str_contains(
            mb_strtolower((string) $rowValue),
            mb_strtolower((string) $filterValue),
        );
    }

    private function matchesChoiceFilter(mixed $rowValue, mixed $filterValue): bool
    {
        if (is_array($filterValue)) {
            return in_array($rowValue, $filterValue, true);
        }

        return $rowValue === $filterValue || (is_scalar($rowValue) && is_scalar($filterValue) && (string) $rowValue === (string) $filterValue);
    }

    private function matchesBooleanFilter(mixed $rowValue, mixed $filterValue): bool
    {
        $normalizedFilterValue = $this->normalizeBooleanValue($filterValue);

        if (null === $normalizedFilterValue) {
            return false;
        }

        return $this->normalizeBooleanValue($rowValue) === $normalizedFilterValue;
    }

    private function matchesDateFilter(mixed $rowValue, mixed $filterValue): bool
    {
        if (!$rowValue instanceof \DateTimeInterface && !is_scalar($rowValue)) {
            return false;
        }

        if (!is_scalar($filterValue)) {
            return false;
        }

        $rowDate = $this->normalizeDateString($rowValue);
        $filterDate = $this->normalizeDateString((string) $filterValue);

        return null !== $rowDate && $rowDate === $filterDate;
    }

    private function matchesNumberFilter(mixed $rowValue, mixed $filterValue): bool
    {
        if (!is_numeric($rowValue) || !is_numeric($filterValue)) {
            return false;
        }

        return (float) $rowValue === (float) $filterValue;
    }

    private function matchesRangeFilter(mixed $rowValue, mixed $filterValue): bool
    {
        if (!is_array($filterValue)) {
            return false;
        }

        $from = $filterValue['from'] ?? null;
        $to = $filterValue['to'] ?? null;

        if (null !== $from && !$this->isValueGreaterThanOrEqual($rowValue, $from)) {
            return false;
        }

        if (null !== $to && !$this->isValueLessThanOrEqual($rowValue, $to)) {
            return false;
        }

        return true;
    }

    private function isValueGreaterThanOrEqual(mixed $rowValue, mixed $minimum): bool
    {
        if (is_numeric($rowValue) && is_numeric($minimum)) {
            return (float) $rowValue >= (float) $minimum;
        }

        $rowDate = $this->normalizeDateString($rowValue);
        $minimumDate = $this->normalizeDateString($minimum);

        return null !== $rowDate && null !== $minimumDate && $rowDate >= $minimumDate;
    }

    private function isValueLessThanOrEqual(mixed $rowValue, mixed $maximum): bool
    {
        if (is_numeric($rowValue) && is_numeric($maximum)) {
            return (float) $rowValue <= (float) $maximum;
        }

        $rowDate = $this->normalizeDateString($rowValue);
        $maximumDate = $this->normalizeDateString($maximum);

        return null !== $rowDate && null !== $maximumDate && $rowDate <= $maximumDate;
    }

    private function normalizeBooleanValue(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return match ($value) {
                1 => true,
                0 => false,
                default => null,
            };
        }

        if (!is_string($value)) {
            return null;
        }

        return match (mb_strtolower(trim($value))) {
            '1', 'true', 'yes', 'on' => true,
            '0', 'false', 'no', 'off' => false,
            default => null,
        };
    }

    private function normalizeDateString(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        if ('' === $value) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        if (!$date instanceof \DateTimeImmutable) {
            return null;
        }

        return $date->format('Y-m-d');
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
        return $this->readFieldValue($row, $column->getName());
    }

    /**
     * @param array<string, mixed> $row
     */
    private function readFieldValue(array $row, string $field): mixed
    {
        foreach ($this->getFieldCandidateKeys($field) as $candidateKey) {
            if (array_key_exists($candidateKey, $row)) {
                return $row[$candidateKey];
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function getFieldCandidateKeys(string $field): array
    {
        $candidateKeys = [$field];

        if (str_contains($field, '.')) {
            $candidateKeys[] = str_replace('.', '_', $field);

            $parts = explode('.', $field);
            $lastPart = $parts[array_key_last($parts)];

            if ('' !== $lastPart) {
                $candidateKeys[] = $lastPart;
            }
        }

        return array_values(array_unique($candidateKeys));
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

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array<string, mixed>>
     */
    private function applyAdvancedFilters(array $rows, DatatableRequest $request): array
    {
        if (!$request->hasAdvancedFilters()) {
            return $rows;
        }

        $expression = $request->getAdvancedFilterExpression();

        if (null === $expression) {
            return $rows;
        }

        $evaluator = new ArrayExpressionEvaluator();

        return array_values(array_filter(
            $rows,
            fn (array $row): bool => $evaluator->evaluate($expression, $row),
        ));
    }
}
