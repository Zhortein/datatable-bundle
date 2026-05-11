<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Enum\BooleanDisplayMode;

final class BooleanDisplayModeTest extends TestCase
{
    public function test_it_creates_display_mode_from_valid_string(): void
    {
        self::assertSame(BooleanDisplayMode::Badge, BooleanDisplayMode::fromNullableString('badge'));
        self::assertSame(BooleanDisplayMode::Icon, BooleanDisplayMode::fromNullableString('icon'));
        self::assertSame(BooleanDisplayMode::Switch, BooleanDisplayMode::fromNullableString('switch'));
        self::assertSame(BooleanDisplayMode::Text, BooleanDisplayMode::fromNullableString('text'));
        self::assertSame(BooleanDisplayMode::Icon, BooleanDisplayMode::fromNullableString(' ICON '));
    }

    public function test_it_falls_back_to_badge_for_null_empty_or_unknown_value(): void
    {
        self::assertSame(BooleanDisplayMode::Badge, BooleanDisplayMode::fromNullableString(null));
        self::assertSame(BooleanDisplayMode::Badge, BooleanDisplayMode::fromNullableString(''));
        self::assertSame(BooleanDisplayMode::Badge, BooleanDisplayMode::fromNullableString('unknown'));
    }
}
