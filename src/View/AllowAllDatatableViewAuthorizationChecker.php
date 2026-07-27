<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\View;

use Zhortein\DatatableBundle\Contract\DatatableViewAuthorizationCheckerInterface;
use Zhortein\DatatableBundle\Enum\DatatableViewOperation;

/**
 * Explicit opt-in checker intended for tests or genuinely public view stores.
 */
final readonly class AllowAllDatatableViewAuthorizationChecker implements DatatableViewAuthorizationCheckerInterface
{
    public function isGranted(
        DatatableViewOperation $operation,
        DatatableViewAuthorizationContext $context,
    ): bool {
        return true;
    }
}
