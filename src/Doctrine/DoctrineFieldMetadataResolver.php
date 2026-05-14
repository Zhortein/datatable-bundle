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
