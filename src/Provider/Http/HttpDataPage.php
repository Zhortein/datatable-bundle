<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Provider\Http;

final readonly class HttpDataPage
{
    /**
     * @param list<array<string, mixed>> $rows
     * @param list<string|int|null>      $identifiers
     */
    public function __construct(
        private array $rows,
        private array $identifiers = [],
        private ?int $totalItems = null,
        private ?int $filteredItems = null,
        private ?string $nextCursor = null,
        private ?string $previousCursor = null,
        private ?bool $hasNextPage = null,
    ) {
        if ([] !== $this->identifiers && count($this->rows) !== count($this->identifiers)) {
            throw new \InvalidArgumentException('HTTP response identifiers must align with response rows.');
        }

        if (null !== $this->totalItems && $this->totalItems < 0) {
            throw new \InvalidArgumentException('The HTTP response total item count cannot be negative.');
        }

        if (null !== $this->filteredItems && $this->filteredItems < 0) {
            throw new \InvalidArgumentException('The HTTP response filtered item count cannot be negative.');
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    /**
     * @return list<string|int|null>
     */
    public function getIdentifiers(): array
    {
        return $this->identifiers;
    }

    public function getTotalItems(): ?int
    {
        return $this->totalItems;
    }

    public function getFilteredItems(): ?int
    {
        return $this->filteredItems;
    }

    public function getNextCursor(): ?string
    {
        return $this->nextCursor;
    }

    public function getPreviousCursor(): ?string
    {
        return $this->previousCursor;
    }

    public function hasNextPage(): ?bool
    {
        return $this->hasNextPage;
    }
}
