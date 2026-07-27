<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Cell;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Cell\CellContext;
use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Definition\ColumnDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;

final class CellContextTest extends TestCase
{
    public function test_it_exposes_complete_server_side_context(): void
    {
        $column = new ColumnDefinition('display_name', label: 'Display name');
        $definition = new DatatableDefinition('users');
        $datatableContext = new DatatableContext(['scope' => 'admin']);
        $source = new \stdClass();
        $row = ['id' => 12, 'display_name' => 'Alice'];

        $context = new CellContext(
            value: 'Alice',
            row: $row,
            source: $source,
            rowIdentifier: '12',
            column: $column,
            definition: $definition,
            datatableContext: $datatableContext,
        );

        self::assertSame('Alice', $context->getValue());
        self::assertSame($row, $context->getRow());
        self::assertSame($source, $context->getSource());
        self::assertTrue($context->hasSource());
        self::assertSame('12', $context->getRowIdentifier());
        self::assertSame($column, $context->getColumn());
        self::assertSame($definition, $context->getDefinition());
        self::assertSame($datatableContext, $context->getDatatableContext());

        $resolvedContext = $context->withValue('ALICE');

        self::assertSame('ALICE', $resolvedContext->getValue());
        self::assertSame($source, $resolvedContext->getSource());
        self::assertSame($row, $resolvedContext->getRow());
    }
}
