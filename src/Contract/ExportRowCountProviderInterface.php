<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Contract;

use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Request\DatatableRequest;

/**
 * Optional provider capability required for guarded synchronous exports.
 *
 * Implementations must return the exact filtered row count or a conservative
 * upper bound. Pagination and sorting must not affect the returned count.
 */
interface ExportRowCountProviderInterface
{
    public function countExportRows(
        DatatableDefinition $definition,
        DatatableRequest $request,
    ): int;
}
