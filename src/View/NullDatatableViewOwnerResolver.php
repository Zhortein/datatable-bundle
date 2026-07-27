<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\View;

use Symfony\Component\HttpFoundation\Request;
use Zhortein\DatatableBundle\Contract\DatatableViewOwnerResolverInterface;

final readonly class NullDatatableViewOwnerResolver implements DatatableViewOwnerResolverInterface
{
    public function resolveOwnerIdentifier(Request $request): ?string
    {
        return null;
    }
}
