<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Definition;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\AggregateColumnDefinition;
use Zhortein\DatatableBundle\Enum\AggregateFunction;

final class AggregateColumnDefinitionTest extends TestCase
{
    public function test_it_stores_aggregate_column_metadata(): void
    {
        $definition = new AggregateColumnDefinition(
            name: 'auditCount',
            field: 'audit.id',
            function: AggregateFunction::Count,
            distinct: true,
        );

        self::assertSame('auditCount', $definition->getName());
        self::assertSame('audit.id', $definition->getField());
        self::assertSame(AggregateFunction::Count, $definition->getFunction());
        self::assertTrue($definition->isDistinct());
    }

    public function test_it_rejects_empty_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The aggregate column name cannot be empty.');

        new AggregateColumnDefinition('', 'audit.id');
    }

    public function test_it_rejects_empty_field(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The aggregate column field cannot be empty.');

        new AggregateColumnDefinition('auditCount', '');
    }
}
