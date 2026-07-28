<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Provider\Http;

use Zhortein\DatatableBundle\Enum\HttpPaginationStrategy;

/**
 * Explicit operations supported by one remote API.
 */
final readonly class HttpProviderCapabilities
{
    /**
     * @param list<HttpPaginationStrategy> $paginationStrategies
     */
    public function __construct(
        private array $paginationStrategies = [HttpPaginationStrategy::Page],
        private bool $search = false,
        private bool $sorting = false,
        private bool $simpleFilters = false,
        private bool $advancedFilters = false,
        private bool $exports = false,
        private bool $exactCounts = false,
    ) {
        if ([] === $this->paginationStrategies) {
            throw new \InvalidArgumentException('At least one HTTP pagination strategy must be declared.');
        }

        foreach ($this->paginationStrategies as $strategy) {
            self::validatePaginationStrategy($strategy);
        }
    }

    private static function validatePaginationStrategy(mixed $strategy): void
    {
        if (!$strategy instanceof HttpPaginationStrategy) {
            throw new \InvalidArgumentException('HTTP pagination strategies must use HttpPaginationStrategy values.');
        }
    }

    public function supportsPagination(HttpPaginationStrategy $strategy): bool
    {
        return in_array($strategy, $this->paginationStrategies, true);
    }

    public function supportsSearch(): bool
    {
        return $this->search;
    }

    public function supportsSorting(): bool
    {
        return $this->sorting;
    }

    public function supportsSimpleFilters(): bool
    {
        return $this->simpleFilters;
    }

    public function supportsAdvancedFilters(): bool
    {
        return $this->advancedFilters;
    }

    public function supportsExports(): bool
    {
        return $this->exports;
    }

    public function providesExactCounts(): bool
    {
        return $this->exactCounts;
    }
}
