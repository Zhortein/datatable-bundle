<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Definition;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\ColumnDefinition;

final class ColumnDefinitionTest extends TestCase
{
    public function test_it_stores_column_metadata(): void
    {
        $column = new ColumnDefinition(
            name: 'e.email',
            label: 'Email',
            visible: true,
            sortable: true,
            searchable: true,
            className: 'text-start',
            template: 'email.html.twig',
            type: 'string',
            negate: true,
            exportable: false,
        );

        self::assertSame('e.email', $column->getName());
        self::assertSame('Email', $column->getLabel());
        self::assertTrue($column->isVisible());
        self::assertTrue($column->isSortable());
        self::assertTrue($column->isSearchable());
        self::assertSame('text-start', $column->getClassName());
        self::assertSame('email.html.twig', $column->getTemplate());
        self::assertSame('string', $column->getType());
        self::assertTrue($column->isNegated());
        self::assertFalse($column->getExportable());
    }

    public function test_boolean_negation_is_disabled_by_default(): void
    {
        $column = new ColumnDefinition(name: 'enabled', type: 'boolean');

        self::assertFalse($column->isNegated());
        self::assertNull($column->getExportable());
    }

    public function test_it_preserves_the_export_policy_when_changing_type(): void
    {
        $column = new ColumnDefinition(name: 'enabled', exportable: true);

        $typedColumn = $column->withType('boolean');

        self::assertSame('boolean', $typedColumn->getType());
        self::assertTrue($typedColumn->getExportable());
    }
}
