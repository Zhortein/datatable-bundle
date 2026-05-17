<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Definition;

use Zhortein\DatatableBundle\Enum\FilterOperator;
use Zhortein\DatatableBundle\Enum\FilterType;

final readonly class AdvancedFilterFieldDefinition
{
    /**
     * @param list<FilterOperator>  $allowedOperators
     * @param array<string, string> $choices
     */
    public function __construct(
        private string $name,
        private string $field,
        private ?string $label = null,
        private FilterType $type = FilterType::Text,
        private array $allowedOperators = [],
        private array $choices = [],
    ) {
        if ('' === trim($this->name)) {
            throw new \InvalidArgumentException('The advanced filter field name cannot be empty.');
        }

        if ('' === trim($this->field)) {
            throw new \InvalidArgumentException('The advanced filter field cannot be empty.');
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
     * @return list<FilterOperator>
     */
    public function getAllowedOperators(): array
    {
        return $this->allowedOperators;
    }

    /**
     * @return array<string, string>
     */
    public function getChoices(): array
    {
        return $this->choices;
    }
}
