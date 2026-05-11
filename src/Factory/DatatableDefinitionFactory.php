<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Factory;

use Zhortein\DatatableBundle\Definition\DatatableDefinition;
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

        return $definition;
    }
}
