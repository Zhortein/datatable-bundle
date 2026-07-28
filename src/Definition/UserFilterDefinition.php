<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Definition;

use Zhortein\DatatableBundle\Enum\FilterType;
use Zhortein\DatatableBundle\EnumPresentation\EnumPresentation;

final readonly class UserFilterDefinition
{
    /**
     * @param array<string, string>               $choices
     * @param array<string, mixed>                $options
     * @param class-string<\UnitEnum>|null        $enumClass
     * @param array<int|string, EnumPresentation> $enumPresentations
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
        private ?string $enumClass = null,
        private array $enumPresentations = [],
        private bool $preferenceSafe = false,
    ) {
        if ('' === trim($this->name)) {
            throw new \InvalidArgumentException('The datatable filter name cannot be empty.');
        }

        if ('' === trim($this->field)) {
            throw new \InvalidArgumentException('The datatable filter field cannot be empty.');
        }

        if (null !== $this->enumClass && !enum_exists($this->enumClass)) {
            throw new \InvalidArgumentException(sprintf('Class "%s" must be an enum.', $this->enumClass));
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

    public function isPreferenceSafe(): bool
    {
        return $this->preferenceSafe;
    }
}
