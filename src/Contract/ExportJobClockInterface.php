<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Contract;

interface ExportJobClockInterface
{
    public function now(): \DateTimeImmutable;
}
