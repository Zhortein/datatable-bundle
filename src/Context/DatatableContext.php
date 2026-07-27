<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Context;

/**
 * Explicit server-side values that a datatable definition may use.
 *
 * Only explicitly allowlisted scalar values may be transported through the
 * browser. Every other value remains server-side.
 */
final readonly class DatatableContext
{
    /**
     * @var array<string, mixed>
     */
    private array $values;

    /**
     * @var list<string>
     */
    private array $browserSafeKeys;

    /**
     * @param array<string, mixed> $values
     * @param list<string>         $browserSafeKeys
     */
    public function __construct(array $values = [], array $browserSafeKeys = [])
    {
        $normalizedValues = [];

        foreach ($values as $name => $value) {
            $normalizedValues[$this->normalizeKey($name)] = $value;
        }

        $normalizedBrowserSafeKeys = [];

        foreach ($browserSafeKeys as $name) {
            $normalizedBrowserSafeKeys[] = $this->normalizeKey($name);
        }

        $this->values = $normalizedValues;
        $this->browserSafeKeys = array_values(array_unique($normalizedBrowserSafeKeys));
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

    /**
     * @return list<string>
     */
    public function getBrowserSafeKeys(): array
    {
        return $this->browserSafeKeys;
    }

    /**
     * @return array<string, mixed>
     */
    public function getBrowserSafeValues(): array
    {
        $values = [];

        foreach ($this->browserSafeKeys as $name) {
            if (array_key_exists($name, $this->values)) {
                $values[$name] = $this->values[$name];
            }
        }

        return $values;
    }

    public function with(string $name, mixed $value): self
    {
        return new self(
            array_replace($this->values, [$this->normalizeKey($name) => $value]),
            $this->browserSafeKeys,
        );
    }

    /**
     * @param array<string, mixed> $values
     */
    public function withBrowserValues(array $values): self
    {
        $normalizedValues = [];

        foreach ($values as $name => $value) {
            $name = $this->normalizeKey($name);

            if (!in_array($name, $this->browserSafeKeys, true)) {
                throw new \InvalidArgumentException(sprintf('The datatable context key "%s" is not allowlisted for browser propagation.', $name));
            }

            $normalizedValues[$name] = $value;
        }

        return new self(
            array_replace($this->values, $normalizedValues),
            $this->browserSafeKeys,
        );
    }

    private function normalizeKey(string $name): string
    {
        $name = trim($name);

        if ('' === $name) {
            throw new \InvalidArgumentException('A datatable context key must be a non-empty string.');
        }

        return $name;
    }
}
