<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Enum\ActionIconPosition;

final class ActionIconPositionTest extends TestCase
{
    public function test_it_creates_icon_position_from_valid_string(): void
    {
        self::assertSame(ActionIconPosition::Before, ActionIconPosition::fromNullableString('before'));
        self::assertSame(ActionIconPosition::After, ActionIconPosition::fromNullableString('after'));
        self::assertSame(ActionIconPosition::After, ActionIconPosition::fromNullableString(' AFTER '));
    }

    public function test_it_falls_back_to_before_for_null_empty_or_unknown_value(): void
    {
        self::assertSame(ActionIconPosition::Before, ActionIconPosition::fromNullableString(null));
        self::assertSame(ActionIconPosition::Before, ActionIconPosition::fromNullableString(''));
        self::assertSame(ActionIconPosition::Before, ActionIconPosition::fromNullableString('unknown'));
    }
}
