<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Doctrine;

use Doctrine\ORM\Mapping\ClassMetadata;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Provider\DoctrineOrmDataProvider;

final readonly class DoctrineCountExpressionFactory
{
    /**
     * @param ClassMetadata<object> $metadata
     */
    public function create(DatatableDefinition $definition, ClassMetadata $metadata): string
    {
        if (!$this->requiresDistinctCount($definition)) {
            return sprintf('COUNT(%s)', DoctrineOrmDataProvider::MAIN_ALIAS);
        }

        $identifierFieldNames = $metadata->getIdentifierFieldNames();

        if (1 !== count($identifierFieldNames)) {
            return sprintf('COUNT(DISTINCT %s)', DoctrineOrmDataProvider::MAIN_ALIAS);
        }

        return sprintf(
            'COUNT(DISTINCT %s.%s)',
            DoctrineOrmDataProvider::MAIN_ALIAS,
            $identifierFieldNames[0],
        );
    }

    public function requiresDistinctCount(DatatableDefinition $definition): bool
    {
        return [] !== $definition->getAggregateColumns()
            || [] !== $definition->getCustomJoins();
    }
}
