<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional;

use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\Provider\DataProviderRegistry;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

final class HttpDataProviderFunctionalTest extends FunctionalTestCase
{
    public function test_it_loads_an_http_datatable_through_the_bundle_registry(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $factory = $container->get('test.'.DatatableDefinitionFactory::class);
        $registry = $container->get('test.'.DataProviderRegistry::class);

        self::assertInstanceOf(DatatableDefinitionFactory::class, $factory);
        self::assertInstanceOf(DataProviderRegistry::class, $registry);

        $definition = $factory->create('http-users');
        $result = $registry->resolve($definition)->getData(
            $definition,
            DatatableRequest::create(searchQuery: 'alice'),
        );

        self::assertSame([
            ['id' => 1, 'email' => 'alice@example.test'],
        ], $result->getRows());
        self::assertSame(1, $result->getTotalItems());
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }
}
