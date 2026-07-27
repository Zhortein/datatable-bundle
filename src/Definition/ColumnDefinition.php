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
        private ?string $template = null,
        private ?string $type = null,
        private bool $negate = false,
        private ?bool $exportable = null,
        private ?string $valueResolver = null,
    ) {
        if (null !== $this->valueResolver && '' === trim($this->valueResolver)) {
            throw new \InvalidArgumentException('A computed column value resolver name must not be empty.');
        }
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

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function isNegated(): bool
    {
        return $this->negate;
    }

    public function getExportable(): ?bool
    {
        return $this->exportable;
    }

    public function getValueResolver(): ?string
    {
        return null === $this->valueResolver ? null : trim($this->valueResolver);
    }

    public function isComputed(): bool
    {
        return null !== $this->valueResolver;
    }

    public function withType(?string $type): self
    {
        return new self(
            name: $this->name,
            label: $this->label,
            visible: $this->visible,
            sortable: $this->sortable,
            searchable: $this->searchable,
            className: $this->className,
            template: $this->template,
            type: $type,
            negate: $this->negate,
            exportable: $this->exportable,
            valueResolver: $this->valueResolver,
        );
    }
}
