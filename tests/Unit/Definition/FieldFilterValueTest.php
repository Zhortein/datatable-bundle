<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Definition;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\FieldFilterValue;

final class FieldFilterValueTest extends TestCase
{
    public function test_it_normalizes_a_field(): void
    {
        self::assertSame('e.stockAlert', FieldFilterValue::from(' e.stockAlert ')->getField());
    }

    public function test_it_rejects_an_empty_field(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A field filter value must name a non-empty field.');

        FieldFilterValue::from(' ');
    }
}
