<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Definition;

use Zhortein\DatatableBundle\Enum\AggregateFunction;

final readonly class AggregateColumnDefinition
{
    public function __construct(
        private string $name,
        private string $field,
        private AggregateFunction $function = AggregateFunction::Count,
        private bool $distinct = false,
    ) {
        if ('' === trim($this->name)) {
            throw new \InvalidArgumentException('The aggregate column name cannot be empty.');
        }

        if ('' === trim($this->field)) {
            throw new \InvalidArgumentException('The aggregate column field cannot be empty.');
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

    public function getFunction(): AggregateFunction
    {
        return $this->function;
    }

    public function isDistinct(): bool
    {
        return $this->distinct;
    }
}
