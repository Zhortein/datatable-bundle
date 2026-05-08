<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Definition;

final readonly class ColumnDefinition
{
    public function __construct(
        private string $name,
        private ?string $label = null,
        private bool $visible = true,
        private bool $sortable = true,
        private bool $searchable = true,
        private ?string $className = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function getClassName(): ?string
    {
        return $this->className;
    }
}
