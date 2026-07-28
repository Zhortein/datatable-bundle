<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Preference;

use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Sorting\SortCriterion;

final readonly class DatatablePreference
{
    /**
     * @param list<string>         $visibleColumns
     * @param list<string>         $hiddenColumns
     * @param list<SortCriterion>  $sorts
     * @param array<string, mixed> $filters
     */
    public function __construct(
        private ?int $pageSize = null,
        private ?string $sortField = null,
        private ?SortDirection $sortDirection = null,
        private array $visibleColumns = [],
        private array $hiddenColumns = [],
        private string $filterLayout = 'toolbar',
        private array $sorts = [],
        private array $filters = [],
    ) {
        if (null !== $this->pageSize && $this->pageSize < 1) {
            throw new \InvalidArgumentException('The datatable preference page size must be greater than or equal to 1.');
        }

        SortCriterion::normalizeList($this->sorts);
    }

    public static function empty(): self
    {
        return new self();
    }

    /**
     * @param list<string>         $visibleColumns
     * @param list<string>         $hiddenColumns
     * @param list<SortCriterion>  $sorts
     * @param array<string, mixed> $filters
     */
    public static function create(
        ?int $pageSize = null,
        ?string $sortField = null,
        ?SortDirection $sortDirection = null,
        array $visibleColumns = [],
        array $hiddenColumns = [],
        string $filterLayout = 'toolbar',
        array $sorts = [],
        array $filters = [],
    ): self {
        $sorts = SortCriterion::normalizeList($sorts);

        if ([] !== $sorts) {
            $sortField = $sorts[0]->getField();
            $sortDirection = $sorts[0]->getDirection();
        }

        return new self(
            pageSize: $pageSize,
            sortField: self::normalizeNullableString($sortField),
            sortDirection: $sortDirection,
            visibleColumns: self::normalizeColumnList($visibleColumns),
            hiddenColumns: self::normalizeColumnList($hiddenColumns),
            filterLayout: $filterLayout,
            sorts: $sorts,
            filters: self::normalizeFilterMap($filters),
        );
    }

    public function getFilterLayout(): string
    {
        return $this->filterLayout;
    }

    public function getPageSize(): ?int
    {
        return $this->pageSize;
    }

    public function getSortField(): ?string
    {
        return ($this->getSorts()[0] ?? null)?->getField() ?? $this->sortField;
    }

    public function getSortDirection(): ?SortDirection
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

        return [new SortCriterion(
            $this->sortField,
            $this->sortDirection ?? SortDirection::Asc,
        )];
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
     * @return array<string, mixed>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function isEmpty(): bool
    {
        return null === $this->pageSize
            && null === $this->sortField
            && null === $this->sortDirection
            && [] === $this->sorts
            && [] === $this->visibleColumns
            && [] === $this->hiddenColumns
            && [] === $this->filters;
    }

    /**
     * @return array<string, mixed>
     */
    public function toRenderOptions(): array
    {
        $options = [];

        if (null !== $this->pageSize) {
            $options['pageSize'] = $this->pageSize;
        }

        if (null !== $this->getSortField()) {
            $options['sortField'] = $this->getSortField();
        }

        if (null !== $this->getSortDirection()) {
            $options['sortDirection'] = $this->getSortDirection()->value;
        }

        if ([] !== $this->getSorts()) {
            $options['sorts'] = array_map(
                static fn (SortCriterion $criterion): array => $criterion->toArray(),
                $this->getSorts(),
            );
        }

        if ([] !== $this->visibleColumns) {
            $options['visibleColumns'] = $this->visibleColumns;
        }

        if ([] !== $this->hiddenColumns) {
            $options['hiddenColumns'] = $this->hiddenColumns;
        }

        if ([] !== $this->filters) {
            $options['filters'] = $this->filters;
        }

        $options['filterLayout'] = $this->filterLayout;

        return $options;
    }

    /**
     * @return array{
     *     pageSize: int|null,
     *     sorts: list<array{field: string, direction: string}>,
     *     visibleColumns: list<string>,
     *     hiddenColumns: list<string>,
     *     filters: array<string, mixed>
     * }
     */
    public function toStorageArray(): array
    {
        return [
            'pageSize' => $this->pageSize,
            'sorts' => array_map(
                static fn (SortCriterion $criterion): array => $criterion->toArray(),
                $this->getSorts(),
            ),
            'visibleColumns' => $this->visibleColumns,
            'hiddenColumns' => $this->hiddenColumns,
            'filters' => $this->filters,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromStorageArray(array $payload): self
    {
        $pageSize = $payload['pageSize'] ?? null;
        $sorts = $payload['sorts'] ?? [];
        $visibleColumns = $payload['visibleColumns'] ?? [];
        $hiddenColumns = $payload['hiddenColumns'] ?? [];
        $filters = $payload['filters'] ?? [];

        if (
            (null !== $pageSize && !is_int($pageSize))
            || !is_array($sorts)
            || !array_is_list($sorts)
            || !is_array($visibleColumns)
            || !array_is_list($visibleColumns)
            || !is_array($hiddenColumns)
            || !array_is_list($hiddenColumns)
            || !is_array($filters)
            || (array_is_list($filters) && [] !== $filters)
        ) {
            throw new \InvalidArgumentException('The stored datatable preference payload is invalid.');
        }

        $criteria = [];

        foreach ($sorts as $sort) {
            if (!is_array($sort)) {
                throw new \InvalidArgumentException('The stored datatable preference sort payload is invalid.');
            }

            $field = $sort['field'] ?? null;
            $direction = $sort['direction'] ?? null;

            if (!is_string($field) || !is_string($direction)) {
                throw new \InvalidArgumentException('The stored datatable preference sort payload is invalid.');
            }

            $criteria[] = SortCriterion::create($field, $direction);
        }

        return self::create(
            pageSize: $pageSize,
            visibleColumns: self::assertStringList($visibleColumns),
            hiddenColumns: self::assertStringList($hiddenColumns),
            sorts: $criteria,
            filters: self::normalizeFilterMap($filters),
        );
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

    /**
     * @param array<array-key, mixed> $values
     *
     * @return list<string>
     */
    private static function assertStringList(array $values): array
    {
        foreach ($values as $value) {
            if (!is_string($value)) {
                throw new \InvalidArgumentException('Stored datatable preference column lists must contain strings.');
            }
        }

        /** @var list<string> $values */
        return $values;
    }

    /**
     * @param array<array-key, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private static function normalizeFilterMap(array $filters): array
    {
        $normalized = [];

        foreach ($filters as $name => $value) {
            if (!is_string($name) || '' === trim($name)) {
                throw new \InvalidArgumentException('Datatable preference filters must use non-empty string keys.');
            }

            $normalized[trim($name)] = $value;
        }

        return $normalized;
    }
}
