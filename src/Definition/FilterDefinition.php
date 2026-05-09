<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Definition;

use Zhortein\DatatableBundle\Enum\FilterOperator;

final readonly class FilterDefinition
{
    public function __construct(
        private string $field,
        private FilterOperator $operator,
        private mixed $value = null,
        private mixed $secondValue = null,
    ) {
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getOperator(): FilterOperator
    {
        return $this->operator;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getSecondValue(): mixed
    {
        return $this->secondValue;
    }

    public function isUnary(): bool
    {
        return in_array($this->operator, [FilterOperator::IsNull, FilterOperator::IsNotNull], true);
    }
}
