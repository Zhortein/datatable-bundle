<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export;

use Zhortein\DatatableBundle\Contract\DatatableExportAuthorizationCheckerInterface;

/**
 * Backward-compatible default. Host applications remain responsible for
 * business authorization and may replace this service alias.
 */
final readonly class AllowAllDatatableExportAuthorizationChecker implements DatatableExportAuthorizationCheckerInterface
{
    public function isGranted(DatatableExportAuthorizationContext $context): bool
    {
        return true;
    }
}
