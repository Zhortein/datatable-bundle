<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export\Job;

use Symfony\Component\HttpFoundation\Request;
use Zhortein\DatatableBundle\Contract\ExportJobOwnerResolverInterface;

final class NullExportJobOwnerResolver implements ExportJobOwnerResolverInterface
{
    public function resolve(Request $request): ?string
    {
        return null;
    }
}
