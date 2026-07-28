<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Contract;

use Symfony\Component\HttpFoundation\Request;

interface DatatablePreferenceIdentityResolverInterface
{
    /**
     * Returns an opaque, stable owner identifier or null for anonymous requests.
     */
    public function resolvePreferenceOwnerIdentifier(Request $request): ?string;
}
