<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Contract;

/**
 * Cooperative cancellation signal for remote provider requests.
 */
interface HttpRequestCancellationInterface
{
    public function isCancellationRequested(): bool;
}
