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
     * @param bool                  $joinsUnshownCollection whether a join follows a collection no column shows,
     *                                                      repeating the main entity
     */
    public function create(
        DatatableDefinition $definition,
        ClassMetadata $metadata,
        bool $joinsUnshownCollection = false,
    ): string {
        if (!$this->requiresDistinctCount($definition, $joinsUnshownCollection)) {
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

    public function requiresDistinctCount(DatatableDefinition $definition, bool $joinsUnshownCollection = false): bool
    {
        return $joinsUnshownCollection
            || [] !== $definition->getAggregateColumns()
            || [] !== $definition->getCustomJoins();
    }
}
