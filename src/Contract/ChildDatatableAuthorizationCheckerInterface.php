<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Contract;

use Zhortein\DatatableBundle\Hierarchy\ChildDatatableAuthorizationContext;

interface ChildDatatableAuthorizationCheckerInterface
{
    public function isGranted(ChildDatatableAuthorizationContext $context): bool;
}
