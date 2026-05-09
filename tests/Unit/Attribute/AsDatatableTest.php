<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Attribute;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Attribute\AsDatatable;

final class AsDatatableTest extends TestCase
{
    public function test_it_stores_attribute_options(): void
    {
        $attribute = new AsDatatable(
            name: 'users',
            label: 'Users',
            provider: 'doctrine',
        );

        self::assertSame('users', $attribute->name);
        self::assertSame('Users', $attribute->label);
        self::assertSame('doctrine', $attribute->provider);
    }

    public function test_it_accepts_empty_options(): void
    {
        $attribute = new AsDatatable();

        self::assertNull($attribute->name);
        self::assertNull($attribute->label);
        self::assertNull($attribute->provider);
    }
}
