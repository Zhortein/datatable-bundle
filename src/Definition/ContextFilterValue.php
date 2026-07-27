<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Definition;

/**
 * References a value from the resolved datatable context in a permanent filter.
 */
final readonly class ContextFilterValue
{
    private string $key;

    private function __construct(string $key)
    {
        $key = trim($key);

        if ('' === $key) {
            throw new \InvalidArgumentException('A context filter value key must be a non-empty string.');
        }

        $this->key = $key;
    }

    public static function from(string $key): self
    {
        return new self($key);
    }

    public function getKey(): string
    {
        return $this->key;
    }
}
