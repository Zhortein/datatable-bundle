<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Definition;

final class DatatableDefinition
{
    /**
     * @var class-string|null
     */
    private ?string $entityClass = null;

    private ?string $translationDomain = null;

    /**
     * @var array<string, ColumnDefinition>
     */
    private array $columns = [];

    public function __construct(
        private readonly string $name,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param class-string $entityClass
     */
    public function setEntityClass(string $entityClass): self
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    /**
     * @return class-string|null
     */
    public function getEntityClass(): ?string
    {
        return $this->entityClass;
    }

    public function setTranslationDomain(?string $translationDomain): self
    {
        $this->translationDomain = $translationDomain;

        return $this;
    }

    public function getTranslationDomain(): ?string
    {
        return $this->translationDomain;
    }

    public function addColumn(
        string $name,
        ?string $label = null,
        bool $visible = true,
        bool $sortable = true,
        bool $searchable = true,
        ?string $className = null,
    ): self {
        $this->columns[$name] = new ColumnDefinition(
            name: $name,
            label: $label,
            visible: $visible,
            sortable: $sortable,
            searchable: $searchable,
            className: $className,
        );

        return $this;
    }

    /**
     * @return array<string, ColumnDefinition>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }
}
