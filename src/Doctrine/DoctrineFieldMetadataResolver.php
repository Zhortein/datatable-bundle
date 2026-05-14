<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
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
        if (DoctrineOrmDataProvider::MAIN_ALIAS === $alias) {
            return $entityManager->getClassMetadata($mainEntityClass);
        }

        $join = $definition->getJoins()[$alias] ?? null;

        if (!$join instanceof JoinDefinition) {
            throw new \InvalidArgumentException(sprintf('The Doctrine alias "%s" is not declared.', $alias));
        }

        $mainMetadata = $entityManager->getClassMetadata($mainEntityClass);
        $associationName = $this->extractAssociationName($join);

        if (!$mainMetadata->hasAssociation($associationName)) {
            throw new \InvalidArgumentException(sprintf('The Doctrine association "%s" does not exist on "%s".', $associationName, $mainEntityClass));
        }

        /** @var class-string $targetClass */
        $targetClass = $mainMetadata->getAssociationTargetClass($associationName);

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

    private function extractAssociationName(JoinDefinition $join): string
    {
        return DoctrineFieldReference::fromString($join->getJoin())->getField();
    }
}
