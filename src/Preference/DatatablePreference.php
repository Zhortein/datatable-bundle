<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Preference;

use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Sorting\SortCriterion;

final readonly class DatatablePreference
{
    /**
     * @param list<string>        $visibleColumns
     * @param list<string>        $hiddenColumns
     * @param list<SortCriterion> $sorts
     */
    public function __construct(
        private ?int $pageSize = null,
        private ?string $sortField = null,
        private ?SortDirection $sortDirection = null,
        private array $visibleColumns = [],
        private array $hiddenColumns = [],
        private string $filterLayout = 'toolbar',
        private array $sorts = [],
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
     * @param list<string>        $visibleColumns
     * @param list<string>        $hiddenColumns
     * @param list<SortCriterion> $sorts
     */
    public static function create(
        ?int $pageSize = null,
        ?string $sortField = null,
        ?SortDirection $sortDirection = null,
        array $visibleColumns = [],
        array $hiddenColumns = [],
        string $filterLayout = 'toolbar',
        array $sorts = [],
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

    public function isEmpty(): bool
    {
        return null === $this->pageSize
            && null === $this->sortField
            && null === $this->sortDirection
            && [] === $this->sorts
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
