<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Renderer;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;
use Zhortein\DatatableBundle\Action\ActionVisibilityCheckerInterface;
use Zhortein\DatatableBundle\Action\ActionVisibilityContext;
use Zhortein\DatatableBundle\Action\RowActionRouteParameterResolver;
use Zhortein\DatatableBundle\Contract\IconResolverInterface;
use Zhortein\DatatableBundle\Definition\ActionDefinition;
use Zhortein\DatatableBundle\Definition\BulkActionDefinition;
use Zhortein\DatatableBundle\Definition\ColumnDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\ActionDisplayMode;
use Zhortein\DatatableBundle\Enum\BooleanDisplayMode;
use Zhortein\DatatableBundle\Enum\CellType;
use Zhortein\DatatableBundle\Enum\FilterLayout;
use Zhortein\DatatableBundle\Enum\PaginationSize;
use Zhortein\DatatableBundle\Result\DatatableResult;

final readonly class DatatableRenderer
{
    /**
     * @param array<string, bool> $defaultTableOptions
     */
    public function __construct(
        private Environment $twig,
        private ?IconResolverInterface $iconResolver = null,
        private ?UrlGeneratorInterface $urlGenerator = null,
        private ?RowActionRouteParameterResolver $routeParameterResolver = null,
        private ?ActionVisibilityCheckerInterface $actionVisibilityChecker = null,
        private ?CsrfTokenManagerInterface $csrfTokenManager = null,
        private string $theme = 'bootstrap',
        private int $defaultPageSize = 25,
        private bool $searchEnabled = false,
        private bool $searchBuilderEnabled = false,
        private array $defaultTableOptions = [],
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function render(DatatableDefinition $definition, array $options = []): string
    {
        $options = $this->resolveOptions($options);

        $options['filterLayout'] = $this->resolveFilterLayout($options)->value;
        $options['paginationSize'] = $this->resolvePaginationSize($options)->value;
        $filters = $options['filters'] ?? [];
        $visibleColumns = $this->getVisibleColumns($definition, $options);

        $bulkActions = $this->normalizeBulkActions($definition);

        return $this->twig->render(sprintf('@ZhorteinDatatable/%s/datatable.html.twig', $this->theme), array_merge([
            'definition' => $definition,
            'visibleColumns' => $visibleColumns,
            'columnClassNames' => $this->resolveColumnClassNames($visibleColumns),
            'rowActions' => $definition->getRowActions(),
            'globalActions' => $this->normalizeGlobalActions($definition),
            'bulkActions' => $bulkActions,
            'hasRowActions' => [] !== $definition->getRowActions(),
            'hasBulkActions' => [] !== $bulkActions,
            'htmlId' => $this->createHtmlId($definition),
            'options' => $options,
            'filters' => $filters,
            'rowActionDisplayMode' => $this->resolveRowActionDisplayMode($definition, $options)->value,
        ], $this->resolveCommonIcons()));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function renderHeader(DatatableDefinition $definition, array $options = []): string
    {
        $options = $this->resolveOptions($options);
        $visibleColumns = $this->getVisibleColumns($definition, $options);

        return $this->twig->render(sprintf('@ZhorteinDatatable/%s/_header.html.twig', $this->theme), array_merge([
            'definition' => $definition,
            'visibleColumns' => $visibleColumns,
            'columnClassNames' => $this->resolveColumnClassNames($visibleColumns),
            'hasRowActions' => [] !== $definition->getRowActions(),
            'hasBulkActions' => $this->hasBulkActions($definition),
            'htmlId' => $this->createHtmlId($definition),
            'options' => $options,
            'filters' => $options['filters'] ?? [],
        ], $this->resolveCommonIcons()));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function renderBody(DatatableDefinition $definition, DatatableResult $result, array $options = []): string
    {
        if ($result->isEmpty()) {
            return $this->renderEmptyBody($definition, $options);
        }

        $options = $this->resolveOptions($options);

        return $this->twig->render(sprintf('@ZhorteinDatatable/%s/_body.html.twig', $this->theme), [
            'rows' => $this->normalizeRows($definition, $result, $options),
            'hasBulkActions' => $this->hasBulkActions($definition),
            'htmlId' => $this->createHtmlId($definition),
            'rowActionDisplayMode' => $this->resolveRowActionDisplayMode($definition, $options)->value,
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function renderEmptyBody(DatatableDefinition $definition, array $options = []): string
    {
        $options = $this->resolveOptions($options);

        return $this->twig->render(sprintf('@ZhorteinDatatable/%s/_empty.html.twig', $this->theme), [
            'visibleColumns' => $this->getVisibleColumns($definition, $options),
            'hasRowActions' => [] !== $definition->getRowActions(),
            'hasBulkActions' => $this->hasBulkActions($definition),
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function renderPagination(DatatableDefinition $definition, DatatableResult $result, array $options = []): string
    {
        $options = $this->resolveOptions($options);
        $options['paginationSize'] = $this->resolvePaginationSize($options)->value;

        return $this->twig->render(sprintf('@ZhorteinDatatable/%s/_pagination.html.twig', $this->theme), [
            'htmlId' => $this->createHtmlId($definition),
            'result' => $result,
            'options' => $options,
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function renderPaginationPlaceholder(DatatableDefinition $definition, array $options = []): string
    {
        $options = $this->resolveOptions($options);
        $options['paginationSize'] = $this->resolvePaginationSize($options)->value;

        return $this->twig->render(sprintf('@ZhorteinDatatable/%s/_pagination.html.twig', $this->theme), [
            'htmlId' => $this->createHtmlId($definition),
            'options' => $options,
        ]);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function resolveOptions(array $options): array
    {
        return array_replace(
            $this->defaultTableOptions,
            [
                'search' => $this->searchEnabled,
                'searchBuilder' => $this->searchBuilderEnabled,
                'pageSize' => $this->defaultPageSize,
            ],
            $options,
        );
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, ColumnDefinition>
     */
    private function getVisibleColumns(DatatableDefinition $definition, array $options = []): array
    {
        $visibleColumns = $this->normalizeColumnListOption($options['visibleColumns'] ?? []);
        $hiddenColumns = $this->normalizeColumnListOption($options['hiddenColumns'] ?? []);

        return array_filter(
            $definition->getColumns(),
            static function (ColumnDefinition $column) use ($visibleColumns, $hiddenColumns): bool {
                if (!$column->isVisible()) {
                    return false;
                }

                if ([] !== $visibleColumns && !in_array($column->getName(), $visibleColumns, true)) {
                    return false;
                }

                return !in_array($column->getName(), $hiddenColumns, true);
            },
        );
    }

    /**
     * @return list<string>
     */
    private function normalizeColumnListOption(mixed $columns): array
    {
        if (!is_array($columns)) {
            return [];
        }

        $normalizedColumns = [];

        foreach ($columns as $column) {
            if (!is_string($column)) {
                continue;
            }

            $column = trim($column);

            if ('' === $column) {
                continue;
            }

            $normalizedColumns[] = $column;
        }

        return array_values(array_unique($normalizedColumns));
    }

    /**
     * @return list<array{name: string, label: string|null, icon: string|null, iconPosition: string, url: string, httpMethod: string, confirmationMessage: string|null, csrfToken: string|null, className: string|null, attributes: array<string, string>, selectedRowsParameterName: string|null, translationDomain: string|null}>
     */
    private function normalizeGlobalActions(DatatableDefinition $definition): array
    {
        if (null === $this->urlGenerator) {
            return [];
        }

        $actions = [];

        foreach ($definition->getGlobalActions() as $action) {
            if (!$this->isActionVisible($action, $definition, null)) {
                continue;
            }

            $actions[] = $this->normalizeAction(
                action: $action,
                url: $this->urlGenerator->generate(
                    $action->getRoute(),
                    null !== $this->routeParameterResolver
                        ? $this->routeParameterResolver->resolveGlobalAction($action, $definition->getContext())
                        : $this->normalizeLegacyStaticRouteParameters($action),
                ),
                translationDomain: $definition->getTranslationDomain(),
            );
        }

        return $actions;
    }

    /**
     * @return list<array{name: string, label: string|null, icon: string|null, iconPosition: string, url: string, httpMethod: string, confirmationMessage: string|null, csrfToken: string|null, className: string|null, attributes: array<string, string>, selectedRowsParameterName: string|null, translationDomain: string|null}>
     */
    private function normalizeBulkActions(DatatableDefinition $definition): array
    {
        if (null === $this->urlGenerator) {
            return [];
        }

        $actions = [];

        foreach ($definition->getBulkActions() as $action) {
            if (!$this->isActionVisible($action, $definition, null)) {
                continue;
            }

            $actions[] = $this->normalizeAction(
                action: $action,
                url: $this->urlGenerator->generate(
                    $action->getRoute(),
                    null !== $this->routeParameterResolver
                        ? $this->routeParameterResolver->resolveBulkAction($action, $definition->getContext())
                        : $this->normalizeLegacyStaticRouteParameters($action),
                ),
                translationDomain: $definition->getTranslationDomain(),
            );
        }

        return $actions;
    }

    private function hasBulkActions(DatatableDefinition $definition): bool
    {
        foreach ($definition->getBulkActions() as $action) {
            if ($this->isActionVisible($action, $definition, null)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, string>
     */
    private function normalizeLegacyStaticRouteParameters(ActionDefinition|BulkActionDefinition $action): array
    {
        $parameters = [];

        foreach ($action->getRouteParameters() as $name => $parameter) {
            if (!is_string($parameter)) {
                throw new \LogicException(sprintf(
                    'The route parameter "%s" for action "%s" requires the configured route parameter resolver.',
                    $name,
                    $action->getName(),
                ));
            }

            $parameters[$name] = $parameter;
        }

        return $parameters;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return list<array{cells: list<array{column: ColumnDefinition, value: mixed, template: string, className: string|null, booleanDisplayMode: string, booleanTrueIcon: string|null, booleanFalseIcon: string|null, translationDomain: string|null}>, actions: list<array{name: string, label: string|null, icon: string|null, iconPosition: string, url: string, httpMethod: string, confirmationMessage: string|null, csrfToken: string|null, className: string|null, attributes: array<string, string>, selectedRowsParameterName: string|null, translationDomain: string|null}>, identifier: string|null}>
     */
    private function normalizeRows(DatatableDefinition $definition, DatatableResult $result, array $options = []): array
    {
        $visibleColumns = $this->getVisibleColumns($definition, $options);
        $hasBulkActions = $this->hasBulkActions($definition);
        $booleanDisplayMode = $this->resolveBooleanDisplayMode($options);
        $booleanTrueIcon = $this->iconResolver?->resolve('boolean_true');
        $booleanFalseIcon = $this->iconResolver?->resolve('boolean_false');
        $normalizedRows = [];

        foreach ($result->getRows() as $row) {
            $cells = [];

            foreach ($visibleColumns as $column) {
                $cells[] = [
                    'column' => $column,
                    'value' => $this->resolveCellValue($row, $column),
                    'template' => $this->resolveCellTemplate($column),
                    'className' => $this->resolveCellClassName($column),
                    'booleanDisplayMode' => $booleanDisplayMode->value,
                    'booleanTrueIcon' => $booleanTrueIcon,
                    'booleanFalseIcon' => $booleanFalseIcon,
                    'translationDomain' => $definition->getTranslationDomain(),
                ];
            }

            $normalizedRow = [
                'cells' => $cells,
                'actions' => $this->normalizeRowActions($definition, $row),
                'identifier' => null,
            ];

            if ($hasBulkActions) {
                $normalizedRow['identifier'] = $this->resolveRowIdentifier($row, $definition);
            }

            $normalizedRows[] = $normalizedRow;
        }

        return $normalizedRows;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function resolveRowIdentifier(array $row, DatatableDefinition $definition): ?string
    {
        $identifierKey = $definition->getOption('identifier');

        if (is_string($identifierKey)) {
            $value = $row[$identifierKey] ?? null;

            return is_scalar($value) ? (string) $value : null;
        }

        foreach (['id', 'e_id'] as $candidate) {
            if (array_key_exists($candidate, $row)) {
                $value = $row[$candidate];

                return is_scalar($value) ? (string) $value : null;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return list<array{name: string, label: string|null, icon: string|null, iconPosition: string, url: string, httpMethod: string, confirmationMessage: string|null, csrfToken: string|null, className: string|null, attributes: array<string, string>, selectedRowsParameterName: string|null, translationDomain: string|null}>
     */
    private function normalizeRowActions(DatatableDefinition $definition, array $row): array
    {
        if (null === $this->urlGenerator || null === $this->routeParameterResolver) {
            return [];
        }

        $actions = [];

        foreach ($definition->getRowActions() as $action) {
            if (!$this->isActionVisible($action, $definition, $row)) {
                continue;
            }

            $actions[] = $this->normalizeAction(
                action: $action,
                url: $this->urlGenerator->generate(
                    $action->getRoute(),
                    $this->routeParameterResolver->resolve($action, $row, $definition->getContext()),
                ),
                translationDomain: $definition->getTranslationDomain(),
            );
        }

        return $actions;
    }

    /**
     * @param array<string, mixed>|null $row
     */
    private function isActionVisible(ActionDefinition|BulkActionDefinition $action, DatatableDefinition $definition, ?array $row): bool
    {
        if (null === $this->actionVisibilityChecker) {
            return true;
        }

        return $this->actionVisibilityChecker->isVisible(
            $action,
            new ActionVisibilityContext(
                definition: $definition,
                row: $row,
            ),
        );
    }

    /**
     * @return array{name: string, label: string|null, icon: string|null, iconPosition: string, url: string, httpMethod: string, confirmationMessage: string|null, csrfToken: string|null, className: string|null, attributes: array<string, string>, selectedRowsParameterName: string|null, translationDomain: string|null}
     */
    private function normalizeAction(
        ActionDefinition|BulkActionDefinition $action,
        string $url,
        ?string $translationDomain,
    ): array {
        $httpMethod = strtoupper($action->getHttpMethod());

        return [
            'name' => $action->getName(),
            'label' => $action->getLabel(),
            'icon' => $this->resolveActionIcon($action),
            'iconPosition' => $action->getIconPosition()->value,
            'url' => $url,
            'httpMethod' => $httpMethod,
            'confirmationMessage' => $action->getConfirmationMessage(),
            'csrfToken' => $this->generateCsrfToken($action, $httpMethod),
            'className' => $action->getClassName(),
            'attributes' => $action->getAttributes(),
            'selectedRowsParameterName' => $action instanceof BulkActionDefinition ? $action->getSelectedRowsParameterName() : null,
            'translationDomain' => $translationDomain,
        ];
    }

    private function resolveActionIcon(ActionDefinition|BulkActionDefinition $action): ?string
    {
        if (null !== $action->getIcon()) {
            return $action->getIcon();
        }

        if (null === $this->iconResolver) {
            return null;
        }

        $name = $action->getName();

        $icon = match ($name) {
            'view', 'show' => $this->iconResolver->resolve('action_view'),
            'edit' => $this->iconResolver->resolve('action_edit'),
            'delete', 'remove' => $this->iconResolver->resolve('action_delete'),
            'create' => $this->iconResolver->resolve('action_create'),
            default => $this->iconResolver->resolve(sprintf('action_%s', $name)),
        };

        if (null !== $icon) {
            return $icon;
        }

        if ($action instanceof BulkActionDefinition) {
            return $this->iconResolver->resolve('bulk_actions');
        }

        return null;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function resolveRowActionDisplayMode(DatatableDefinition $definition, array $options): ActionDisplayMode
    {
        $runtimeMode = $options['rowActionDisplayMode'] ?? null;

        if (is_string($runtimeMode)) {
            return ActionDisplayMode::fromNullableString($runtimeMode);
        }

        $definitionMode = $definition->getOption('rowActionDisplayMode');

        return ActionDisplayMode::fromNullableString(is_string($definitionMode) ? $definitionMode : null);
    }

    private function generateCsrfToken(ActionDefinition|BulkActionDefinition $action, string $httpMethod): ?string
    {
        if ('GET' === $httpMethod || null === $this->csrfTokenManager) {
            return null;
        }

        return $this->csrfTokenManager
            ->getToken(sprintf('zhortein_datatable_action_%s', $action->getName()))
            ->getValue()
        ;
    }

    private function resolveCellTemplate(ColumnDefinition $column): string
    {
        if (null !== $column->getTemplate()) {
            return $column->getTemplate();
        }

        $cellType = CellType::fromNullableString($column->getType());

        return sprintf(
            '@ZhorteinDatatable/%s/cell/%s.html.twig',
            $this->theme,
            $cellType->getTemplateName(),
        );
    }

    private function resolveCellClassName(ColumnDefinition $column): ?string
    {
        if (null !== $column->getClassName() && '' !== trim($column->getClassName())) {
            return $column->getClassName();
        }

        return match (CellType::fromNullableString($column->getType())) {
            CellType::Numeric => 'text-end align-middle',
            CellType::Boolean, CellType::Enum => 'text-center align-middle',
            default => null,
        };
    }

    /**
     * @param array<string, ColumnDefinition> $columns
     *
     * @return array<string, string|null>
     */
    private function resolveColumnClassNames(array $columns): array
    {
        $classNames = [];

        foreach ($columns as $column) {
            $classNames[$column->getName()] = $this->resolveCellClassName($column);
        }

        return $classNames;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function resolveCellValue(array $row, ColumnDefinition $column): mixed
    {
        $value = $this->readColumnValue($row, $column);

        if (
            !$column->isNegated()
            || CellType::Boolean !== CellType::fromNullableString($column->getType())
            || null === $value
        ) {
            return $value;
        }

        return !(bool) $value;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function readColumnValue(array $row, ColumnDefinition $column): mixed
    {
        foreach ($this->getColumnValueCandidateKeys($column->getName()) as $candidateKey) {
            if (array_key_exists($candidateKey, $row)) {
                return $row[$candidateKey];
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function getColumnValueCandidateKeys(string $columnName): array
    {
        $candidateKeys = [$columnName];

        if (str_contains($columnName, '.')) {
            $candidateKeys[] = str_replace('.', '_', $columnName);

            $parts = explode('.', $columnName);
            $lastPart = $parts[array_key_last($parts)];

            if ('' !== $lastPart) {
                $candidateKeys[] = $lastPart;
            }
        }

        return array_values(array_unique($candidateKeys));
    }

    private function createHtmlId(DatatableDefinition $definition): string
    {
        $name = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $definition->getName()) ?? $definition->getName();

        return 'zhortein-datatable-'.strtolower(trim($name, '-'));
    }

    /**
     * @param array<string, mixed> $options
     */
    private function resolveBooleanDisplayMode(array $options): BooleanDisplayMode
    {
        $mode = $options['booleanDisplayMode'] ?? null;

        return BooleanDisplayMode::fromNullableString(is_string($mode) ? $mode : null);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function resolveFilterLayout(array $options): FilterLayout
    {
        $layout = $options['filterLayout'] ?? null;

        return FilterLayout::fromNullableString(is_string($layout) ? $layout : null);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function resolvePaginationSize(array $options): PaginationSize
    {
        $size = $options['paginationSize'] ?? null;

        if (null === $size && (bool) ($options['tableSmall'] ?? false)) {
            return PaginationSize::Small;
        }

        return PaginationSize::fromNullableString(is_string($size) ? $size : null);
    }

    /**
     * @return array{sort_neutral: string|null, sort_asc: string|null, sort_desc: string|null, filter_icon: string|null, filter_active_icon: string|null, export_icon: string|null, export_csv_icon: string|null, export_xlsx_icon: string|null}
     */
    private function resolveCommonIcons(): array
    {
        return [
            'sort_neutral' => $this->iconResolver?->resolve('sort_neutral'),
            'sort_asc' => $this->iconResolver?->resolve('sort_asc'),
            'sort_desc' => $this->iconResolver?->resolve('sort_desc'),
            'filter_icon' => $this->iconResolver?->resolve('filter'),
            'filter_active_icon' => $this->iconResolver?->resolve('filter_active'),
            'export_icon' => $this->iconResolver?->resolve('export'),
            'export_csv_icon' => $this->iconResolver?->resolve('export_csv'),
            'export_xlsx_icon' => $this->iconResolver?->resolve('export_xlsx'),
            'search_builder_icon' => $this->iconResolver?->resolve('search_builder'),
        ];
    }
}
