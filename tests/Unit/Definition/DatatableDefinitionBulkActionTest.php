<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Definition;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\AjaxActionOptions;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\AjaxActionSuccessStrategy;

final class DatatableDefinitionBulkActionTest extends TestCase
{
    public function test_it_stores_bulk_actions(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addBulkAction(
                'delete',
                route: 'app_user_bulk_delete',
                label: 'Delete selected',
                permission: 'USER_BULK_DELETE',
                ajax: new AjaxActionOptions(AjaxActionSuccessStrategy::RemoveRow),
            )
            ->addBulkAction('activate', route: 'app_user_bulk_activate', label: 'Activate selected', selectedRowsParameterName: 'uids')
        ;

        $bulkActions = $definition->getBulkActions();

        self::assertCount(2, $bulkActions);
        self::assertArrayHasKey('delete', $bulkActions);
        self::assertArrayHasKey('activate', $bulkActions);

        self::assertSame('app_user_bulk_delete', $bulkActions['delete']->getRoute());
        self::assertSame('ids', $bulkActions['delete']->getSelectedRowsParameterName());
        self::assertSame('USER_BULK_DELETE', $bulkActions['delete']->getPermission());
        self::assertSame(AjaxActionSuccessStrategy::RemoveRow, $bulkActions['delete']->getAjaxOptions()?->getSuccessStrategy());

        self::assertSame('app_user_bulk_activate', $bulkActions['activate']->getRoute());
        self::assertSame('uids', $bulkActions['activate']->getSelectedRowsParameterName());
    }
}
