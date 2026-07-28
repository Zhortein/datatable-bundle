<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Contract;

use Symfony\Component\HttpFoundation\Request;

interface ExportJobOwnerResolverInterface
{
    /**
     * Returns an opaque, stable owner/scope identifier or null for anonymous access.
     */
    public function resolve(Request $request): ?string;
}
