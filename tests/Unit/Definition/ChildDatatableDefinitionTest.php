<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Definition;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\ChildContextValue;
use Zhortein\DatatableBundle\Definition\ChildDatatableDefinition;

final class ChildDatatableDefinitionTest extends TestCase
{
    public function test_it_stores_a_normalized_child_definition(): void
    {
        $definition = new ChildDatatableDefinition(
            name: ' order-lines ',
            context: [
                ' orderId ' => ChildContextValue::row('id'),
                'tenant' => ChildContextValue::context('tenant'),
            ],
            expandLabel: 'Show order lines',
            collapseLabel: 'Hide order lines',
            maxDepth: 2,
        );

        self::assertSame('order-lines', $definition->getName());
        self::assertSame(['orderId', 'tenant'], array_keys($definition->getContext()));
        self::assertSame('Show order lines', $definition->getExpandLabel());
        self::assertSame('Hide order lines', $definition->getCollapseLabel());
        self::assertSame(2, $definition->getMaxDepth());
    }

    #[DataProvider('invalidNameProvider')]
    public function test_it_rejects_invalid_names(string $name): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A child datatable name must be at most 128 characters');

        new ChildDatatableDefinition($name);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidNameProvider(): iterable
    {
        yield 'empty' => [' '];
        yield 'leading punctuation' => ['-children'];
        yield 'slash' => ['parent/children'];
        yield 'whitespace' => ['child table'];
        yield 'control character' => ["child\nrows"];
        yield 'too long' => [str_repeat('a', ChildDatatableDefinition::MAX_NAME_LENGTH + 1)];
    }

    #[DataProvider('invalidDepthProvider')]
    public function test_it_rejects_invalid_maximum_depths(int $maxDepth): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A child datatable maximum depth must be between 1 and 3.');

        new ChildDatatableDefinition('children', maxDepth: $maxDepth);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function invalidDepthProvider(): iterable
    {
        yield 'zero' => [0];
        yield 'above hard limit' => [4];
    }

    public function test_it_rejects_empty_context_keys(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A child datatable context key must be a non-empty string of at most 128 characters without control characters.');

        new ChildDatatableDefinition('children', [
            ' ' => ChildContextValue::row('id'),
        ]);
    }

    public function test_it_rejects_context_keys_with_control_characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A child datatable context key must be a non-empty string of at most 128 characters without control characters.');

        new ChildDatatableDefinition('children', [
            "parent\nId" => ChildContextValue::row('id'),
        ]);
    }

    public function test_it_rejects_duplicate_normalized_context_keys(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The child datatable context key "parentId" is declared more than once.');

        new ChildDatatableDefinition('children', [
            'parentId' => ChildContextValue::row('id'),
            ' parentId ' => ChildContextValue::row('uuid'),
        ]);
    }

    public function test_it_rejects_invalid_context_value_objects(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('A child datatable context value must be an instance of "%s"; "string" given.', ChildContextValue::class));

        new ChildDatatableDefinition('children', ['parentId' => 'id']);
    }

    public function test_it_rejects_more_context_values_than_the_transport_limit(): void
    {
        $context = [];

        for ($index = 0; $index <= ChildDatatableDefinition::MAX_CONTEXT_VALUES; ++$index) {
            $context['value'.$index] = ChildContextValue::literal($index);
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A child datatable cannot propagate more than 64 context values.');

        new ChildDatatableDefinition('children', $context);
    }
}
