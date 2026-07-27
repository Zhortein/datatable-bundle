<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Contract;

use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Export\ExportRow;
use Zhortein\DatatableBundle\Export\ExportStreamContext;
use Zhortein\DatatableBundle\Request\DatatableRequest;

/**
 * Optional provider capability for bounded-memory exports.
 */
interface StreamingDataProviderInterface
{
    /**
     * @return iterable<ExportRow>
     */
    public function streamExportRows(
        DatatableDefinition $definition,
        DatatableRequest $request,
        ExportStreamContext $context,
    ): iterable;
}
