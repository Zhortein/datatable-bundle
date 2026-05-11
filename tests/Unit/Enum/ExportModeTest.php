<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Enum\ExportMode;

final class ExportModeTest extends TestCase
{
    public function test_it_creates_export_mode_from_valid_string(): void
    {
        self::assertSame(ExportMode::Current, ExportMode::fromString('current'));
        self::assertSame(ExportMode::Full, ExportMode::fromString('full'));
        self::assertSame(ExportMode::Full, ExportMode::fromString(' FULL '));
    }

    public function test_it_rejects_invalid_mode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid export mode "invalid". Supported modes: current, full.');

        ExportMode::fromString('invalid');
    }

    public function test_it_knows_whether_pagination_should_be_kept(): void
    {
        self::assertTrue(ExportMode::Current->shouldKeepPagination());
        self::assertFalse(ExportMode::Full->shouldKeepPagination());
    }
}
