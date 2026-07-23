<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export;

use Zhortein\DatatableBundle\Definition\ColumnDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;

final readonly class ExportableColumnResolver
{
    /**
     * @return list<ColumnDefinition>
     */
    public function resolve(
        DatatableExportRequest $request,
        DatatableDefinition $definition,
    ): array {
        $datatableRequest = $request->getDatatableRequest();

        $visibleColumns = $datatableRequest?->getVisibleColumns() ?? [];
        $hiddenColumns = $datatableRequest?->getHiddenColumns() ?? [];

        return array_values(array_filter(
            $definition->getColumns(),
            static function (ColumnDefinition $column) use ($visibleColumns, $hiddenColumns): bool {
                $exportable = $column->getExportable();

                if (null !== $exportable) {
                    return $exportable;
                }

                if (!$column->isVisible()) {
                    return false;
                }

                if ([] !== $visibleColumns && !in_array($column->getName(), $visibleColumns, true)) {
                    return false;
                }

                return !in_array($column->getName(), $hiddenColumns, true);
            },
        ));
    }
}
