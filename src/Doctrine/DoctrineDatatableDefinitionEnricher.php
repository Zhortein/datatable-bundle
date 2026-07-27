<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Doctrine;

use Zhortein\DatatableBundle\Definition\DatatableDefinition;

final readonly class DoctrineDatatableDefinitionEnricher
{
    public function __construct(
        private DoctrineFieldTypeGuesser $fieldTypeGuesser,
    ) {
    }

    public function enrich(DatatableDefinition $definition): DatatableDefinition
    {
        $entityClass = $definition->getEntityClass();

        if (null === $entityClass) {
            return $definition;
        }

        foreach ($definition->getColumns() as $column) {
            if (null !== $column->getType() || $column->isComputed()) {
                continue;
            }

            try {
                $fieldType = $this->fieldTypeGuesser->guessForDefinition($definition, $column->getName());
            } catch (\InvalidArgumentException) {
                continue;
            }

            $definition->replaceColumn($column->withType(
                $fieldType->getCellType(),
                $fieldType->getEnumClass(),
            ));
        }

        return $definition;
    }
}
