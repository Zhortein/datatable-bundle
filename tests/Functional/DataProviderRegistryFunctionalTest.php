<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;
use Zhortein\DatatableBundle\Provider\DataProviderRegistry;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class DataProviderRegistryFunctionalTest extends FunctionalTestCase
{
    public function test_it_registers_array_data_provider_in_container(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        self::assertTrue($container->has('test.'.DataProviderRegistry::class));

        $registry = $container->get('test.'.DataProviderRegistry::class);

        self::assertInstanceOf(DataProviderRegistry::class, $registry);
        self::assertTrue($registry->has(ArrayDataProvider::PROVIDER_NAME));
        self::assertInstanceOf(ArrayDataProvider::class, $registry->get(ArrayDataProvider::PROVIDER_NAME));
    }

    public function test_it_resolves_array_provider_for_supported_definition(): void
    {
        self::bootKernel();

        $registry = self::getContainer()->get('test.'.DataProviderRegistry::class);

        self::assertInstanceOf(DataProviderRegistry::class, $registry);

        $definition = new DatatableDefinition('users');
        $definition->setOption(ArrayDataProvider::OPTION_ROWS, []);

        self::assertInstanceOf(ArrayDataProvider::class, $registry->resolve($definition));
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }
}
