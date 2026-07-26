<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Definition;

use Zhortein\DatatableBundle\Enum\ActionIconPosition;

final readonly class ActionDefinition
{
    /**
     * @var array<string, string>
     */
    private array $attributes;

    private ?string $permission;

    /**
     * @param array<string, string|RouteParameter> $routeParameters
     * @param array<string, string>                $attributes
     */
    public function __construct(
        private string $name,
        private string $route,
        private ?string $label = null,
        private ?string $icon = null,
        private ActionIconPosition $iconPosition = ActionIconPosition::Before,
        private string $httpMethod = 'GET',
        private ?string $confirmationMessage = null,
        private ?string $className = null,
        private array $routeParameters = [],
        array $attributes = [],
        ?string $permission = null,
    ) {
        $legacyPermission = $attributes['permission'] ?? null;
        unset($attributes['permission']);

        $this->attributes = $attributes;
        $this->permission = $permission ?? $legacyPermission;
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

    public function getIconPosition(): ActionIconPosition
    {
        return $this->iconPosition;
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
     * @return array<string, string|RouteParameter>
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
        if ('permission' === $name) {
            return $this->permission ?? $default;
        }

        return $this->attributes[$name] ?? $default;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }
}
