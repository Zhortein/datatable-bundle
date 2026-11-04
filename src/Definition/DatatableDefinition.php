<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Definition;

use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Enum\ActionIconPosition;
use Zhortein\DatatableBundle\Enum\AggregateFunction;
use Zhortein\DatatableBundle\Enum\ExportFormat;
use Zhortein\DatatableBundle\Enum\FilterOperator;
use Zhortein\DatatableBundle\Enum\FilterType;
use Zhortein\DatatableBundle\Enum\JoinType;
use Zhortein\DatatableBundle\EnumPresentation\EnumPresentation;
use Zhortein\DatatableBundle\Filter\Expression\ComparisonOperator;
use Zhortein\DatatableBundle\Sorting\SortCriterion;

final class DatatableDefinition
{
    /**
     * @var class-string|null
     */
    private ?string $entityClass = null;

    private ?string $translationDomain = null;

    private DatatableContext $context;

    private ?ChildDatatableDefinition $childDatatable = null;

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
     * @var array<string, BulkActionDefinition>
     */
    private array $bulkActions = [];

    /**
     * @var list<FilterDefinition>
     */
    private array $permanentFilters = [];

    /**
     * @var array<string, UserFilterDefinition>
     */
    private array $filters = [];

    /**
     * @var array<string, JoinDefinition>
     */
    private array $joins = [];

    /**
     * @var array<string, mixed>
     */
    private array $options = [];

    /**
     * @var array<string, CustomJoinDefinition>
     */
    private array $customJoins = [];

    /**
     * @var array<string, AggregateColumnDefinition>
     */
    private array $aggregateColumns = [];

    /**
     * @var array<string, AdvancedFilterFieldDefinition>
     */
    private array $advancedFilterFields = [];

    /**
     * @var array<string, int>
     */
    private array $exportLimits = [];

    /**
     * @var list<SortCriterion>
     */
    private array $defaultSorts = [];

    /**
     * @var array<string, mixed>
     */
    private array $defaultRenderOptions = [];

    /**
     * @var list<string>|null
     */
    private ?array $entitySourceAssociations = null;

    public function __construct(
        private readonly string $name,
    ) {
        $this->context = new DatatableContext();
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * The order the datatable renders in when neither the render options nor the user's preferences sort it.
     */
    public function setDefaultSorts(SortCriterion ...$sorts): self
    {
        $this->defaultSorts = SortCriterion::normalizeList(array_values($sorts));

        return $this;
    }

    /**
     * @return list<SortCriterion>
     */
    public function getDefaultSorts(): array
    {
        return $this->defaultSorts;
    }

    /**
     * The render options the datatable starts from, such as `search` or `pageSize`, each replaced by the same option
     * passed when it is rendered.
     *
     * A child datatable is rendered by the bundle's child route, which passes no such option: these are its only ones.
     *
     * @param array<string, mixed> $options
     */
    public function setDefaultRenderOptions(array $options): self
    {
        $this->defaultRenderOptions = $options;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaultRenderOptions(): array
    {
        return $this->defaultRenderOptions;
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

    /**
     * Attaches to each row its entity as the row's source, loaded for the whole page in one query with the associations
     * given, so that a row action's permission is decided on the entity and a cell may read it.
     *
     * Without it, the Doctrine provider returns scalar projections only: a voter needing an association of the entity,
     * such as its roles, could not decide on the row alone.
     *
     * @param list<string> $associations associations of the entity fetched with it, so that reading them queries nothing
     */
    public function setEntitySources(array $associations = []): self
    {
        $this->entitySourceAssociations = $associations;

        return $this;
    }

    public function hasEntitySources(): bool
    {
        return null !== $this->entitySourceAssociations;
    }

    /**
     * @return list<string>
     */
    public function getEntitySourceAssociations(): array
    {
        return $this->entitySourceAssociations ?? [];
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

    public function setContext(DatatableContext $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function getContext(): DatatableContext
    {
        return $this->context;
    }

    /**
     * @param array<string, ChildContextValue> $context
     */
    public function setChildDatatable(
        string $name,
        array $context = [],
        ?string $expandLabel = null,
        ?string $collapseLabel = null,
        int $maxDepth = ChildDatatableDefinition::MAX_DEPTH,
    ): self {
        $this->childDatatable = new ChildDatatableDefinition(
            name: $name,
            context: $context,
            expandLabel: $expandLabel,
            collapseLabel: $collapseLabel,
            maxDepth: $maxDepth,
        );

        return $this;
    }

    public function hasChildDatatable(): bool
    {
        return null !== $this->childDatatable;
    }

    public function getChildDatatable(): ?ChildDatatableDefinition
    {
        return $this->childDatatable;
    }

    /**
     * @param class-string<\UnitEnum>|null        $enumClass
     * @param array<int|string, EnumPresentation> $enumPresentations
     */
    public function addColumn(
        string $name,
        ?string $label = null,
        bool $visible = true,
        bool $sortable = true,
        bool $searchable = true,
        ?string $className = null,
        ?string $template = null,
        ?string $type = null,
        bool $negate = false,
        ?bool $exportable = null,
        ?string $enumClass = null,
        array $enumPresentations = [],
    ): self {
        $type ??= null !== $enumClass ? 'enum' : null;

        $this->columns[$name] = new ColumnDefinition(
            name: $name,
            label: $label,
            visible: $visible,
            sortable: $sortable,
            searchable: $searchable,
            className: $className,
            template: $template,
            type: $type,
            negate: $negate,
            exportable: $exportable,
            enumClass: $enumClass,
            enumPresentations: $enumPresentations,
        );

        return $this;
    }

    public function replaceColumn(ColumnDefinition $column): self
    {
        $this->columns[$column->getName()] = $column;

        return $this;
    }

    /**
     * @param class-string<\UnitEnum>|null        $enumClass
     * @param array<int|string, EnumPresentation> $enumPresentations
     */
    public function addComputedColumn(
        string $name,
        string $valueResolver,
        ?string $label = null,
        bool $visible = true,
        ?string $className = null,
        ?string $template = null,
        ?string $type = null,
        bool $negate = false,
        ?bool $exportable = null,
        ?string $enumClass = null,
        array $enumPresentations = [],
    ): self {
        $type ??= null !== $enumClass ? 'enum' : null;

        $this->columns[$name] = new ColumnDefinition(
            name: $name,
            label: $label,
            visible: $visible,
            sortable: false,
            searchable: false,
            className: $className,
            template: $template,
            type: $type,
            negate: $negate,
            exportable: $exportable,
            valueResolver: $valueResolver,
            enumClass: $enumClass,
            enumPresentations: $enumPresentations,
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
     * @param array<string, string>               $choices
     * @param array<string, mixed>                $options
     * @param class-string<\UnitEnum>|null        $enumClass
     * @param array<int|string, EnumPresentation> $enumPresentations
     */
    public function addFilter(
        string $name,
        string $field,
        ?string $label = null,
        FilterType $type = FilterType::Text,
        array $choices = [],
        ?string $placeholder = null,
        bool $required = false,
        array $options = [],
        ?string $enumClass = null,
        array $enumPresentations = [],
        bool $preferenceSafe = false,
    ): self {
        if (null !== $enumClass && FilterType::Text === $type) {
            $type = FilterType::Enum;
        }

        $this->filters[$name] = new UserFilterDefinition(
            name: $name,
            field: $field,
            label: $label,
            type: $type,
            choices: $choices,
            placeholder: $placeholder,
            required: $required,
            options: $options,
            enumClass: $enumClass,
            enumPresentations: $enumPresentations,
            preferenceSafe: $preferenceSafe,
        );

        return $this;
    }

    /**
     * @return array<string, UserFilterDefinition>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function addJoin(
        string $alias,
        string $join,
        JoinType $type = JoinType::Left,
    ): self {
        $this->joins[$alias] = new JoinDefinition(
            alias: $alias,
            join: $join,
            type: $type,
        );

        return $this;
    }

    /**
     * @return array<string, JoinDefinition>
     */
    public function getJoins(): array
    {
        return $this->joins;
    }

    /**
     * @param array<string, string|RouteParameter> $routeParameters
     * @param array<string, string>                $attributes
     */
    public function addRowAction(
        string $name,
        string $route,
        ?string $label = null,
        ?string $icon = null,
        ActionIconPosition $iconPosition = ActionIconPosition::Before,
        string $httpMethod = 'GET',
        ?string $confirmationMessage = null,
        ?string $className = null,
        array $routeParameters = [],
        array $attributes = [],
        ?string $permission = null,
        ?AjaxActionOptions $ajax = null,
    ): self {
        $this->rowActions[$name] = new ActionDefinition(
            name: $name,
            route: $route,
            label: $label,
            icon: $icon,
            iconPosition: $iconPosition,
            httpMethod: $httpMethod,
            confirmationMessage: $confirmationMessage,
            className: $className,
            routeParameters: $routeParameters,
            attributes: $attributes,
            permission: $permission,
            ajax: $ajax,
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
     * @param array<string, string|RouteParameter> $routeParameters
     * @param array<string, string>                $attributes
     */
    public function addGlobalAction(
        string $name,
        string $route,
        ?string $label = null,
        ?string $icon = null,
        ActionIconPosition|string $iconPosition = ActionIconPosition::Before,
        string $httpMethod = 'GET',
        ?string $confirmationMessage = null,
        ?string $className = null,
        array $routeParameters = [],
        array $attributes = [],
        ?string $permission = null,
        ?AjaxActionOptions $ajax = null,
    ): self {
        if (is_string($iconPosition)) {
            $iconPosition = ActionIconPosition::tryFrom($iconPosition) ?? ActionIconPosition::Before;
        }
        $this->globalActions[$name] = new ActionDefinition(
            name: $name,
            route: $route,
            label: $label,
            icon: $icon,
            iconPosition: $iconPosition,
            httpMethod: $httpMethod,
            confirmationMessage: $confirmationMessage,
            className: $className,
            routeParameters: $routeParameters,
            attributes: $attributes,
            permission: $permission,
            ajax: $ajax,
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

    /**
     * @param array<string, string|RouteParameter> $routeParameters
     * @param array<string, string>                $attributes
     *
     * NOTE: Visibility checks only control whether the action is rendered in the UI.
     * The backend route MUST also enforce authorization and validate the request.
     */
    public function addBulkAction(
        string $name,
        string $route,
        ?string $label = null,
        ?string $icon = null,
        ActionIconPosition|string $iconPosition = ActionIconPosition::Before,
        string $httpMethod = 'POST',
        ?string $confirmationMessage = null,
        ?string $className = null,
        array $routeParameters = [],
        array $attributes = [],
        string $selectedRowsParameterName = 'ids',
        ?string $permission = null,
        ?AjaxActionOptions $ajax = null,
    ): self {
        if (is_string($iconPosition)) {
            $iconPosition = ActionIconPosition::tryFrom($iconPosition) ?? ActionIconPosition::Before;
        }
        $this->bulkActions[$name] = new BulkActionDefinition(
            name: $name,
            route: $route,
            label: $label,
            icon: $icon,
            iconPosition: $iconPosition,
            httpMethod: $httpMethod,
            confirmationMessage: $confirmationMessage,
            className: $className,
            routeParameters: $routeParameters,
            attributes: $attributes,
            selectedRowsParameterName: $selectedRowsParameterName,
            permission: $permission,
            ajax: $ajax,
        );

        return $this;
    }

    /**
     * @return array<string, BulkActionDefinition>
     */
    public function getBulkActions(): array
    {
        return $this->bulkActions;
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

    public function setExportLimit(
        int $maxRows,
        ExportFormat|string|null $format = null,
    ): self {
        if ($maxRows < 1) {
            throw new \InvalidArgumentException('The datatable export row limit must be greater than or equal to 1.');
        }

        if (is_string($format)) {
            $format = ExportFormat::fromString($format);
        }

        $this->exportLimits[$format->value ?? '*'] = $maxRows;

        return $this;
    }

    public function getExportLimit(ExportFormat|string|null $format = null): ?int
    {
        if (is_string($format)) {
            $format = ExportFormat::fromString($format);
        }

        if (null !== $format && isset($this->exportLimits[$format->value])) {
            return $this->exportLimits[$format->value];
        }

        return $this->exportLimits['*'] ?? null;
    }

    /**
     * @param class-string $targetEntityClass
     */
    public function addCustomJoin(
        string $alias,
        string $targetEntityClass,
        string $condition,
        JoinType $type = JoinType::Left,
    ): self {
        $this->customJoins[$alias] = new CustomJoinDefinition($alias, $targetEntityClass, $condition, $type);

        return $this;
    }

    /**
     * @return array<string, CustomJoinDefinition>
     */
    public function getCustomJoins(): array
    {
        return $this->customJoins;
    }

    /**
     * An aggregate column is numeric unless told otherwise: the latest of dates is a datetime, and a sum of amounts
     * may take the template formatting the entity's own amounts.
     */
    public function addAggregateColumn(
        string $name,
        string $field,
        AggregateFunction $function = AggregateFunction::Count,
        ?string $label = null,
        bool $visible = true,
        ?string $className = null,
        bool $distinct = false,
        bool $sortable = true,
        ?string $template = null,
        string $type = 'numeric',
    ): self {
        $this->columns[$name] = new ColumnDefinition(
            name: $name,
            label: $label,
            visible: $visible,
            sortable: $sortable,
            searchable: false,
            className: $className,
            template: $template,
            type: $type,
        );

        $this->aggregateColumns[$name] = new AggregateColumnDefinition(
            name: $name,
            field: $field,
            function: $function,
            distinct: $distinct,
        );

        return $this;
    }

    /**
     * @return array<string, AggregateColumnDefinition>
     */
    public function getAggregateColumns(): array
    {
        return $this->aggregateColumns;
    }

    /**
     * @param list<FilterOperator|ComparisonOperator> $allowedOperators
     * @param array<string, string>                   $choices
     * @param class-string<\BackedEnum>|null          $enumClass
     * @param array<int|string, EnumPresentation>     $enumPresentations
     */
    public function addAdvancedFilterField(
        string $name,
        string $field,
        ?string $label = null,
        FilterType $type = FilterType::Text,
        array $allowedOperators = [],
        array $choices = [],
        ?string $enumClass = null,
        bool $nullable = false,
        array $enumPresentations = [],
    ): self {
        if (null !== $enumClass && FilterType::Text === $type) {
            $type = FilterType::Enum;
        }

        $this->advancedFilterFields[$name] = new AdvancedFilterFieldDefinition(
            name: $name,
            field: $field,
            label: $label,
            type: $type,
            allowedOperators: $allowedOperators,
            choices: $choices,
            enumClass: $enumClass,
            nullable: $nullable,
            enumPresentations: $enumPresentations,
        );

        return $this;
    }

    /**
     * @return array<string, AdvancedFilterFieldDefinition>
     */
    public function getAdvancedFilterFields(): array
    {
        return $this->advancedFilterFields;
    }
}
