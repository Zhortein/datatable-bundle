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

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }
}
