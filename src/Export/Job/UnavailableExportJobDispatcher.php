<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export\Job;

use Zhortein\DatatableBundle\Contract\ExportJobDispatcherInterface;

final class UnavailableExportJobDispatcher implements ExportJobDispatcherInterface
{
    public function dispatch(ExportJobIdentifier $identifier): void
    {
        throw new \LogicException('No asynchronous export job dispatcher is configured.');
    }
}
