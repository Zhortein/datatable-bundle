<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\View;

use Zhortein\DatatableBundle\State\DatatableState;

/**
 * Immutable state stored by a named view.
 *
 * Current pagination is deliberately reset unless includePage is explicitly
 * requested by the host application.
 */
final readonly class DatatableViewState
{
    private DatatableState $state;

    public function __construct(DatatableState $state, private bool $includePage = false)
    {
        $this->state = $this->includePage ? $state : self::withoutCurrentPage($state);
    }

    public static function create(DatatableState $state, bool $includePage = false): self
    {
        return new self($state, $includePage);
    }

    public function getState(): DatatableState
    {
        return $this->state;
    }

    public function includesPage(): bool
    {
        return $this->includePage;
    }

    /**
     * @return array{includePage: bool, state: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'includePage' => $this->includePage,
            'state' => $this->state->toArray(),
        ];
    }

    private static function withoutCurrentPage(DatatableState $state): DatatableState
    {
        return DatatableState::create(
            page: 1,
            pageSize: $state->getPageSize(),
            searchQuery: $state->getSearchQuery(),
            sortField: $state->getSortField(),
            sortDirection: $state->getSortDirection(),
            filters: $state->getFilters(),
            advancedFilters: $state->getAdvancedFilters(),
            visibleColumns: $state->getVisibleColumns(),
            hiddenColumns: $state->getHiddenColumns(),
            sorts: $state->getSorts(),
        );
    }
}
