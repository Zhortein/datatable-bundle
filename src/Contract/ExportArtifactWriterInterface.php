<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Contract;

use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Export\DatatableExportRequest;
use Zhortein\DatatableBundle\Export\ExportRow;
use Zhortein\DatatableBundle\Export\ExportStreamContext;
use Zhortein\DatatableBundle\Export\Job\ExportArtifact;

/**
 * Optional writer capability for background export artifacts.
 */
interface ExportArtifactWriterInterface
{
    /**
     * @param iterable<ExportRow> $rows
     */
    public function writeArtifact(
        DatatableExportRequest $request,
        DatatableDefinition $definition,
        iterable $rows,
        ExportStreamContext $context,
    ): ExportArtifact;
}
