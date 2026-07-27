<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Contract;

use Zhortein\DatatableBundle\Cell\CellContext;

interface CellValueResolverInterface
{
    public function getName(): string;

    public function resolve(CellContext $context): mixed;
}
