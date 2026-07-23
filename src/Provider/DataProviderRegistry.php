<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Provider;

use Zhortein\DatatableBundle\Contract\DataProviderInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Exception\DataProviderNotFoundException;
use Zhortein\DatatableBundle\Exception\DuplicateDataProviderException;
use Zhortein\DatatableBundle\Exception\UnsupportedDatatableDefinitionException;

final readonly class DataProviderRegistry
{
    public const string OPTION_PROVIDER = 'provider';

    /**
     * @var array<string, DataProviderInterface>
     */
    private array $providers;

    /**
     * @param iterable<string, DataProviderInterface> $providers
     */
    public function __construct(
        iterable $providers = [],
        private string $defaultProvider = 'doctrine',
    ) {
        $normalizedProviders = [];

        foreach ($providers as $name => $provider) {
            if ('' === trim($name)) {
                throw new DataProviderNotFoundException('A data provider cannot be registered with an empty name.');
            }

            if (isset($normalizedProviders[$name])) {
                throw new DuplicateDataProviderException(sprintf('A data provider named "%s" is already registered.', $name));
            }

            $normalizedProviders[$name] = $provider;
        }

        $this->providers = $normalizedProviders;
    }

    public function has(string $name): bool
    {
        return isset($this->providers[$name]);
    }

    public function get(string $name): DataProviderInterface
    {
        if (!$this->has($name)) {
            throw new DataProviderNotFoundException(sprintf('The data provider "%s" is not registered.', $name));
        }

        return $this->providers[$name];
    }

    public function resolve(DatatableDefinition $definition, ?string $name = null): DataProviderInterface
    {
        if (null === $name) {
            $configuredProvider = $definition->getOption(self::OPTION_PROVIDER);

            if (is_string($configuredProvider) && '' !== trim($configuredProvider)) {
                $name = trim($configuredProvider);
            }
        }

        if (null !== $name) {
            return $this->get($name);
        }

        if ($this->has($this->defaultProvider)) {
            $defaultProvider = $this->get($this->defaultProvider);

            if ($defaultProvider->supports($definition)) {
                return $defaultProvider;
            }
        }

        foreach ($this->providers as $provider) {
            if ($provider->supports($definition)) {
                return $provider;
            }
        }

        throw new UnsupportedDatatableDefinitionException(sprintf('No data provider supports the datatable "%s".', $definition->getName()));
    }

    /**
     * @return list<string>
     */
    public function getNames(): array
    {
        return array_keys($this->providers);
    }
}
