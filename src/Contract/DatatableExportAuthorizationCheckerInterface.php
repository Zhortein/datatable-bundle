<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Contract;

use Zhortein\DatatableBundle\Export\DatatableExportAuthorizationContext;

interface DatatableExportAuthorizationCheckerInterface
{
    public function isGranted(DatatableExportAuthorizationContext $context): bool;
}
