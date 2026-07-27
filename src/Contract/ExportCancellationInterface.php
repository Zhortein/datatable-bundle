<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Contract;

/**
 * Reports whether an in-progress export should stop producing rows.
 */
interface ExportCancellationInterface
{
    /** @phpstan-impure */
    public function isCancelled(): bool;
}
