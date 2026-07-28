<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Contract;

interface ExportJobExpiryPolicyInterface
{
    public function expiresAt(\DateTimeImmutable $from): \DateTimeImmutable;
}
