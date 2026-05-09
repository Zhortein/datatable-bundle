<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional;

use Zhortein\DatatableBundle\Registry\DatatableRegistry;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Datatable\FunctionalUserDatatable;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

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

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }
}
