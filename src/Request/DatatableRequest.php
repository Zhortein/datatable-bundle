<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Request;

use Zhortein\DatatableBundle\Enum\SortDirection;

final readonly class DatatableRequest
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        private int $page = 1,
        private int $pageSize = 25,
        private ?string $searchQuery = null,
        private ?string $sortField = null,
        private SortDirection $sortDirection = SortDirection::Asc,
        private array $options = [],
    ) {
        if ($this->page < 1) {
            throw new \InvalidArgumentException('The datatable page must be greater than or equal to 1.');
        }

        if ($this->pageSize < 1) {
            throw new \InvalidArgumentException('The datatable page size must be greater than or equal to 1.');
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    public static function create(
        int $page = 1,
        int $pageSize = 25,
        ?string $searchQuery = null,
        ?string $sortField = null,
        SortDirection|string $sortDirection = SortDirection::Asc,
        array $options = [],
    ): self {
        return new self(
            page: $page,
            pageSize: $pageSize,
            searchQuery: self::normalizeNullableString($searchQuery),
            sortField: self::normalizeNullableString($sortField),
            sortDirection: is_string($sortDirection) ? SortDirection::fromString($sortDirection) : $sortDirection,
            options: $options,
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
        return $this->sortField;
    }

    public function hasSort(): bool
    {
        return null !== $this->sortField;
    }

    public function getSortDirection(): SortDirection
    {
        return $this->sortDirection;
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
}
