<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Enum\CellType;

final class CellTypeTest extends TestCase
{
    public function test_it_creates_cell_type_from_valid_string(): void
    {
        self::assertSame(CellType::String, CellType::fromNullableString('string'));
        self::assertSame(CellType::Numeric, CellType::fromNullableString('numeric'));
        self::assertSame(CellType::Boolean, CellType::fromNullableString('boolean'));
        self::assertSame(CellType::Datetime, CellType::fromNullableString('datetime'));
        self::assertSame(CellType::Array, CellType::fromNullableString('array'));
        self::assertSame(CellType::Enum, CellType::fromNullableString('enum'));
        self::assertSame(CellType::Default, CellType::fromNullableString('default'));
    }

    public function test_it_normalizes_input(): void
    {
        self::assertSame(CellType::String, CellType::fromNullableString(' STRING '));
        self::assertSame(CellType::Datetime, CellType::fromNullableString(' DATETIME '));
    }

    public function test_it_falls_back_to_default_for_null_empty_or_unknown_type(): void
    {
        self::assertSame(CellType::Default, CellType::fromNullableString(null));
        self::assertSame(CellType::Default, CellType::fromNullableString(''));
        self::assertSame(CellType::Default, CellType::fromNullableString('   '));
        self::assertSame(CellType::Default, CellType::fromNullableString('unknown'));
    }

    public function test_it_returns_template_name(): void
    {
        self::assertSame('string', CellType::String->getTemplateName());
        self::assertSame('numeric', CellType::Numeric->getTemplateName());
        self::assertSame('boolean', CellType::Boolean->getTemplateName());
        self::assertSame('datetime', CellType::Datetime->getTemplateName());
        self::assertSame('array', CellType::Array->getTemplateName());
        self::assertSame('enum', CellType::Enum->getTemplateName());
        self::assertSame('default', CellType::Default->getTemplateName());
    }
}
