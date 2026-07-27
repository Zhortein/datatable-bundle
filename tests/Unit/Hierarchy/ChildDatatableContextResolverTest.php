<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Hierarchy;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Definition\ChildContextValue;
use Zhortein\DatatableBundle\Definition\ChildDatatableDefinition;
use Zhortein\DatatableBundle\Exception\InvalidChildDatatableContextValueException;
use Zhortein\DatatableBundle\Exception\MissingChildDatatableContextValueException;
use Zhortein\DatatableBundle\Hierarchy\ChildDatatableContextResolver;
use Zhortein\DatatableBundle\Hierarchy\RowValueAccessor;

final class ChildDatatableContextResolverTest extends TestCase
{
    public function test_it_resolves_row_context_and_literal_mappings_as_browser_safe_values(): void
    {
        $resolver = new ChildDatatableContextResolver(new RowValueAccessor());
        $definition = new ChildDatatableDefinition('order-lines', [
            'orderId' => ChildContextValue::row('o.id'),
            'locale' => ChildContextValue::context('locale'),
            'kind' => ChildContextValue::literal(ChildContextStatusFixture::Published),
            'reference' => ChildContextValue::row('metadata.reference'),
            'page' => ChildContextValue::literal(1),
            'empty' => ChildContextValue::literal(null),
        ]);

        $context = $resolver->resolve(
            $definition,
            [
                'o_id' => 42,
                'metadata' => new ChildContextMetadataFixture(
                    new ChildContextStringableFixture('order-42'),
                ),
            ],
            new DatatableContext(['locale' => 'fr', 'server' => new \stdClass()]),
        );

        self::assertSame([
            'orderId' => 42,
            'locale' => 'fr',
            'kind' => 'published',
            'reference' => 'order-42',
            'page' => 1,
            'empty' => null,
        ], $context->all());
        self::assertSame(array_keys($context->all()), $context->getBrowserSafeKeys());
        self::assertSame($context->all(), $context->getBrowserSafeValues());
    }

    public function test_it_applies_optional_and_defaulted_null_semantics(): void
    {
        $resolver = new ChildDatatableContextResolver(new RowValueAccessor());
        $definition = new ChildDatatableDefinition('order-lines', [
            'optionalRow' => ChildContextValue::optionalRow('missing'),
            'optionalContext' => ChildContextValue::optionalContext('nullable'),
            'defaultedRow' => ChildContextValue::rowOr('missing', 'fallback'),
            'defaultedContext' => ChildContextValue::contextOr('nullable', 'fr'),
            'omittedNullDefault' => ChildContextValue::contextOr('missing', null),
        ]);

        $context = $resolver->resolve(
            $definition,
            [],
            new DatatableContext(['nullable' => null]),
        );

        self::assertSame([
            'defaultedRow' => 'fallback',
            'defaultedContext' => 'fr',
        ], $context->all());
        self::assertSame(['defaultedRow', 'defaultedContext'], $context->getBrowserSafeKeys());
    }

    public function test_it_rejects_a_missing_required_row_value(): void
    {
        $resolver = new ChildDatatableContextResolver(new RowValueAccessor());
        $definition = new ChildDatatableDefinition('order-lines', [
            'orderId' => ChildContextValue::row('o.id'),
        ]);

        $this->expectException(MissingChildDatatableContextValueException::class);
        $this->expectExceptionMessage('Unable to resolve required context value "orderId" for child datatable "order-lines" from row source "o.id": the parent row value is missing.');

        $resolver->resolve($definition, [], new DatatableContext());
    }

    public function test_it_rejects_a_missing_required_parent_context_value(): void
    {
        $resolver = new ChildDatatableContextResolver(new RowValueAccessor());
        $definition = new ChildDatatableDefinition('order-lines', [
            'tenant' => ChildContextValue::context('tenant'),
        ]);

        $this->expectException(MissingChildDatatableContextValueException::class);
        $this->expectExceptionMessage('Unable to resolve required context value "tenant" for child datatable "order-lines" from context source "tenant": the parent context key is missing.');

        $resolver->resolve($definition, [], new DatatableContext());
    }

    public function test_it_rejects_a_non_transportable_resolved_value(): void
    {
        $resolver = new ChildDatatableContextResolver(new RowValueAccessor());
        $definition = new ChildDatatableDefinition('order-lines', [
            'metadata' => ChildContextValue::row('metadata'),
        ]);

        $this->expectException(InvalidChildDatatableContextValueException::class);
        $this->expectExceptionMessage('Unable to resolve context value "metadata" for child datatable "order-lines" from row source "metadata": values of type "array" are not transportable.');

        $resolver->resolve($definition, ['metadata' => ['private']], new DatatableContext());
    }
}

enum ChildContextStatusFixture: string
{
    case Published = 'published';
}

final readonly class ChildContextMetadataFixture
{
    public function __construct(
        private ChildContextStringableFixture $reference,
    ) {
    }

    public function getReference(): ChildContextStringableFixture
    {
        return $this->reference;
    }
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
