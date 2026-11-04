<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Action;

use Zhortein\DatatableBundle\Definition\DatatableDefinition;

final readonly class ActionVisibilityContext
{
    /**
     * @param array<string, mixed>|null $row
     * @param array<string, mixed>      $options
     * @param mixed                     $source  the server-side source the provider attached to the row, if any
     */
    public function __construct(
        private DatatableDefinition $definition,
        private ?array $row = null,
        private array $options = [],
        private mixed $source = null,
    ) {
    }

    public function getDefinition(): DatatableDefinition
    {
        return $this->definition;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRow(): ?array
    {
        return $this->row;
    }

    public function hasRow(): bool
    {
        return null !== $this->row;
    }

    public function getSource(): mixed
    {
        return $this->source;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOption(string $name, mixed $default = null): mixed
    {
        return $this->options[$name] ?? $default;
    }
}
