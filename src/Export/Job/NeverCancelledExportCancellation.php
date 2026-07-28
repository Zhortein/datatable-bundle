<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export\Job;

use Zhortein\DatatableBundle\Contract\ExportCancellationInterface;

final class NeverCancelledExportCancellation implements ExportCancellationInterface
{
    public function isCancelled(): bool
    {
        return false;
    }
}
