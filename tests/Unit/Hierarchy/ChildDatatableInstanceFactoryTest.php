<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Hierarchy;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Hierarchy\ChildDatatableInstanceFactory;

final class ChildDatatableInstanceFactoryTest extends TestCase
{
    public function test_it_creates_a_stable_opaque_instance_and_parses_its_depth(): void
    {
        $factory = new ChildDatatableInstanceFactory();
        $instance = $factory->create('orders', 'orders-table', 'order-lines', 42, 2);

        self::assertSame($instance, $factory->create('orders', 'orders-table', 'order-lines', 42, 2));
        self::assertMatchesRegularExpression('/^zd-child-d2-[A-Za-z0-9_-]{43}$/D', $instance);
        self::assertTrue($factory->isChildInstance($instance));
        self::assertFalse($factory->isChildInstance('orders-table'));
        self::assertSame(2, $factory->parseDepth($instance));
    }

    public function test_it_isolates_instances_by_every_parent_coordinate(): void
    {
        $factory = new ChildDatatableInstanceFactory();
        $baseline = $factory->create('orders', 'orders-table', 'order-lines', 42, 1);

        self::assertNotSame($baseline, $factory->create('archived-orders', 'orders-table', 'order-lines', 42, 1));
        self::assertNotSame($baseline, $factory->create('orders', 'other-table', 'order-lines', 42, 1));
        self::assertNotSame($baseline, $factory->create('orders', 'orders-table', 'shipments', 42, 1));
        self::assertNotSame($baseline, $factory->create('orders', 'orders-table', 'order-lines', 43, 1));
        self::assertNotSame($baseline, $factory->create('orders', 'orders-table', 'order-lines', 42, 2));
        self::assertNotSame($baseline, $factory->create('orders', 'orders-table', 'order-lines', '42', 1));
    }

    public function test_it_normalizes_backed_enum_and_stringable_identifiers(): void
    {
        $factory = new ChildDatatableInstanceFactory();

        self::assertSame(
            $factory->create('orders', 'orders-table', 'order-lines', 'published', 1),
            $factory->create('orders', 'orders-table', 'order-lines', ChildInstanceStatusFixture::Published, 1),
        );
        self::assertSame(
            $factory->create('orders', 'orders-table', 'order-lines', 'order-42', 1),
            $factory->create('orders', 'orders-table', 'order-lines', new ChildInstanceStringableFixture('order-42'), 1),
        );
    }

    #[DataProvider('invalidRowIdentifierProvider')]
    public function test_it_rejects_invalid_row_identifiers(mixed $identifier): void
    {
        $factory = new ChildDatatableInstanceFactory();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A child datatable parent row identifier must be a non-empty scalar');

        $factory->create('orders', 'orders-table', 'order-lines', $identifier, 1);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function invalidRowIdentifierProvider(): iterable
    {
        yield 'null' => [null];
        yield 'empty string' => [' '];
        yield 'array' => [[42]];
        yield 'object' => [new \stdClass()];
    }

    #[DataProvider('invalidDepthProvider')]
    public function test_it_rejects_invalid_depths(int $depth): void
    {
        $factory = new ChildDatatableInstanceFactory();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A child datatable depth must be between 1 and 3.');

        $factory->create('orders', 'orders-table', 'order-lines', 42, $depth);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function invalidDepthProvider(): iterable
    {
        yield 'zero' => [0];
        yield 'above maximum' => [4];
    }

    public function test_it_rejects_an_instance_that_does_not_match_the_reserved_format(): void
    {
        $factory = new ChildDatatableInstanceFactory();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The child datatable instance key is invalid.');

        $factory->parseDepth('orders-table');
    }
}

enum ChildInstanceStatusFixture: string
{
    case Published = 'published';
}

final readonly class ChildInstanceStringableFixture implements \Stringable
{
    public function __construct(
        private string $value,
    ) {
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
