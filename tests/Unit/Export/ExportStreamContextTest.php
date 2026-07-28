<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Export;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Contract\ExportCancellationInterface;
use Zhortein\DatatableBundle\Export\ExportStreamContext;

final class ExportStreamContextTest extends TestCase
{
    public function test_it_exposes_bounded_runtime_constraints_and_cancellation(): void
    {
        $cancellation = new MutableExportCancellation();
        $context = new ExportStreamContext(250, 1200, $cancellation);

        self::assertSame(250, $context->getBatchSize());
        self::assertSame(1200, $context->getExpectedRowCount());
        self::assertFalse($context->isCancelled());

        $cancellation->cancelled = true;

        self::assertTrue($context->isCancelled());
    }

    public function test_it_rejects_invalid_batch_size(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ExportStreamContext(0, 1, new MutableExportCancellation());
    }

    public function test_it_rejects_negative_expected_row_count(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ExportStreamContext(1, -1, new MutableExportCancellation());
    }
}

final class MutableExportCancellation implements ExportCancellationInterface
{
    public bool $cancelled = false;

    public function isCancelled(): bool
    {
        return $this->cancelled;
    }
}
