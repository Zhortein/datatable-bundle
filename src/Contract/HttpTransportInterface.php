<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Contract;

use Zhortein\DatatableBundle\Provider\Http\HttpTransportRequest;
use Zhortein\DatatableBundle\Provider\Http\HttpTransportResponse;

/**
 * Transport boundary for HTTP-backed datatables.
 */
interface HttpTransportInterface
{
    public function send(HttpTransportRequest $request): HttpTransportResponse;
}
