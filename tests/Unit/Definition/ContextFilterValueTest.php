<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Definition;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\ContextFilterValue;

final class ContextFilterValueTest extends TestCase
{
    public function test_it_normalizes_a_context_key(): void
    {
        self::assertSame('orderId', ContextFilterValue::from(' orderId ')->getKey());
    }

    public function test_it_rejects_an_empty_context_key(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A context filter value key must be a non-empty string.');

        ContextFilterValue::from(' ');
    }
}
