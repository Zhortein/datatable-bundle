<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Definition;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\AggregateFunction;

final class DatatableDefinitionAggregateColumnTest extends TestCase
{
    public function test_it_declares_aggregate_column_and_matching_display_column(): void
    {
        $definition = new DatatableDefinition('users');

        $definition->addAggregateColumn(
            name: 'auditCount',
            field: 'audit.id',
            function: AggregateFunction::Count,
            label: 'Audit count',
            distinct: true,
        );

        self::assertArrayHasKey('auditCount', $definition->getColumns());
        self::assertArrayHasKey('auditCount', $definition->getAggregateColumns());

        $column = $definition->getColumns()['auditCount'];
        $aggregate = $definition->getAggregateColumns()['auditCount'];

        self::assertSame('Audit count', $column->getLabel());
        self::assertSame('numeric', $column->getType());
        self::assertFalse($column->isSearchable());
        self::assertFalse($column->isSortable());

        self::assertSame('audit.id', $aggregate->getField());
        self::assertSame(AggregateFunction::Count, $aggregate->getFunction());
        self::assertTrue($aggregate->isDistinct());
    }
}
