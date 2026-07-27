<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Contract;

use Zhortein\DatatableBundle\Enum\DatatableViewOperation;
use Zhortein\DatatableBundle\View\DatatableViewAuthorizationContext;

interface DatatableViewAuthorizationCheckerInterface
{
    public function isGranted(
        DatatableViewOperation $operation,
        DatatableViewAuthorizationContext $context,
    ): bool;
}
