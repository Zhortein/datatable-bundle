<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Contract;

use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Provider\Http\HttpDataPage;
use Zhortein\DatatableBundle\Provider\Http\HttpProviderConfiguration;
use Zhortein\DatatableBundle\Provider\Http\HttpTransportResponse;
use Zhortein\DatatableBundle\Request\DatatableRequest;

interface HttpResponseMapperInterface
{
    public function mapResponse(
        HttpTransportResponse $response,
        DatatableDefinition $definition,
        DatatableRequest $request,
        HttpProviderConfiguration $configuration,
    ): HttpDataPage;
}
