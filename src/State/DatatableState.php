<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\State;

use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Sorting\SortCriterion;

/**
 * Canonical, shareable state of one datatable instance.
 *
 * Provider-only options and server-side context deliberately do not belong here.
 */
final readonly class DatatableState
{
    public const int VERSION = 1;

    /**
     * @param array<string, mixed> $filters
     * @param array<string, mixed> $advancedFilters
     * @param list<string>         $visibleColumns
     * @param list<string>         $hiddenColumns
     * @param list<SortCriterion>  $sorts
     */
    public function __construct(
        private int $page = 1,
        private int $pageSize = 25,
        private ?string $searchQuery = null,
        private ?string $sortField = null,
        private SortDirection $sortDirection = SortDirection::Asc,
        private array $filters = [],
        private array $advancedFilters = [],
        private array $visibleColumns = [],
        private array $hiddenColumns = [],
        private array $sorts = [],
    ) {
        if ($this->page < 1) {
            throw new \InvalidArgumentException('The datatable state page must be greater than or equal to 1.');
        }

        if ($this->pageSize < 1) {
            throw new \InvalidArgumentException('The datatable state page size must be greater than or equal to 1.');
        }

        SortCriterion::normalizeList($this->sorts);
    }

    /**
     * @param array<string, mixed> $filters
     * @param array<string, mixed> $advancedFilters
     * @param list<string>         $visibleColumns
     * @param list<string>         $hiddenColumns
     * @param list<SortCriterion>  $sorts
     */
    public static function create(
        int $page = 1,
        int $pageSize = 25,
        ?string $searchQuery = null,
        ?string $sortField = null,
        SortDirection|string $sortDirection = SortDirection::Asc,
        array $filters = [],
        array $advancedFilters = [],
        array $visibleColumns = [],
        array $hiddenColumns = [],
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
            filters: self::normalizeMap($filters),
            advancedFilters: self::normalizeTransportMap($advancedFilters),
            visibleColumns: self::normalizeColumnList($visibleColumns),
            hiddenColumns: self::normalizeColumnList($hiddenColumns),
            sorts: $sorts,
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

    public function getSearchQuery(): ?string
    {
        return $this->searchQuery;
    }

    public function getSortField(): ?string
    {
        return ($this->getSorts()[0] ?? null)?->getField() ?? $this->sortField;
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

    /**
     * @return array<string, mixed>
     */
    public function getAdvancedFilters(): array
    {
        return $this->advancedFilters;
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

    /**
     * @return array{
     *     version: int,
     *     page: int,
     *     pageSize: int,
     *     search: string|null,
     *     sortField: string|null,
     *     sortDirection: string,
     *     sorts: list<array{field: string, direction: string}>,
     *     filters: array<string, mixed>,
     *     advancedFilters: array<string, mixed>,
     *     visibleColumns: list<string>,
     *     hiddenColumns: list<string>
     * }
     */
    public function toArray(): array
    {
        return [
            'version' => self::VERSION,
            'page' => $this->page,
            'pageSize' => $this->pageSize,
            'search' => $this->searchQuery,
            'sortField' => $this->getSortField(),
            'sortDirection' => $this->getSortDirection()->value,
            'sorts' => array_map(
                static fn (SortCriterion $criterion): array => $criterion->toArray(),
                $this->getSorts(),
            ),
            'filters' => $this->filters,
            'advancedFilters' => $this->advancedFilters,
            'visibleColumns' => $this->visibleColumns,
            'hiddenColumns' => $this->hiddenColumns,
        ];
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
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private static function normalizeMap(array $values): array
    {
        $normalizedValues = [];

        foreach ($values as $name => $value) {
            $name = trim($name);

            if ('' === $name) {
                continue;
            }

            $normalizedValue = self::normalizeValue($value);

            if (null === $normalizedValue) {
                continue;
            }

            $normalizedValues[$name] = $normalizedValue;
        }

        return $normalizedValues;
    }

    private static function normalizeValue(mixed $value): mixed
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

            foreach ($value as $name => $item) {
                $normalizedItem = self::normalizeValue($item);

                if (null === $normalizedItem) {
                    continue;
                }

                if (is_string($name)) {
                    $normalizedValues[$name] = $normalizedItem;
                } else {
                    $normalizedValues[] = $normalizedItem;
                }
            }

            return [] === $normalizedValues ? null : $normalizedValues;
        }

        return is_scalar($value) ? $value : null;
    }

    /**
     * Advanced expressions keep their existing wire values, including empty
     * strings and nulls. The expression factory remains authoritative.
     *
     * @param array<array-key, mixed> $values
     *
     * @return array<string, mixed>
     */
    private static function normalizeTransportMap(array $values): array
    {
        $normalizedValues = [];

        foreach ($values as $name => $value) {
            if (!is_string($name) || '' === trim($name)) {
                continue;
            }

            $normalizedValues[trim($name)] = self::normalizeTransportValue($value);
        }

        return $normalizedValues;
    }

    private static function normalizeTransportValue(mixed $value): mixed
    {
        if (!is_array($value)) {
            return null === $value || is_scalar($value) ? $value : null;
        }

        $normalizedValues = [];

        foreach ($value as $name => $item) {
            $normalizedItem = self::normalizeTransportValue($item);

            if (is_string($name)) {
                $normalizedValues[$name] = $normalizedItem;
            } else {
                $normalizedValues[] = $normalizedItem;
            }
        }

        return $normalizedValues;
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
