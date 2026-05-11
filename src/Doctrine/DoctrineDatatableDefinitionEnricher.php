<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Doctrine;

use Zhortein\DatatableBundle\Definition\ColumnDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Provider\DoctrineOrmDataProvider;

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
            if (null !== $column->getType()) {
                continue;
            }

            $fieldName = $this->extractDoctrineFieldName($column);

            if (null === $fieldName) {
                continue;
            }

            try {
                $fieldType = $this->fieldTypeGuesser->guess($entityClass, $fieldName);
            } catch (\InvalidArgumentException) {
                continue;
            }

            $definition->replaceColumn($column->withType($fieldType->getCellType()));
        }

        return $definition;
    }

    private function extractDoctrineFieldName(ColumnDefinition $column): ?string
    {
        $columnName = $column->getName();

        if (!str_contains($columnName, '.')) {
            return $columnName;
        }

        [$alias, $fieldName] = explode('.', $columnName, 2);

        if (DoctrineOrmDataProvider::MAIN_ALIAS !== $alias) {
            return null;
        }

        return '' !== $fieldName ? $fieldName : null;
    }
}
