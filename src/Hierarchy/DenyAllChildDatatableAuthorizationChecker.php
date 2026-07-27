<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Hierarchy;

use Zhortein\DatatableBundle\Contract\ChildDatatableAuthorizationCheckerInterface;

final readonly class DenyAllChildDatatableAuthorizationChecker implements ChildDatatableAuthorizationCheckerInterface
{
    public function isGranted(ChildDatatableAuthorizationContext $context): bool
    {
        return false;
    }
}
