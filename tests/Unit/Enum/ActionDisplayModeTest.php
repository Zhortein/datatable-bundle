<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Enum\ActionDisplayMode;

final class ActionDisplayModeTest extends TestCase
{
    public function test_it_creates_display_mode_from_valid_string(): void
    {
        self::assertSame(ActionDisplayMode::Inline, ActionDisplayMode::fromNullableString('inline'));
        self::assertSame(ActionDisplayMode::Dropdown, ActionDisplayMode::fromNullableString('dropdown'));
        self::assertSame(ActionDisplayMode::List, ActionDisplayMode::fromNullableString('list'));
        self::assertSame(ActionDisplayMode::Dropdown, ActionDisplayMode::fromNullableString(' DROPDOWN '));
    }

    public function test_it_falls_back_to_inline_for_null_empty_or_unknown_value(): void
    {
        self::assertSame(ActionDisplayMode::Inline, ActionDisplayMode::fromNullableString(null));
        self::assertSame(ActionDisplayMode::Inline, ActionDisplayMode::fromNullableString(''));
        self::assertSame(ActionDisplayMode::Inline, ActionDisplayMode::fromNullableString('unknown'));
    }
}
