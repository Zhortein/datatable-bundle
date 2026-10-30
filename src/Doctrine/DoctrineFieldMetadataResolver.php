<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Zhortein\DatatableBundle\Definition\CustomJoinDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Definition\JoinDefinition;
use Zhortein\DatatableBundle\Provider\DoctrineOrmDataProvider;

final readonly class DoctrineFieldMetadataResolver
{
    /**
     * @param class-string $mainEntityClass
     *
     * @return ClassMetadata<object>
     */
    public function resolveMetadataForAlias(
        EntityManagerInterface $entityManager,
        string $mainEntityClass,
        DatatableDefinition $definition,
        string $alias,
    ): ClassMetadata {
        return $this->resolveMetadataForAliasWithStack(
            entityManager: $entityManager,
            mainEntityClass: $mainEntityClass,
            definition: $definition,
            alias: $alias,
            resolvingAliases: [],
        );
    }

    /**
     * @param class-string $mainEntityClass
     * @param list<string> $resolvingAliases
     *
     * @return ClassMetadata<object>
     */
    private function resolveMetadataForAliasWithStack(
        EntityManagerInterface $entityManager,
        string $mainEntityClass,
        DatatableDefinition $definition,
        string $alias,
        array $resolvingAliases,
    ): ClassMetadata {
        if (DoctrineOrmDataProvider::MAIN_ALIAS === $alias) {
            return $entityManager->getClassMetadata($mainEntityClass);
        }

        if (in_array($alias, $resolvingAliases, true)) {
            throw new \InvalidArgumentException(sprintf('Circular Doctrine join alias reference detected for alias "%s".', $alias));
        }

        $customJoin = $definition->getCustomJoins()[$alias] ?? null;

        if ($customJoin instanceof CustomJoinDefinition) {
            return $entityManager->getClassMetadata($customJoin->getTargetEntityClass());
        }

        $join = $definition->getJoins()[$alias] ?? null;

        if (!$join instanceof JoinDefinition) {
            throw new \InvalidArgumentException(sprintf('The Doctrine alias "%s" is not declared.', $alias));
        }

        $joinReference = DoctrineFieldReference::fromString($join->getJoin());

        $sourceMetadata = $this->resolveMetadataForAliasWithStack(
            entityManager: $entityManager,
            mainEntityClass: $mainEntityClass,
            definition: $definition,
            alias: $joinReference->getAlias(),
            resolvingAliases: [...$resolvingAliases, $alias],
        );

        $associationName = $joinReference->getField();

        if (!$sourceMetadata->hasAssociation($associationName)) {
            throw new \InvalidArgumentException(sprintf('The Doctrine association "%s" does not exist on "%s".', $associationName, $sourceMetadata->getName()));
        }

        /** @var class-string $targetClass */
        $targetClass = $sourceMetadata->getAssociationTargetClass($associationName);

        return $entityManager->getClassMetadata($targetClass);
    }

    /**
     * The aliases of the definition's joins reached through a collection, directly or through another such alias:
     * each repeats the main entity once per related row.
     *
     * @param class-string $mainEntityClass
     *
     * @return list<string>
     */
    public function collectionAliases(
        EntityManagerInterface $entityManager,
        string $mainEntityClass,
        DatatableDefinition $definition,
    ): array {
        $aliases = [];

        // A join reaches only aliases declared before it, which the loop has already classified
        foreach ($definition->getJoins() as $alias => $join) {
            $association = DoctrineFieldReference::fromString($join->getJoin());
            $source = $this->resolveMetadataForAlias(
                entityManager: $entityManager,
                mainEntityClass: $mainEntityClass,
                definition: $definition,
                alias: $association->getAlias(),
            );

            if (
                in_array($association->getAlias(), $aliases, true)
                || $source->isCollectionValuedAssociation($association->getField())
            ) {
                $aliases[] = $alias;
            }
        }

        return $aliases;
    }

    /**
     * @param class-string $mainEntityClass
     */
    public function hasField(
        EntityManagerInterface $entityManager,
        string $mainEntityClass,
        DatatableDefinition $definition,
        DoctrineFieldReference $reference,
    ): bool {
        return $this
            ->resolveMetadataForAlias($entityManager, $mainEntityClass, $definition, $reference->getAlias())
            ->hasField($reference->getField())
        ;
    }

    /**
     * @param class-string $mainEntityClass
     */
    public function getTypeOfField(
        EntityManagerInterface $entityManager,
        string $mainEntityClass,
        DatatableDefinition $definition,
        DoctrineFieldReference $reference,
    ): ?string {
        $metadata = $this->resolveMetadataForAlias(
            entityManager: $entityManager,
            mainEntityClass: $mainEntityClass,
            definition: $definition,
            alias: $reference->getAlias(),
        );

        if (!$metadata->hasField($reference->getField())) {
            return null;
        }

        return $metadata->getTypeOfField($reference->getField());
    }
}
