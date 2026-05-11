<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Doctrine;

final readonly class DoctrineFieldType
{
    /**
     * @param class-string<\BackedEnum>|null $enumClass
     */
    public function __construct(
        private string $fieldName,
        private string $doctrineType,
        private string $cellType,
        private bool $searchable,
        private bool $sortable,
        private ?string $enumClass = null,
    ) {
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function getDoctrineType(): string
    {
        return $this->doctrineType;
    }

    public function getCellType(): string
    {
        return $this->cellType;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    /**
     * @return class-string<\BackedEnum>|null
     */
    public function getEnumClass(): ?string
    {
        return $this->enumClass;
    }

    public function isEnum(): bool
    {
        return null !== $this->enumClass;
    }
}
