<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export;

use Zhortein\DatatableBundle\Contract\ExportCancellationInterface;

/**
 * Immutable runtime constraints shared by a streaming provider and writer.
 */
final readonly class ExportStreamContext
{
    public function __construct(
        private int $batchSize,
        private int $expectedRowCount,
        private ExportCancellationInterface $cancellation,
    ) {
        if ($this->batchSize < 1) {
            throw new \InvalidArgumentException('The export stream batch size must be greater than or equal to 1.');
        }

        if ($this->expectedRowCount < 0) {
            throw new \InvalidArgumentException('The expected export row count must be greater than or equal to 0.');
        }
    }

    public function getBatchSize(): int
    {
        return $this->batchSize;
    }

    public function getExpectedRowCount(): int
    {
        return $this->expectedRowCount;
    }

    /** @phpstan-impure */
    public function isCancelled(): bool
    {
        return $this->cancellation->isCancelled();
    }
}
