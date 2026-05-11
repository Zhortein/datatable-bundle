<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Definition;

final readonly class ActionDefinition
{
    /**
     * @param array<string, string> $routeParameters
     * @param array<string, string> $attributes
     */
    public function __construct(
        private string $name,
        private string $route,
        private ?string $label = null,
        private ?string $icon = null,
        private string $httpMethod = 'GET',
        private ?string $confirmationMessage = null,
        private ?string $className = null,
        private array $routeParameters = [],
        private array $attributes = [],
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getHttpMethod(): string
    {
        return $this->httpMethod;
    }

    public function getConfirmationMessage(): ?string
    {
        return $this->confirmationMessage;
    }

    public function getClassName(): ?string
    {
        return $this->className;
    }

    /**
     * @return array<string, string>
     */
    public function getRouteParameters(): array
    {
        return $this->routeParameters;
    }

    /**
     * @return array<string, string>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $name, ?string $default = null): ?string
    {
        return $this->attributes[$name] ?? $default;
    }
}
