<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export;

use Zhortein\DatatableBundle\Contract\ExportCancellationInterface;

final readonly class ConnectionAbortedExportCancellation implements ExportCancellationInterface
{
    public function isCancelled(): bool
    {
        return 0 !== connection_aborted();
    }
}
