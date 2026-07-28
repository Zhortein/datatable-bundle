<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Contract;

use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Provider\Http\HttpProviderConfiguration;
use Zhortein\DatatableBundle\Provider\Http\HttpTransportRequest;
use Zhortein\DatatableBundle\Request\DatatableRequest;

interface HttpRequestMapperInterface
{
    public function mapRequest(
        DatatableDefinition $definition,
        DatatableRequest $request,
        HttpProviderConfiguration $configuration,
    ): HttpTransportRequest;
}
