<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Preference;

use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\State\DatatableState;

final readonly class DatatablePreferenceSanitizer
{
    public function __construct(
        private int $maximumPageSize = 500,
    ) {
        if ($this->maximumPageSize < 1) {
            throw new \InvalidArgumentException('The maximum datatable preference page size must be greater than or equal to 1.');
        }
    }

    public function sanitize(
        DatatableDefinition $definition,
        DatatableState $state,
    ): DatatablePreference {
        $availableColumns = $definition->getColumns();
        $visibleColumns = [];
        $hiddenColumns = [];

        foreach ($state->getVisibleColumns() as $column) {
            if (isset($availableColumns[$column]) && $availableColumns[$column]->isVisible()) {
                $visibleColumns[] = $column;
            }
        }

        foreach ($state->getHiddenColumns() as $column) {
            if (isset($availableColumns[$column]) && $availableColumns[$column]->isVisible()) {
                $hiddenColumns[] = $column;
            }
        }

        $sorts = [];

        foreach ($state->getSorts() as $sort) {
            $column = $availableColumns[$sort->getField()] ?? null;

            if (null !== $column && $column->isSortable()) {
                $sorts[] = $sort;
            }
        }

        $filters = [];

        foreach ($definition->getFilters() as $name => $filter) {
            if (
                $filter->isPreferenceSafe()
                && array_key_exists($name, $state->getFilters())
            ) {
                $filters[$name] = $state->getFilters()[$name];
            }
        }

        return DatatablePreference::create(
            pageSize: min($state->getPageSize(), $this->maximumPageSize),
            visibleColumns: $visibleColumns,
            hiddenColumns: $hiddenColumns,
            sorts: $sorts,
            filters: $filters,
        );
    }
}
