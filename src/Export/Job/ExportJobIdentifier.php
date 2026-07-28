<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export\Job;

final readonly class ExportJobIdentifier implements \Stringable
{
    public function __construct(
        private string $value,
    ) {
        if (1 !== preg_match('/^[A-Za-z0-9_-]{16,128}$/', $this->value)) {
            throw new \InvalidArgumentException('An export job identifier must contain 16 to 128 URL-safe opaque characters.');
        }
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
