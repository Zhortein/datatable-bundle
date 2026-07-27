<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Contract;

use Symfony\Component\HttpFoundation\Request;

interface DatatableViewOwnerResolverInterface
{
    /**
     * Returns an opaque storage identifier or null for an application-defined
     * shared/anonymous scope.
     */
    public function resolveOwnerIdentifier(Request $request): ?string;
}
