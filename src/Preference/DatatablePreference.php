<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Preference;

use Zhortein\DatatableBundle\Enum\SortDirection;

final readonly class DatatablePreference
{
    /**
     * @param list<string> $visibleColumns
     * @param list<string> $hiddenColumns
     */
    public function __construct(
        private ?int $pageSize = null,
        private ?string $sortField = null,
        private ?SortDirection $sortDirection = null,
        private array $visibleColumns = [],
        private array $hiddenColumns = [],
        private string $filterLayout = 'toolbar',
    ) {
        if (null !== $this->pageSize && $this->pageSize < 1) {
            throw new \InvalidArgumentException('The datatable preference page size must be greater than or equal to 1.');
        }
    }

    public static function empty(): self
    {
        return new self();
    }

    /**
     * @param list<string> $visibleColumns
     * @param list<string> $hiddenColumns
     */
    public static function create(
        ?int $pageSize = null,
        ?string $sortField = null,
        ?SortDirection $sortDirection = null,
        array $visibleColumns = [],
        array $hiddenColumns = [],
        string $filterLayout = 'toolbar',
    ): self {
        return new self(
            pageSize: $pageSize,
            sortField: self::normalizeNullableString($sortField),
            sortDirection: $sortDirection,
            visibleColumns: self::normalizeColumnList($visibleColumns),
            hiddenColumns: self::normalizeColumnList($hiddenColumns),
            filterLayout: $filterLayout,
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
        return $this->sortField;
    }

    public function getSortDirection(): ?SortDirection
    {
        return $this->sortDirection;
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

    public function isEmpty(): bool
    {
        return null === $this->pageSize
            && null === $this->sortField
            && null === $this->sortDirection
            && [] === $this->visibleColumns
            && [] === $this->hiddenColumns;
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

        if (null !== $this->sortField) {
            $options['sortField'] = $this->sortField;
        }

        if (null !== $this->sortDirection) {
            $options['sortDirection'] = $this->sortDirection->value;
        }

        if ([] !== $this->visibleColumns) {
            $options['visibleColumns'] = $this->visibleColumns;
        }

        if ([] !== $this->hiddenColumns) {
            $options['hiddenColumns'] = $this->hiddenColumns;
        }

        $options['filterLayout'] = $this->filterLayout;

        return $options;
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
}
