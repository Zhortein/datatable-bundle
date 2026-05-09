<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Definition;

use Zhortein\DatatableBundle\Enum\FilterOperator;

final class DatatableDefinition
{
    /**
     * @var class-string|null
     */
    private ?string $entityClass = null;

    private ?string $translationDomain = null;

    /**
     * @var array<string, ColumnDefinition>
     */
    private array $columns = [];

    /**
     * @var array<string, ActionDefinition>
     */
    private array $rowActions = [];

    /**
     * @var array<string, ActionDefinition>
     */
    private array $globalActions = [];

    /**
     * @var list<FilterDefinition>
     */
    private array $permanentFilters = [];

    /**
     * @var array<string, mixed>
     */
    private array $options = [];

    public function __construct(
        private readonly string $name,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param class-string $entityClass
     */
    public function setEntityClass(string $entityClass): self
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    /**
     * @return class-string|null
     */
    public function getEntityClass(): ?string
    {
        return $this->entityClass;
    }

    public function setTranslationDomain(?string $translationDomain): self
    {
        $this->translationDomain = $translationDomain;

        return $this;
    }

    public function getTranslationDomain(): ?string
    {
        return $this->translationDomain;
    }

    public function addColumn(
        string $name,
        ?string $label = null,
        bool $visible = true,
        bool $sortable = true,
        bool $searchable = true,
        ?string $className = null,
        ?string $template = null,
        ?string $type = null,
    ): self {
        $this->columns[$name] = new ColumnDefinition(
            name: $name,
            label: $label,
            visible: $visible,
            sortable: $sortable,
            searchable: $searchable,
            className: $className,
            template: $template,
            type: $type,
        );

        return $this;
    }

    /**
     * @return array<string, ColumnDefinition>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @param array<string, string> $routeParameters
     * @param array<string, string> $attributes
     */
    public function addRowAction(
        string $name,
        string $route,
        ?string $label = null,
        ?string $icon = null,
        string $httpMethod = 'GET',
        ?string $confirmationMessage = null,
        ?string $className = null,
        array $routeParameters = [],
        array $attributes = [],
    ): self {
        $this->rowActions[$name] = new ActionDefinition(
            name: $name,
            route: $route,
            label: $label,
            icon: $icon,
            httpMethod: $httpMethod,
            confirmationMessage: $confirmationMessage,
            className: $className,
            routeParameters: $routeParameters,
            attributes: $attributes,
        );

        return $this;
    }

    /**
     * @return array<string, ActionDefinition>
     */
    public function getRowActions(): array
    {
        return $this->rowActions;
    }

    /**
     * @param array<string, string> $routeParameters
     * @param array<string, string> $attributes
     */
    public function addGlobalAction(
        string $name,
        string $route,
        ?string $label = null,
        ?string $icon = null,
        string $httpMethod = 'GET',
        ?string $confirmationMessage = null,
        ?string $className = null,
        array $routeParameters = [],
        array $attributes = [],
    ): self {
        $this->globalActions[$name] = new ActionDefinition(
            name: $name,
            route: $route,
            label: $label,
            icon: $icon,
            httpMethod: $httpMethod,
            confirmationMessage: $confirmationMessage,
            className: $className,
            routeParameters: $routeParameters,
            attributes: $attributes,
        );

        return $this;
    }

    /**
     * @return array<string, ActionDefinition>
     */
    public function getGlobalActions(): array
    {
        return $this->globalActions;
    }

    public function addPermanentFilter(
        string $field,
        FilterOperator $operator,
        mixed $value = null,
        mixed $secondValue = null,
    ): self {
        $this->permanentFilters[] = new FilterDefinition(
            field: $field,
            operator: $operator,
            value: $value,
            secondValue: $secondValue,
        );

        return $this;
    }

    /**
     * @return list<FilterDefinition>
     */
    public function getPermanentFilters(): array
    {
        return $this->permanentFilters;
    }

    public function setOption(string $name, mixed $value): self
    {
        $this->options[$name] = $value;

        return $this;
    }

    public function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }

    public function getOption(string $name, mixed $default = null): mixed
    {
        return $this->options[$name] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
