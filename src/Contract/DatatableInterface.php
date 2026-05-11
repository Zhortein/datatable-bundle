<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Contract;

use Zhortein\DatatableBundle\Definition\DatatableDefinition;

interface DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void;
}
