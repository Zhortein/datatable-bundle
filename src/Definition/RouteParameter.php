<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Definition;

use Zhortein\DatatableBundle\Enum\RouteParameterSource;

/**
 * Describes where an action route parameter obtains its value.
 */
final readonly class RouteParameter
{
    private function __construct(
        private RouteParameterSource $source,
        private ?string $key = null,
        private mixed $value = null,
        private bool $required = true,
        private bool $hasDefault = false,
        private mixed $defaultValue = null,
    ) {
    }

    public static function row(string $path): self
    {
        return self::referenced(RouteParameterSource::Row, $path);
    }

    public static function optionalRow(string $path): self
    {
        return self::referenced(RouteParameterSource::Row, $path, required: false);
    }

    public static function rowOr(string $path, mixed $defaultValue): self
    {
        return self::referenced(
            RouteParameterSource::Row,
            $path,
            required: false,
            hasDefault: true,
            defaultValue: $defaultValue,
        );
    }

    public static function literal(mixed $value): self
    {
        return new self(RouteParameterSource::Literal, value: $value);
    }

    public static function context(string $key): self
    {
        return self::referenced(RouteParameterSource::Context, $key);
    }

    public static function optionalContext(string $key): self
    {
        return self::referenced(RouteParameterSource::Context, $key, required: false);
    }

    public static function contextOr(string $key, mixed $defaultValue): self
    {
        return self::referenced(
            RouteParameterSource::Context,
            $key,
            required: false,
            hasDefault: true,
            defaultValue: $defaultValue,
        );
    }

    public function getSource(): RouteParameterSource
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
        RouteParameterSource $source,
        string $key,
        bool $required = true,
        bool $hasDefault = false,
        mixed $defaultValue = null,
    ): self {
        $key = trim($key);

        if ('' === $key) {
            throw new \InvalidArgumentException(sprintf('A %s route parameter source key must not be empty.', $source->value));
        }

        return new self(
            source: $source,
            key: $key,
            required: $required,
            hasDefault: $hasDefault,
            defaultValue: $defaultValue,
        );
    }
}
