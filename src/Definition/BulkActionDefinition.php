<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Definition;

use Zhortein\DatatableBundle\Enum\ActionIconPosition;

/**
 * Bulk actions are used to perform actions on multiple rows at once.
 *
 * NOTE: Visibility checks only control whether the action is rendered in the UI.
 * The backend route MUST also enforce authorization and validate the request.
 *
 * @param array<string, string> $routeParameters
 * @param array<string, string> $attributes
 */
final readonly class BulkActionDefinition
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
        private ActionIconPosition $iconPosition = ActionIconPosition::Before,
        private string $httpMethod = 'POST',
        private ?string $confirmationMessage = null,
        private ?string $className = null,
        private array $routeParameters = [],
        private array $attributes = [],
        private string $selectedRowsParameterName = 'ids',
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

    public function getSelectedRowsParameterName(): string
    {
        return $this->selectedRowsParameterName;
    }
}
