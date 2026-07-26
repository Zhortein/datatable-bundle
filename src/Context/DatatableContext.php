<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Context;

/**
 * Explicit server-side values that a datatable definition may use.
 *
 * Context values are never serialized to the browser automatically.
 */
final readonly class DatatableContext
{
    /**
     * @var array<string, mixed>
     */
    private array $values;

    /**
     * @param array<string, mixed> $values
     */
    public function __construct(array $values = [])
    {
        $normalizedValues = [];

        foreach ($values as $name => $value) {
            if (!is_string($name) || '' === trim($name)) {
                throw new \InvalidArgumentException('A datatable context key must be a non-empty string.');
            }

            $normalizedValues[trim($name)] = $value;
        }

        $this->values = $normalizedValues;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->values);
    }

    public function get(string $name, mixed $default = null): mixed
    {
        return array_key_exists($name, $this->values)
            ? $this->values[$name]
            : $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->values;
    }

    public function with(string $name, mixed $value): self
    {
        return new self(array_replace($this->values, [$name => $value]));
    }
}
