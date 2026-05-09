<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Doctrine;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;

final readonly class DoctrineFieldTypeGuesser
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
    ) {
    }

    /**
     * @param class-string $entityClass
     */
    public function guess(string $entityClass, string $fieldName): DoctrineFieldType
    {
        $manager = $this->managerRegistry->getManagerForClass($entityClass);

        if (null === $manager) {
            throw new \InvalidArgumentException(sprintf('No Doctrine manager found for class "%s".', $entityClass));
        }

        $metadata = $manager->getClassMetadata($entityClass);

        if (!$metadata instanceof ClassMetadata) {
            throw new \InvalidArgumentException(sprintf('Doctrine metadata for class "%s" must be an ORM class metadata instance.', $entityClass));
        }

        if (!$metadata->hasField($fieldName)) {
            throw new \InvalidArgumentException(sprintf('Field "%s" does not exist on Doctrine entity "%s".', $fieldName, $entityClass));
        }

        $doctrineType = $metadata->getTypeOfField($fieldName);

        if (null === $doctrineType) {
            throw new \InvalidArgumentException(sprintf('Unable to guess Doctrine type for field "%s" on entity "%s".', $fieldName, $entityClass));
        }

        $enumClass = $this->guessEnumClass($metadata->getFieldMapping($fieldName));

        return new DoctrineFieldType(
            fieldName: $fieldName,
            doctrineType: $doctrineType,
            cellType: $this->mapDoctrineTypeToCellType($doctrineType, $enumClass),
            searchable: $this->isSearchableDoctrineType($doctrineType, $enumClass),
            sortable: $this->isSortableDoctrineType($doctrineType),
            enumClass: $enumClass,
        );
    }

    /**
     * @param class-string<\BackedEnum>|null $enumClass
     */
    private function mapDoctrineTypeToCellType(string $doctrineType, ?string $enumClass): string
    {
        if (null !== $enumClass) {
            return 'enum';
        }

        return match ($doctrineType) {
            Types::BIGINT,
            Types::DECIMAL,
            Types::FLOAT,
            Types::INTEGER,
            Types::SMALLINT => 'numeric',

            Types::BOOLEAN => 'boolean',

            Types::DATE_MUTABLE,
            Types::DATE_IMMUTABLE,
            Types::DATETIME_MUTABLE,
            Types::DATETIME_IMMUTABLE,
            Types::DATETIMETZ_MUTABLE,
            Types::DATETIMETZ_IMMUTABLE,
            Types::TIME_MUTABLE,
            Types::TIME_IMMUTABLE => 'datetime',

            Types::JSON,
            Types::SIMPLE_ARRAY => 'array',

            default => 'string',
        };
    }

    /**
     * @param class-string<\BackedEnum>|null $enumClass
     */
    private function isSearchableDoctrineType(string $doctrineType, ?string $enumClass): bool
    {
        if (null !== $enumClass) {
            return true;
        }

        return match ($doctrineType) {
            Types::ASCII_STRING,
            Types::STRING,
            Types::TEXT,
            Types::GUID,
            Types::BIGINT,
            Types::INTEGER,
            Types::SMALLINT => true,
            default => false,
        };
    }

    private function isSortableDoctrineType(string $doctrineType): bool
    {
        return !in_array($doctrineType, [
            Types::BINARY,
            Types::BLOB,
            Types::JSON,
            Types::SIMPLE_ARRAY,
        ], true);
    }

    /**
     * @return class-string<\BackedEnum>|null
     */
    private function guessEnumClass(mixed $fieldMapping): ?string
    {
        $enumClass = null;

        if (is_array($fieldMapping)) {
            $enumClass = $fieldMapping['enumType'] ?? null;
        } elseif (is_object($fieldMapping) && property_exists($fieldMapping, 'enumType')) {
            $enumClass = $fieldMapping->enumType;
        }

        if (!is_string($enumClass) || '' === $enumClass) {
            return null;
        }

        if (!is_subclass_of($enumClass, \BackedEnum::class)) {
            return null;
        }

        return $enumClass;
    }
}
