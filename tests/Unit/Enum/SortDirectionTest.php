<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Enum\SortDirection;

final class SortDirectionTest extends TestCase
{
    public function test_it_creates_direction_from_valid_string(): void
    {
        self::assertSame(SortDirection::Asc, SortDirection::fromString('asc'));
        self::assertSame(SortDirection::Desc, SortDirection::fromString('desc'));
        self::assertSame(SortDirection::Asc, SortDirection::fromString(' ASC '));
        self::assertSame(SortDirection::Desc, SortDirection::fromString(' DESC '));
    }

    public function test_it_rejects_invalid_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sort direction "invalid". Expected "asc" or "desc".');

        SortDirection::fromString('invalid');
    }
}
