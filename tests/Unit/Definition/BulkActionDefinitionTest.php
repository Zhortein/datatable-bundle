<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Definition;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\BulkActionDefinition;
use Zhortein\DatatableBundle\Enum\ActionIconPosition;

final class BulkActionDefinitionTest extends TestCase
{
    public function test_it_stores_bulk_action_metadata(): void
    {
        $action = new BulkActionDefinition(
            name: 'delete',
            route: 'app_user_bulk_delete',
            label: 'Delete selected',
            icon: 'trash',
            iconPosition: ActionIconPosition::After,
            httpMethod: 'POST',
            confirmationMessage: 'Are you sure you want to delete selected users?',
            className: 'btn btn-sm btn-danger',
            routeParameters: ['foo' => 'bar'],
            attributes: ['data-test' => 'bulk-delete'],
            selectedRowsParameterName: 'user_ids',
        );

        self::assertSame('delete', $action->getName());
        self::assertSame('app_user_bulk_delete', $action->getRoute());
        self::assertSame('Delete selected', $action->getLabel());
        self::assertSame('trash', $action->getIcon());
        self::assertSame(ActionIconPosition::After, $action->getIconPosition());
        self::assertSame('POST', $action->getHttpMethod());
        self::assertSame('Are you sure you want to delete selected users?', $action->getConfirmationMessage());
        self::assertSame('btn btn-sm btn-danger', $action->getClassName());
        self::assertSame(['foo' => 'bar'], $action->getRouteParameters());
        self::assertSame(['data-test' => 'bulk-delete'], $action->getAttributes());
        self::assertSame('user_ids', $action->getSelectedRowsParameterName());
    }

    public function test_it_has_default_values(): void
    {
        $action = new BulkActionDefinition(
            name: 'delete',
            route: 'app_user_bulk_delete',
        );

        self::assertSame('POST', $action->getHttpMethod());
        self::assertSame(ActionIconPosition::Before, $action->getIconPosition());
        self::assertSame('ids', $action->getSelectedRowsParameterName());
    }
}
