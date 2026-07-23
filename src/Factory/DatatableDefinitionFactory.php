<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Factory;

use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Provider\DataProviderRegistry;
use Zhortein\DatatableBundle\Registry\DatatableRegistry;

final readonly class DatatableDefinitionFactory
{
    public function __construct(
        private DatatableRegistry $registry,
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

        return $definition;
    }
}
