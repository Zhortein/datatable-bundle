<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Definition;

use Zhortein\DatatableBundle\EnumPresentation\EnumPresentation;

final readonly class ColumnDefinition
{
    /**
     * @param class-string<\UnitEnum>|null         $enumClass
     * @param array<int|string, EnumPresentation> $enumPresentations
     */
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
        private ?string $enumClass = null,
        private array $enumPresentations = [],
    ) {
        if (null !== $this->valueResolver && '' === trim($this->valueResolver)) {
            throw new \InvalidArgumentException('A computed column value resolver name must not be empty.');
        }

        if (null !== $this->enumClass && !enum_exists($this->enumClass)) {
            throw new \InvalidArgumentException(sprintf('Class "%s" must be an enum.', $this->enumClass));
        }

        foreach ($this->enumPresentations as $presentation) {
            if (!$presentation instanceof EnumPresentation) {
                throw new \InvalidArgumentException('Enum presentations must contain EnumPresentation instances.');
            }
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

    /**
     * @return class-string<\UnitEnum>|null
     */
    public function getEnumClass(): ?string
    {
        return $this->enumClass;
    }

    /**
     * @return array<int|string, EnumPresentation>
     */
    public function getEnumPresentations(): array
    {
        return $this->enumPresentations;
    }

    /**
     * @param class-string<\UnitEnum>|null $enumClass
     */
    public function withType(?string $type, ?string $enumClass = null): self
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
            enumClass: $enumClass ?? $this->enumClass,
            enumPresentations: $this->enumPresentations,
        );
    }
}
