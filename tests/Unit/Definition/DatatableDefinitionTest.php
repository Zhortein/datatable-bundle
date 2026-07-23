<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Definition;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterOperator;

final class DatatableDefinitionTest extends TestCase
{
    public function test_it_stores_basic_datatable_metadata(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->setEntityClass(\stdClass::class)
            ->setTranslationDomain('user')
            ->addColumn('e.id', visible: false, sortable: false, searchable: false)
            ->addColumn('e.email', label: 'Email', className: 'text-start')
            ->addColumn('e.enabled', type: 'boolean', negate: true, exportable: false)
        ;

        self::assertSame('users', $definition->getName());
        self::assertSame(\stdClass::class, $definition->getEntityClass());
        self::assertSame('user', $definition->getTranslationDomain());

        $columns = $definition->getColumns();

        self::assertArrayHasKey('e.id', $columns);
        self::assertArrayHasKey('e.email', $columns);
        self::assertFalse($columns['e.id']->isVisible());
        self::assertFalse($columns['e.id']->isSortable());
        self::assertFalse($columns['e.id']->isSearchable());
        self::assertSame('Email', $columns['e.email']->getLabel());
        self::assertSame('text-start', $columns['e.email']->getClassName());
        self::assertTrue($columns['e.enabled']->isNegated());
        self::assertFalse($columns['e.enabled']->getExportable());
    }

    public function test_it_stores_row_and_global_actions(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addRowAction('view', route: 'app_user_show', label: 'View', routeParameters: ['id' => 'id'])
            ->addGlobalAction('create', route: 'app_user_create', label: 'Create')
        ;

        $rowActions = $definition->getRowActions();
        $globalActions = $definition->getGlobalActions();

        self::assertArrayHasKey('view', $rowActions);
        self::assertArrayHasKey('create', $globalActions);
        self::assertSame('app_user_show', $rowActions['view']->getRoute());
        self::assertSame(['id' => 'id'], $rowActions['view']->getRouteParameters());
        self::assertSame('app_user_create', $globalActions['create']->getRoute());
    }

    public function test_it_stores_permanent_filters(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addPermanentFilter('e.deletedAt', FilterOperator::IsNull)
            ->addPermanentFilter('e.status', FilterOperator::Equals, 'enabled')
        ;

        $filters = $definition->getPermanentFilters();

        self::assertCount(2, $filters);
        self::assertSame('e.deletedAt', $filters[0]->getField());
        self::assertSame(FilterOperator::IsNull, $filters[0]->getOperator());
        self::assertSame('e.status', $filters[1]->getField());
        self::assertSame(FilterOperator::Equals, $filters[1]->getOperator());
        self::assertSame('enabled', $filters[1]->getValue());
    }
}
