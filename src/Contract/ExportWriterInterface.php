<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Contract;

use Symfony\Component\HttpFoundation\Response;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\ExportFormat;
use Zhortein\DatatableBundle\Export\DatatableExportRequest;
use Zhortein\DatatableBundle\Result\DatatableResult;

interface ExportWriterInterface
{
    public function supports(ExportFormat $format): bool;

    public function write(
        DatatableExportRequest $request,
        DatatableDefinition $definition,
        DatatableResult $result,
    ): Response;
}
