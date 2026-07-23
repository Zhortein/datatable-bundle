<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Contract\DataProviderInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;
use Zhortein\DatatableBundle\Provider\DataProviderRegistry;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Result\DatatableResult;

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

    public function test_it_resolves_the_provider_declared_on_the_definition(): void
    {
        $primary = new TaggedIteratorTestProvider();
        $secondary = new TaggedIteratorTestProvider();
        $registry = new DataProviderRegistry(
            providers: [
                'primary' => $primary,
                'secondary' => $secondary,
            ],
            defaultProvider: 'secondary',
        );
        $definition = new DatatableDefinition('users');
        $definition->setOption(DataProviderRegistry::OPTION_PROVIDER, 'primary');

        self::assertSame($primary, $registry->resolve($definition));
    }

    public function test_it_prefers_the_default_provider_when_it_supports_the_definition(): void
    {
        $primary = new TaggedIteratorTestProvider();
        $secondary = new TaggedIteratorTestProvider();
        $registry = new DataProviderRegistry(
            providers: [
                'primary' => $primary,
                'secondary' => $secondary,
            ],
            defaultProvider: 'secondary',
        );

        self::assertSame(
            $secondary,
            $registry->resolve(new DatatableDefinition('users')),
        );
    }

    public function test_it_falls_back_to_a_compatible_provider_when_the_default_is_unavailable(): void
    {
        $provider = new ArrayDataProvider();
        $registry = new DataProviderRegistry(
            providers: [ArrayDataProvider::PROVIDER_NAME => $provider],
            defaultProvider: 'doctrine',
        );
        $definition = new DatatableDefinition('users');
        $definition->setOption(ArrayDataProvider::OPTION_ROWS, []);

        self::assertSame($provider, $registry->resolve($definition));
    }
}

final class TaggedIteratorTestProvider implements DataProviderInterface
{
    public function supports(DatatableDefinition $definition): bool
    {
        return true;
    }

    public function getData(DatatableDefinition $definition, DatatableRequest $request): DatatableResult
    {
        return new DatatableResult();
    }
}
