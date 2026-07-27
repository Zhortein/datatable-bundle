<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\View;

use Zhortein\DatatableBundle\Contract\DatatableViewAuthorizationCheckerInterface;
use Zhortein\DatatableBundle\Enum\DatatableViewOperation;

final readonly class DenyDatatableViewAuthorizationChecker implements DatatableViewAuthorizationCheckerInterface
{
    public function isGranted(
        DatatableViewOperation $operation,
        DatatableViewAuthorizationContext $context,
    ): bool {
        return false;
    }
}
