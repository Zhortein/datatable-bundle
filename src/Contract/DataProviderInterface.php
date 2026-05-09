<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Contract;

use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Result\DatatableResult;

interface DataProviderInterface
{
    public function supports(DatatableDefinition $definition): bool;

    public function getData(DatatableDefinition $definition, DatatableRequest $request): DatatableResult;
}
