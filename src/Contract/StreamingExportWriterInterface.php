<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Contract;

use Symfony\Component\HttpFoundation\Response;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Export\DatatableExportRequest;
use Zhortein\DatatableBundle\Export\ExportRow;
use Zhortein\DatatableBundle\Export\ExportStreamContext;

/**
 * Optional writer capability for consuming export rows incrementally.
 */
interface StreamingExportWriterInterface
{
    /**
     * @param iterable<ExportRow> $rows
     */
    public function writeStream(
        DatatableExportRequest $request,
        DatatableDefinition $definition,
        iterable $rows,
        ExportStreamContext $context,
    ): Response;
}
