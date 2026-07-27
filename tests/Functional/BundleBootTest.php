<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Zhortein\DatatableBundle\Cell\CellValueResolverRegistry;
use Zhortein\DatatableBundle\Registry\DatatableRegistry;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Cell\FunctionalCellValueResolver;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Datatable\FunctionalUserDatatable;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class BundleBootTest extends FunctionalTestCase
{
    public function test_it_boots_bundle_and_registers_datatable_services(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        self::assertTrue($container->has(DatatableRegistry::class));

        $registry = $container->get(DatatableRegistry::class);

        self::assertInstanceOf(DatatableRegistry::class, $registry);
        self::assertTrue($registry->has('functional-users'));
        self::assertInstanceOf(FunctionalUserDatatable::class, $registry->get('functional-users'));
    }

    public function test_it_autoconfigures_cell_value_resolvers(): void
    {
        self::bootKernel();

        $registry = self::getContainer()->get(CellValueResolverRegistry::class);

        self::assertInstanceOf(CellValueResolverRegistry::class, $registry);
        self::assertTrue($registry->has('functional_cell'));
        self::assertInstanceOf(FunctionalCellValueResolver::class, $registry->get('functional_cell'));
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }
}
