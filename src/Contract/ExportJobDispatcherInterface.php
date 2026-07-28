<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Contract;

use Zhortein\DatatableBundle\Export\Job\ExportJobIdentifier;

interface ExportJobDispatcherInterface
{
    public function dispatch(ExportJobIdentifier $identifier): void;
}
