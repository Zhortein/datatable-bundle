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
final class DatatableExportRoutesFunctionalTest extends FunctionalTestCase
{
    public function test_export_route_accepts_csv_and_xlsx_formats(): void
    {
        self::bootKernel();

        $router = self::getContainer()->get(RouterInterface::class);

        self::assertInstanceOf(RouterInterface::class, $router);

        self::assertSame(
            '/_zhortein/datatable/users/export',
            $router->generate('zhortein_datatable_export', [
                'name' => 'users',
                'format' => 'csv',
            ]),
        );

        self::assertSame(
            '/_zhortein/datatable/users/export/xlsx',
            $router->generate('zhortein_datatable_export', [
                'name' => 'users',
                'format' => 'xlsx',
            ]),
        );
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }
}
