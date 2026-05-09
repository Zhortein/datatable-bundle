<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;
use Zhortein\DatatableBundle\Provider\DataProviderRegistry;

final class DataProviderRegistryTaggedIteratorTest extends TestCase
{
    public function test_it_accepts_named_iterable_providers(): void
    {
        $provider = new ArrayDataProvider();

        $registry = new DataProviderRegistry([
            ArrayDataProvider::PROVIDER_NAME => $provider,
        ]);

        self::assertSame([ArrayDataProvider::PROVIDER_NAME], $registry->getNames());
        self::assertSame($provider, $registry->get(ArrayDataProvider::PROVIDER_NAME));
    }
}
