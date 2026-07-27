<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Definition;

use Zhortein\DatatableBundle\Enum\ChildContextSource;

/**
 * Describes where a child datatable context value obtains its value.
 */
final readonly class ChildContextValue
{
    private function __construct(
        private ChildContextSource $source,
        private ?string $key = null,
        private mixed $value = null,
        private bool $required = true,
        private bool $hasDefault = false,
        private mixed $defaultValue = null,
    ) {
        if (ChildContextSource::Literal === $this->source) {
            self::assertTransportable($this->value, 'literal');
        }

        if ($this->hasDefault) {
            self::assertTransportable($this->defaultValue, 'default');
        }
    }

    public static function row(string $path): self
    {
        return self::referenced(ChildContextSource::Row, $path);
    }

    public static function optionalRow(string $path): self
    {
        return self::referenced(ChildContextSource::Row, $path, required: false);
    }

    public static function rowOr(string $path, mixed $defaultValue): self
    {
        return self::referenced(
            ChildContextSource::Row,
            $path,
            required: false,
            hasDefault: true,
            defaultValue: $defaultValue,
        );
    }

    public static function context(string $key): self
    {
        return self::referenced(ChildContextSource::Context, $key);
    }

    public static function optionalContext(string $key): self
    {
        return self::referenced(ChildContextSource::Context, $key, required: false);
    }

    public static function contextOr(string $key, mixed $defaultValue): self
    {
        return self::referenced(
            ChildContextSource::Context,
            $key,
            required: false,
            hasDefault: true,
            defaultValue: $defaultValue,
        );
    }

    public static function literal(mixed $value): self
    {
        return new self(ChildContextSource::Literal, value: $value);
    }

    public function getSource(): ChildContextSource
    {
        return $this->source;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function hasDefault(): bool
    {
        return $this->hasDefault;
    }

    public function getDefaultValue(): mixed
    {
        return $this->defaultValue;
    }

    private static function referenced(
        ChildContextSource $source,
        string $key,
        bool $required = true,
        bool $hasDefault = false,
        mixed $defaultValue = null,
    ): self {
        $key = trim($key);

        if ('' === $key) {
            throw new \InvalidArgumentException(sprintf('A child datatable %s context source key must not be empty.', $source->value));
        }

        return new self(
            source: $source,
            key: $key,
            required: $required,
            hasDefault: $hasDefault,
            defaultValue: $defaultValue,
        );
    }

    private static function assertTransportable(mixed $value, string $valueType): void
    {
        if (
            null !== $value
            && !is_scalar($value)
            && !$value instanceof \BackedEnum
            && !$value instanceof \Stringable
        ) {
            throw new \InvalidArgumentException(sprintf('A child datatable context %s value must be scalar, null, a backed enum or Stringable; "%s" given.', $valueType, get_debug_type($value)));
        }
    }
}
