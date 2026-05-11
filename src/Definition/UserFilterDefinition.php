<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Definition;

use Zhortein\DatatableBundle\Enum\FilterType;

final readonly class UserFilterDefinition
{
    /**
     * @param array<string, string> $choices
     * @param array<string, mixed>  $options
     */
    public function __construct(
        private string $name,
        private string $field,
        private ?string $label = null,
        private FilterType $type = FilterType::Text,
        private array $choices = [],
        private ?string $placeholder = null,
        private bool $required = false,
        private array $options = [],
    ) {
        if ('' === trim($this->name)) {
            throw new \InvalidArgumentException('The datatable filter name cannot be empty.');
        }

        if ('' === trim($this->field)) {
            throw new \InvalidArgumentException('The datatable filter field cannot be empty.');
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getType(): FilterType
    {
        return $this->type;
    }

    /**
     * @return array<string, string>
     */
    public function getChoices(): array
    {
        return $this->choices;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function isRequired(): bool
    {
        return $this->required;
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
