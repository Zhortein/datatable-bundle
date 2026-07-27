<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Request;

use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Filter\Expression\AdvancedFilterExpression;
use Zhortein\DatatableBundle\Sorting\SortCriterion;

final readonly class DatatableRequest
{
    /**
     * @param array<string, mixed> $filters
     * @param list<string>         $visibleColumns
     * @param list<string>         $hiddenColumns
     * @param array<string, mixed> $options
     * @param list<SortCriterion>  $sorts
     */
    public function __construct(
        private int $page = 1,
        private int $pageSize = 25,
        private ?string $searchQuery = null,
        private ?string $sortField = null,
        private SortDirection $sortDirection = SortDirection::Asc,
        private array $filters = [],
        private array $visibleColumns = [],
        private array $hiddenColumns = [],
        private array $options = [],
        private ?AdvancedFilterExpression $advancedFilterExpression = null,
        private array $sorts = [],
    ) {
        if ($this->page < 1) {
            throw new \InvalidArgumentException('The datatable page must be greater than or equal to 1.');
        }

        if ($this->pageSize < 1) {
            throw new \InvalidArgumentException('The datatable page size must be greater than or equal to 1.');
        }

        SortCriterion::normalizeList($this->sorts);
    }

    /**
     * @param array<string, mixed> $filters
     * @param list<string>         $visibleColumns
     * @param list<string>         $hiddenColumns
     * @param array<string, mixed> $options
     * @param list<SortCriterion>  $sorts
     */
    public static function create(
        int $page = 1,
        int $pageSize = 25,
        ?string $searchQuery = null,
        ?string $sortField = null,
        SortDirection|string $sortDirection = SortDirection::Asc,
        array $filters = [],
        array $visibleColumns = [],
        array $hiddenColumns = [],
        array $options = [],
        ?AdvancedFilterExpression $advancedFilterExpression = null,
        array $sorts = [],
    ): self {
        $sorts = SortCriterion::normalizeList($sorts);

        if ([] !== $sorts) {
            $sortField = $sorts[0]->getField();
            $sortDirection = $sorts[0]->getDirection();
        }

        return new self(
            page: $page,
            pageSize: $pageSize,
            searchQuery: self::normalizeNullableString($searchQuery),
            sortField: self::normalizeNullableString($sortField),
            sortDirection: is_string($sortDirection) ? SortDirection::fromString($sortDirection) : $sortDirection,
            filters: self::normalizeFilters($filters),
            visibleColumns: self::normalizeColumnList($visibleColumns),
            hiddenColumns: self::normalizeColumnList($hiddenColumns),
            options: $options,
            advancedFilterExpression: $advancedFilterExpression,
            sorts: $sorts,
        );
    }

    public function withoutPagination(): self
    {
        return new self(
            page: 1,
            pageSize: $this->pageSize,
            searchQuery: $this->searchQuery,
            sortField: $this->sortField,
            sortDirection: $this->sortDirection,
            filters: $this->filters,
            visibleColumns: $this->visibleColumns,
            hiddenColumns: $this->hiddenColumns,
            options: array_replace($this->options, [
                'disablePagination' => true,
            ]),
            advancedFilterExpression: $this->advancedFilterExpression,
            sorts: $this->sorts,
        );
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->pageSize;
    }

    public function isPaginationEnabled(): bool
    {
        return true !== $this->getOption('disablePagination', false);
    }

    public function getSearchQuery(): ?string
    {
        return $this->searchQuery;
    }

    public function hasSearchQuery(): bool
    {
        return null !== $this->searchQuery;
    }

    public function getSortField(): ?string
    {
        return ($this->getSorts()[0] ?? null)?->getField() ?? $this->sortField;
    }

    public function hasSort(): bool
    {
        return [] !== $this->getSorts();
    }

    public function getSortDirection(): SortDirection
    {
        return ($this->getSorts()[0] ?? null)?->getDirection() ?? $this->sortDirection;
    }

    /**
     * @return list<SortCriterion>
     */
    public function getSorts(): array
    {
        $sorts = SortCriterion::normalizeList($this->sorts);

        if ([] !== $sorts) {
            return $sorts;
        }

        if (null === $this->sortField || '' === trim($this->sortField)) {
            return [];
        }

        return [new SortCriterion($this->sortField, $this->sortDirection)];
    }

    /**
     * @return array<string, mixed>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function hasFilters(): bool
    {
        return [] !== $this->filters;
    }

    public function hasFilter(string $name): bool
    {
        return array_key_exists($name, $this->filters);
    }

    public function getFilter(string $name, mixed $default = null): mixed
    {
        return $this->filters[$name] ?? $default;
    }

    public function getAdvancedFilterExpression(): ?AdvancedFilterExpression
    {
        return $this->advancedFilterExpression;
    }

    public function hasAdvancedFilters(): bool
    {
        return null !== $this->advancedFilterExpression;
    }

    /**
     * @return list<string>
     */
    public function getVisibleColumns(): array
    {
        return $this->visibleColumns;
    }

    /**
     * @return list<string>
     */
    public function getHiddenColumns(): array
    {
        return $this->hiddenColumns;
    }

    public function hasColumnVisibilityState(): bool
    {
        return [] !== $this->visibleColumns || [] !== $this->hiddenColumns;
    }

    /**
     * @return array{
     *      visibleColumns: list<string>,
     *      hiddenColumns: list<string>,
     *      sortField: string|null,
     *      sortDirection: string,
     *      sorts: list<array{field: string, direction: string}>
     *  }
     */
    public function getColumnVisibilityOptions(): array
    {
        return [
            'visibleColumns' => $this->visibleColumns,
            'hiddenColumns' => $this->hiddenColumns,
            'sortField' => $this->getSortField(),
            'sortDirection' => $this->getSortDirection()->value,
            'sorts' => array_map(
                static fn (SortCriterion $criterion): array => $criterion->toArray(),
                $this->getSorts(),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOption(string $name, mixed $default = null): mixed
    {
        return $this->options[$name] ?? $default;
    }

    private static function normalizeNullableString(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $value = trim($value);

        return '' === $value ? null : $value;
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private static function normalizeFilters(array $filters): array
    {
        $normalizedFilters = [];

        foreach ($filters as $name => $value) {
            if ('' === trim($name)) {
                continue;
            }

            $normalizedValue = self::normalizeFilterValue($value);

            if (null === $normalizedValue) {
                continue;
            }

            $normalizedFilters[$name] = $normalizedValue;
        }

        return $normalizedFilters;
    }

    private static function normalizeFilterValue(mixed $value): mixed
    {
        if (null === $value) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);

            return '' === $value ? null : $value;
        }

        if (is_array($value)) {
            $normalizedValues = [];

            foreach ($value as $itemKey => $itemValue) {
                $normalizedItemValue = self::normalizeFilterValue($itemValue);

                if (null === $normalizedItemValue) {
                    continue;
                }

                if (is_string($itemKey)) {
                    $normalizedValues[$itemKey] = $normalizedItemValue;
                } else {
                    $normalizedValues[] = $normalizedItemValue;
                }
            }

            return [] === $normalizedValues ? null : $normalizedValues;
        }

        if (is_scalar($value)) {
            return $value;
        }

        return null;
    }

    /**
     * @param list<string> $columns
     *
     * @return list<string>
     */
    private static function normalizeColumnList(array $columns): array
    {
        $normalizedColumns = [];

        foreach ($columns as $column) {
            $column = trim($column);

            if ('' === $column) {
                continue;
            }

            $normalizedColumns[] = $column;
        }

        return array_values(array_unique($normalizedColumns));
    }
}
