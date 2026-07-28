<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Provider\Http;

/**
 * Dot-separated paths used to extract a remote response.
 */
final readonly class HttpResponseMapping
{
    public function __construct(
        private string $rowsPath = 'items',
        private ?string $identifierPath = 'id',
        private ?string $totalItemsPath = 'total',
        private ?string $filteredItemsPath = null,
        private ?string $nextCursorPath = 'pagination.next_cursor',
        private ?string $previousCursorPath = 'pagination.previous_cursor',
        private ?string $hasNextPagePath = 'pagination.has_next',
    ) {
        if ('' === trim($this->rowsPath)) {
            throw new \InvalidArgumentException('The HTTP response rows path cannot be empty.');
        }
    }

    public function getRowsPath(): string
    {
        return $this->rowsPath;
    }

    public function getIdentifierPath(): ?string
    {
        return $this->identifierPath;
    }

    public function getTotalItemsPath(): ?string
    {
        return $this->totalItemsPath;
    }

    public function getFilteredItemsPath(): ?string
    {
        return $this->filteredItemsPath;
    }

    public function getNextCursorPath(): ?string
    {
        return $this->nextCursorPath;
    }

    public function getPreviousCursorPath(): ?string
    {
        return $this->previousCursorPath;
    }

    public function getHasNextPagePath(): ?string
    {
        return $this->hasNextPagePath;
    }
}
