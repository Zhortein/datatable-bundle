<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Definition;

/**
 * References another field of the same row in a permanent filter, which then compares the two fields.
 */
final readonly class FieldFilterValue
{
    private string $field;

    private function __construct(string $field)
    {
        $field = trim($field);

        if ('' === $field) {
            throw new \InvalidArgumentException('A field filter value must name a non-empty field.');
        }

        $this->field = $field;
    }

    public static function from(string $field): self
    {
        return new self($field);
    }

    public function getField(): string
    {
        return $this->field;
    }
}
