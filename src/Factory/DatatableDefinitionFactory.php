<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Factory;

use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Doctrine\DoctrineDatatableDefinitionEnricher;
use Zhortein\DatatableBundle\Exception\DataProviderException;
use Zhortein\DatatableBundle\Provider\DataProviderRegistry;
use Zhortein\DatatableBundle\Provider\DoctrineOrmDataProvider;
use Zhortein\DatatableBundle\Registry\DatatableRegistry;

final readonly class DatatableDefinitionFactory
{
    public function __construct(
        private DatatableRegistry $registry,
        private ?DoctrineDatatableDefinitionEnricher $doctrineDefinitionEnricher = null,
        private ?DataProviderRegistry $dataProviderRegistry = null,
        private string $defaultProvider = DoctrineOrmDataProvider::PROVIDER_NAME,
    ) {
    }

    public function create(string $name): DatatableDefinition
    {
        $datatable = $this->registry->get($name);
        $definition = new DatatableDefinition($name);

        $datatable->buildDatatable($definition);

        $providerName = $this->registry->getProviderName($name);

        if (null !== $providerName) {
            $definition->setOption(DataProviderRegistry::OPTION_PROVIDER, $providerName);
        }

        if (
            null !== $this->doctrineDefinitionEnricher
            && $this->usesDoctrineProvider($definition)
        ) {
            $this->doctrineDefinitionEnricher->enrich($definition);
        }

        return $definition;
    }

    private function usesDoctrineProvider(DatatableDefinition $definition): bool
    {
        if (null !== $this->dataProviderRegistry) {
            try {
                return $this->dataProviderRegistry->resolve($definition) instanceof DoctrineOrmDataProvider;
            } catch (DataProviderException) {
                // Provider errors remain handled by the normal request flow.
            }
        }

        $configuredProvider = $definition->getOption(DataProviderRegistry::OPTION_PROVIDER);
        $providerName = is_string($configuredProvider) && '' !== trim($configuredProvider)
            ? trim($configuredProvider)
            : $this->defaultProvider;

        return DoctrineOrmDataProvider::PROVIDER_NAME === $providerName;
    }
}
