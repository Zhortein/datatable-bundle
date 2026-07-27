<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\EnumPresentation;

final readonly class EnumPresentation
{
    public function __construct(
        private string $label,
        private ?string $badgeVariant = null,
        private ?string $color = null,
        private ?string $icon = null,
    ) {
        if ('' === trim($this->label)) {
            throw new \InvalidArgumentException('An enum presentation label must not be empty.');
        }
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getBadgeVariant(): ?string
    {
        return $this->normalizeOptionalValue($this->badgeVariant);
    }

    public function getColor(): ?string
    {
        return $this->normalizeOptionalValue($this->color);
    }

    public function getIcon(): ?string
    {
        return $this->normalizeOptionalValue($this->icon);
    }

    public function withLabel(string $label): self
    {
        return new self(
            label: $label,
            badgeVariant: $this->badgeVariant,
            color: $this->color,
            icon: $this->icon,
        );
    }

    private function normalizeOptionalValue(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $value = trim($value);

        return '' === $value ? null : $value;
    }
}
