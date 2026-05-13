<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Enum\FilterLayout;

final class FilterLayoutTest extends TestCase
{
    public function test_it_creates_filter_layout_from_valid_string(): void
    {
        self::assertSame(FilterLayout::Toolbar, FilterLayout::fromNullableString('toolbar'));
        self::assertSame(FilterLayout::Header, FilterLayout::fromNullableString('header'));
        self::assertSame(FilterLayout::None, FilterLayout::fromNullableString('none'));
        self::assertSame(FilterLayout::Header, FilterLayout::fromNullableString(' HEADER '));
    }

    public function test_it_falls_back_to_toolbar_for_null_empty_or_unknown_value(): void
    {
        self::assertSame(FilterLayout::Toolbar, FilterLayout::fromNullableString(null));
        self::assertSame(FilterLayout::Toolbar, FilterLayout::fromNullableString(''));
        self::assertSame(FilterLayout::Toolbar, FilterLayout::fromNullableString('unknown'));
    }

    public function test_it_exposes_rendering_capabilities(): void
    {
        self::assertTrue(FilterLayout::Toolbar->rendersFilters());
        self::assertTrue(FilterLayout::Toolbar->rendersToolbarFilters());
        self::assertFalse(FilterLayout::Toolbar->rendersHeaderFilters());

        self::assertTrue(FilterLayout::Header->rendersFilters());
        self::assertFalse(FilterLayout::Header->rendersToolbarFilters());
        self::assertTrue(FilterLayout::Header->rendersHeaderFilters());

        self::assertFalse(FilterLayout::None->rendersFilters());
        self::assertFalse(FilterLayout::None->rendersToolbarFilters());
        self::assertFalse(FilterLayout::None->rendersHeaderFilters());
    }
}
