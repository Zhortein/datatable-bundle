<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Factory;

use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Doctrine\DoctrineDatatableDefinitionEnricher;
use Zhortein\DatatableBundle\Exception\DataProviderException;
use Zhortein\DatatableBundle\Exception\InvalidDatatableException;
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
        $this->assertDefaultSortsAreSortable($definition);

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

    /**
     * A request drops a sort on a column it cannot sort, so a mistyped default order would leave the list unsorted
     * without a word.
     */
    private function assertDefaultSortsAreSortable(DatatableDefinition $definition): void
    {
        $columns = $definition->getColumns();

        foreach ($definition->getDefaultSorts() as $sort) {
            if (isset($columns[$sort->getField()]) && $columns[$sort->getField()]->isSortable()) {
                continue;
            }

            $message = sprintf(
                'The datatable "%s" sorts by default on "%s", which is not a sortable column.',
                $definition->getName(),
                $sort->getField(),
            );

            throw new InvalidDatatableException($message);
        }
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
