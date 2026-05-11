<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Definition;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\ActionDefinition;

final class ActionDefinitionTest extends TestCase
{
    public function test_it_stores_action_metadata(): void
    {
        $action = new ActionDefinition(
            name: 'edit',
            route: 'app_user_edit',
            label: 'Edit',
            icon: 'pencil',
            httpMethod: 'GET',
            confirmationMessage: 'Are you sure?',
            className: 'btn btn-sm btn-primary',
            routeParameters: ['id' => 'id'],
            attributes: ['data-test' => 'edit-user'],
        );

        self::assertSame('edit', $action->getName());
        self::assertSame('app_user_edit', $action->getRoute());
        self::assertSame('Edit', $action->getLabel());
        self::assertSame('pencil', $action->getIcon());
        self::assertSame('GET', $action->getHttpMethod());
        self::assertSame('Are you sure?', $action->getConfirmationMessage());
        self::assertSame('btn btn-sm btn-primary', $action->getClassName());
        self::assertSame(['id' => 'id'], $action->getRouteParameters());
        self::assertSame(['data-test' => 'edit-user'], $action->getAttributes());
    }
}
