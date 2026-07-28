<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Preference;

use Zhortein\DatatableBundle\Definition\DatatableDefinition;

final readonly class DatatablePreferenceSchema
{
    public static function version(
        DatatableDefinition $definition,
        string $applicationVersion = '1',
    ): string {
        $columns = [];

        foreach ($definition->getColumns() as $column) {
            $columns[] = [
                'name' => $column->getName(),
                'visible' => $column->isVisible(),
                'sortable' => $column->isSortable(),
            ];
        }

        $filters = [];

        foreach ($definition->getFilters() as $filter) {
            if (!$filter->isPreferenceSafe()) {
                continue;
            }

            $filters[] = [
                'name' => $filter->getName(),
                'field' => $filter->getField(),
                'type' => $filter->getType()->value,
            ];
        }

        return substr(hash('sha256', json_encode([
            'application' => trim($applicationVersion),
            'columns' => $columns,
            'filters' => $filters,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)), 0, 32);
    }
}
