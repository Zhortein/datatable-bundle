<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Contract;

interface IconResolverInterface
{
    public function resolve(string $key): ?string;
}
