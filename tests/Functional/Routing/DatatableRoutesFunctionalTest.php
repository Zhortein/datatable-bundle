<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Routing;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Routing\RouterInterface;
use Zhortein\DatatableBundle\Tests\Functional\FunctionalTestCase;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class DatatableRoutesFunctionalTest extends FunctionalTestCase
{
    public function test_fragments_route_is_registered(): void
    {
        self::bootKernel();

        $router = self::getContainer()->get(RouterInterface::class);

        self::assertInstanceOf(RouterInterface::class, $router);

        $route = $router->getRouteCollection()->get('zhortein_datatable_fragments');

        self::assertNotNull($route);
        self::assertSame('/_zhortein/datatable/{name}/fragments', $route->getPath());
        self::assertSame(['GET', 'POST'], $route->getMethods());
    }

    public function test_fragments_route_can_be_generated(): void
    {
        self::bootKernel();

        $router = self::getContainer()->get(RouterInterface::class);

        self::assertInstanceOf(RouterInterface::class, $router);

        self::assertSame(
            '/_zhortein/datatable/users/fragments',
            $router->generate('zhortein_datatable_fragments', [
                'name' => 'users',
            ]),
        );
    }

    public function test_export_route_is_registered(): void
    {
        self::bootKernel();

        $router = self::getContainer()->get(RouterInterface::class);

        self::assertInstanceOf(RouterInterface::class, $router);

        $route = $router->getRouteCollection()->get('zhortein_datatable_export');

        self::assertNotNull($route);
        self::assertSame('/_zhortein/datatable/{name}/export/{format}', $route->getPath());
        self::assertSame(['GET', 'POST'], $route->getMethods());
    }

    public function test_export_route_can_be_generated(): void
    {
        self::bootKernel();

        $router = self::getContainer()->get(RouterInterface::class);

        self::assertInstanceOf(RouterInterface::class, $router);

        // Symfony omits optional route parameters when they match their default value.
        self::assertSame(
            '/_zhortein/datatable/users/export',
            $router->generate('zhortein_datatable_export', [
                'name' => 'users',
                'format' => 'csv',
            ]),
        );
    }

    public function test_named_view_routes_are_registered(): void
    {
        self::bootKernel();

        $router = self::getContainer()->get(RouterInterface::class);

        self::assertInstanceOf(RouterInterface::class, $router);

        $routes = $router->getRouteCollection();

        self::assertSame(
            ['GET'],
            $routes->get('zhortein_datatable_views_list')?->getMethods(),
        );
        self::assertSame(
            ['POST'],
            $routes->get('zhortein_datatable_views_create')?->getMethods(),
        );
        self::assertSame(
            ['GET'],
            $routes->get('zhortein_datatable_views_load')?->getMethods(),
        );
        self::assertSame(
            ['PATCH'],
            $routes->get('zhortein_datatable_views_mutate')?->getMethods(),
        );
        self::assertSame(
            ['DELETE'],
            $routes->get('zhortein_datatable_views_delete')?->getMethods(),
        );
        self::assertSame(
            '/_zhortein/datatable/{name}/views/{viewIdentifier}',
            $routes->get('zhortein_datatable_views_load')->getPath(),
        );
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }
}
