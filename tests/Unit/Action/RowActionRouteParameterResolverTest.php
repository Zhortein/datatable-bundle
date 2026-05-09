<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Action;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Action\RowActionRouteParameterResolver;
use Zhortein\DatatableBundle\Definition\ActionDefinition;
use Zhortein\DatatableBundle\Exception\MissingRouteParameterValueException;

final class RowActionRouteParameterResolverTest extends TestCase
{
    public function test_it_resolves_direct_row_keys(): void
    {
        $resolver = new RowActionRouteParameterResolver();

        $action = new ActionDefinition(
            name: 'view',
            route: 'app_user_show',
            routeParameters: [
                'id' => 'id',
            ],
        );

        self::assertSame([
            'id' => 42,
        ], $resolver->resolve($action, [
            'id' => 42,
        ]));
    }

    public function test_it_resolves_aliased_row_keys(): void
    {
        $resolver = new RowActionRouteParameterResolver();

        $action = new ActionDefinition(
            name: 'view',
            route: 'app_user_show',
            routeParameters: [
                'id' => 'e_id',
            ],
        );

        self::assertSame([
            'id' => 42,
        ], $resolver->resolve($action, [
            'e_id' => 42,
        ]));
    }

    public function test_it_resolves_doctrine_dot_notation_from_aliased_row_key(): void
    {
        $resolver = new RowActionRouteParameterResolver();

        $action = new ActionDefinition(
            name: 'view',
            route: 'app_user_show',
            routeParameters: [
                'id' => 'e.id',
            ],
        );

        self::assertSame([
            'id' => 42,
        ], $resolver->resolve($action, [
            'e_id' => 42,
        ]));
    }

    public function test_it_resolves_doctrine_dot_notation_from_direct_last_segment(): void
    {
        $resolver = new RowActionRouteParameterResolver();

        $action = new ActionDefinition(
            name: 'view',
            route: 'app_user_show',
            routeParameters: [
                'id' => 'e.id',
            ],
        );

        self::assertSame([
            'id' => 42,
        ], $resolver->resolve($action, [
            'id' => 42,
        ]));
    }

    public function test_it_resolves_multiple_parameters(): void
    {
        $resolver = new RowActionRouteParameterResolver();

        $action = new ActionDefinition(
            name: 'view',
            route: 'app_order_line_show',
            routeParameters: [
                'orderId' => 'order_id',
                'lineId' => 'line_id',
            ],
        );

        self::assertSame([
            'orderId' => 10,
            'lineId' => 20,
        ], $resolver->resolve($action, [
            'order_id' => 10,
            'line_id' => 20,
        ]));
    }

    public function test_it_returns_empty_parameters_when_action_has_no_route_parameters(): void
    {
        $resolver = new RowActionRouteParameterResolver();

        $action = new ActionDefinition(
            name: 'create',
            route: 'app_user_create',
        );

        self::assertSame([], $resolver->resolve($action, [
            'id' => 42,
        ]));
    }

    public function test_it_throws_when_row_value_is_missing(): void
    {
        $resolver = new RowActionRouteParameterResolver();

        $action = new ActionDefinition(
            name: 'view',
            route: 'app_user_show',
            routeParameters: [
                'id' => 'e.id',
            ],
        );

        $this->expectException(MissingRouteParameterValueException::class);
        $this->expectExceptionMessage('Unable to resolve route parameter "id" for row action "view" from row key "e.id".');

        $resolver->resolve($action, [
            'email' => 'alice@example.test',
        ]);
    }
}
