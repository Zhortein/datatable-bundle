<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Definition;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\ChildContextValue;
use Zhortein\DatatableBundle\Enum\ChildContextSource;

final class ChildContextValueTest extends TestCase
{
    public function test_it_describes_required_optional_and_defaulted_sources(): void
    {
        $requiredRow = ChildContextValue::row('order.id');
        $optionalContext = ChildContextValue::optionalContext('tenant');
        $defaultedRow = ChildContextValue::rowOr('customer.id', 42);
        $literal = ChildContextValue::literal('details');

        self::assertSame(ChildContextSource::Row, $requiredRow->getSource());
        self::assertSame('order.id', $requiredRow->getKey());
        self::assertTrue($requiredRow->isRequired());
        self::assertFalse($requiredRow->hasDefault());

        self::assertSame(ChildContextSource::Context, $optionalContext->getSource());
        self::assertSame('tenant', $optionalContext->getKey());
        self::assertFalse($optionalContext->isRequired());

        self::assertSame(42, $defaultedRow->getDefaultValue());
        self::assertTrue($defaultedRow->hasDefault());
        self::assertFalse($defaultedRow->isRequired());

        self::assertSame(ChildContextSource::Literal, $literal->getSource());
        self::assertSame('details', $literal->getValue());
        self::assertNull($literal->getKey());
    }

    public function test_it_trims_referenced_source_keys(): void
    {
        self::assertSame('parent.id', ChildContextValue::row(' parent.id ')->getKey());
        self::assertSame('tenant', ChildContextValue::context(' tenant ')->getKey());
        self::assertSame('fallback', ChildContextValue::contextOr(' locale ', 'fallback')->getDefaultValue());
        self::assertFalse(ChildContextValue::optionalRow(' parent.uuid ')->isRequired());
    }

    public function test_it_rejects_empty_referenced_source_keys(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A child datatable row context source key must not be empty.');

        ChildContextValue::row(' ');
    }

    public function test_it_accepts_transportable_literal_and_default_values(): void
    {
        $enumValue = ChildContextValue::literal(ChildContextStatusFixture::Active);
        $stringableValue = ChildContextValue::contextOr('tenant', new ChildContextStringableFixture('acme'));

        self::assertSame(ChildContextStatusFixture::Active, $enumValue->getValue());
        self::assertInstanceOf(ChildContextStringableFixture::class, $stringableValue->getDefaultValue());
    }

    public function test_it_rejects_non_transportable_literal_values(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A child datatable context literal value must be scalar, null, a backed enum or Stringable; "array" given.');

        ChildContextValue::literal(['forbidden']);
    }

    public function test_it_rejects_non_transportable_default_values(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A child datatable context default value must be scalar, null, a backed enum or Stringable; "stdClass" given.');

        ChildContextValue::rowOr('id', new \stdClass());
    }
}

enum ChildContextStatusFixture: string
{
    case Active = 'active';
}

final readonly class ChildContextStringableFixture implements \Stringable
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
