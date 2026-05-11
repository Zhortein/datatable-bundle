<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Result;

final readonly class DatatableResult
{
    /**
     * @param list<array<string, mixed>> $rows
     */
    public function __construct(
        private array $rows = [],
        private int $page = 1,
        private int $pageSize = 25,
        private int $totalItems = 0,
        private ?int $filteredItems = null,
    ) {
        if ($this->page < 1) {
            throw new \InvalidArgumentException('The datatable result page must be greater than or equal to 1.');
        }

        if ($this->pageSize < 1) {
            throw new \InvalidArgumentException('The datatable result page size must be greater than or equal to 1.');
        }

        if ($this->totalItems < 0) {
            throw new \InvalidArgumentException('The datatable result total items count must be greater than or equal to 0.');
        }

        if (null !== $this->filteredItems && $this->filteredItems < 0) {
            throw new \InvalidArgumentException('The datatable result filtered items count must be greater than or equal to 0.');
        }
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    public static function create(
        array $rows = [],
        int $page = 1,
        int $pageSize = 25,
        int $totalItems = 0,
        ?int $filteredItems = null,
    ): self {
        return new self(
            rows: $rows,
            page: $page,
            pageSize: $pageSize,
            totalItems: $totalItems,
            filteredItems: $filteredItems,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    public function getFilteredItems(): int
    {
        return $this->filteredItems ?? $this->totalItems;
    }

    public function hasFilteredItems(): bool
    {
        return null !== $this->filteredItems && $this->filteredItems !== $this->totalItems;
    }

    public function getTotalPages(): int
    {
        if (0 === $this->getFilteredItems()) {
            return 0;
        }

        return (int) ceil($this->getFilteredItems() / $this->pageSize);
    }

    public function hasRows(): bool
    {
        return [] !== $this->rows;
    }

    public function isEmpty(): bool
    {
        return [] === $this->rows;
    }
}
