<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export\Job;

use Zhortein\DatatableBundle\Contract\ExportJobExpiryPolicyInterface;

final readonly class FixedExportJobExpiryPolicy implements ExportJobExpiryPolicyInterface
{
    public function __construct(
        private int $ttl,
    ) {
        if ($this->ttl < 1) {
            throw new \InvalidArgumentException('The export job time-to-live must be greater than or equal to one second.');
        }
    }

    public function expiresAt(\DateTimeImmutable $from): \DateTimeImmutable
    {
        return $from->modify(sprintf('+%d seconds', $this->ttl));
    }
}
