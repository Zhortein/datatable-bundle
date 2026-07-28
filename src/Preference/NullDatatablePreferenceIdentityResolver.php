<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Preference;

use Symfony\Component\HttpFoundation\Request;
use Zhortein\DatatableBundle\Contract\DatatablePreferenceIdentityResolverInterface;

final readonly class NullDatatablePreferenceIdentityResolver implements DatatablePreferenceIdentityResolverInterface
{
    public function resolvePreferenceOwnerIdentifier(Request $request): ?string
    {
        return null;
    }
}
