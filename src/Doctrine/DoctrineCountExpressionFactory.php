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
     * @param bool                  $joinsCollection whether a join follows a collection, repeating the main entity
     */
    public function create(
        DatatableDefinition $definition,
        ClassMetadata $metadata,
        bool $joinsCollection = false,
    ): string {
        if (!$this->requiresDistinctCount($definition, $joinsCollection)) {
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

    public function requiresDistinctCount(DatatableDefinition $definition, bool $joinsCollection = false): bool
    {
        return $joinsCollection
            || [] !== $definition->getAggregateColumns()
            || [] !== $definition->getCustomJoins();
    }
}
