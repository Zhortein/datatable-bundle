<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Renderer;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;
use Zhortein\DatatableBundle\Action\ActionVisibilityCheckerInterface;
use Zhortein\DatatableBundle\Action\ActionVisibilityContext;
use Zhortein\DatatableBundle\Action\RowActionRouteParameterResolver;
use Zhortein\DatatableBundle\Cell\CellContext;
use Zhortein\DatatableBundle\Cell\CellContextFactory;
use Zhortein\DatatableBundle\Context\DatatableContextTransport;
use Zhortein\DatatableBundle\Contract\IconResolverInterface;
use Zhortein\DatatableBundle\Definition\ActionDefinition;
use Zhortein\DatatableBundle\Definition\BulkActionDefinition;
use Zhortein\DatatableBundle\Definition\ChildDatatableDefinition;
use Zhortein\DatatableBundle\Definition\ColumnDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\ActionDisplayMode;
use Zhortein\DatatableBundle\Enum\BooleanDisplayMode;
use Zhortein\DatatableBundle\Enum\CellType;
use Zhortein\DatatableBundle\Enum\FilterLayout;
use Zhortein\DatatableBundle\Enum\PaginationSize;
use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Exception\ChildDatatableAccessDeniedException;
use Zhortein\DatatableBundle\Hierarchy\ChildDatatableResolver;
use Zhortein\DatatableBundle\Hierarchy\ResolvedChildDatatable;
use Zhortein\DatatableBundle\Result\DatatableResult;
use Zhortein\DatatableBundle\State\DatatableStateUrlSerializer;
use Zhortein\DatatableBundle\Sorting\SortCriterion;
use Zhortein\DatatableBundle\View\DatatableViewCsrfTokenIdGenerator;
use Zhortein\DatatableBundle\View\DatatableViewScope;

final readonly class DatatableRenderer
{
    private CellContextFactory $cellContextFactory;

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
        private ?DatatableContextTransport $contextTransport = null,
        private string $theme = 'bootstrap',
        private int $defaultPageSize = 25,
        private bool $searchEnabled = false,
        private bool $searchBuilderEnabled = false,
        private array $defaultTableOptions = [],
        private ?DatatableStateUrlSerializer $stateUrlSerializer = null,
        ?CellContextFactory $cellContextFactory = null,
        private ?ChildDatatableResolver $childDatatableResolver = null,
    ) {
        $this->cellContextFactory = $cellContextFactory ?? new CellContextFactory();
    }

    /**
     * @param array<string, mixed> $options
     */
    public function render(DatatableDefinition $definition, array $options = []): string
    {
        $options = $this->prepareContextOptions($definition, $this->resolveOptions($options));

        $options['filterLayout'] = $this->resolveFilterLayout($options)->value;
        $options['paginationSize'] = $this->resolvePaginationSize($options)->value;
        $filters = $options['filters'] ?? [];
        $visibleColumns = $this->getVisibleColumns($definition, $options);

        $bulkActions = $this->normalizeBulkActions($definition, $options);

        return $this->twig->render(sprintf('@ZhorteinDatatable/%s/datatable.html.twig', $this->theme), array_merge([
            'definition' => $definition,
            'visibleColumns' => $visibleColumns,
            'columnClassNames' => $this->resolveColumnClassNames($visibleColumns),
            'rowActions' => $definition->getRowActions(),
            'globalActions' => $this->normalizeGlobalActions($definition, $options),
            'bulkActions' => $bulkActions,
            'hasRowActions' => [] !== $definition->getRowActions(),
            'hasBulkActions' => [] !== $bulkActions,
            'hasChildDatatable' => $this->canExpandChildDatatable($definition, $options),
            'htmlId' => $this->createHtmlId($definition, $options),
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
        $options = $this->prepareContextOptions($definition, $this->resolveOptions($options));
        $visibleColumns = $this->getVisibleColumns($definition, $options);

        return $this->twig->render(sprintf('@ZhorteinDatatable/%s/_header.html.twig', $this->theme), array_merge([
            'definition' => $definition,
            'visibleColumns' => $visibleColumns,
            'columnClassNames' => $this->resolveColumnClassNames($visibleColumns),
            'hasRowActions' => [] !== $definition->getRowActions(),
            'hasBulkActions' => $this->hasBulkActions($definition),
            'hasChildDatatable' => $this->canExpandChildDatatable($definition, $options),
            'htmlId' => $this->createHtmlId($definition, $options),
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

        $options = $this->prepareContextOptions($definition, $this->resolveOptions($options));
        $hasChildDatatable = $this->canExpandChildDatatable($definition, $options);
        $visibleColumns = $this->getVisibleColumns($definition, $options);

        return $this->twig->render(sprintf('@ZhorteinDatatable/%s/_body.html.twig', $this->theme), [
            'rows' => $this->normalizeRows($definition, $result, $options),
            'hasBulkActions' => $this->hasBulkActions($definition),
            'hasChildDatatable' => $hasChildDatatable,
            'childColspan' => count($visibleColumns)
                + ($this->hasBulkActions($definition) ? 1 : 0)
                + ([] !== $definition->getRowActions() ? 1 : 0)
                + ($hasChildDatatable ? 1 : 0),
            'htmlId' => $this->createHtmlId($definition, $options),
            'rowActionDisplayMode' => $this->resolveRowActionDisplayMode($definition, $options)->value,
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function renderEmptyBody(DatatableDefinition $definition, array $options = []): string
    {
        $options = $this->prepareContextOptions($definition, $this->resolveOptions($options));

        return $this->twig->render(sprintf('@ZhorteinDatatable/%s/_empty.html.twig', $this->theme), [
            'visibleColumns' => $this->getVisibleColumns($definition, $options),
            'hasRowActions' => [] !== $definition->getRowActions(),
            'hasBulkActions' => $this->hasBulkActions($definition),
            'hasChildDatatable' => $this->canExpandChildDatatable($definition, $options),
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function renderPagination(DatatableDefinition $definition, DatatableResult $result, array $options = []): string
    {
        $options = $this->prepareContextOptions($definition, $this->resolveOptions($options));
        $options['paginationSize'] = $this->resolvePaginationSize($options)->value;

        return $this->twig->render(sprintf('@ZhorteinDatatable/%s/_pagination.html.twig', $this->theme), [
            'htmlId' => $this->createHtmlId($definition, $options),
            'result' => $result,
            'options' => $options,
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function renderPaginationPlaceholder(DatatableDefinition $definition, array $options = []): string
    {
        $options = $this->prepareContextOptions($definition, $this->resolveOptions($options));
        $options['paginationSize'] = $this->resolvePaginationSize($options)->value;

        return $this->twig->render(sprintf('@ZhorteinDatatable/%s/_pagination.html.twig', $this->theme), [
            'htmlId' => $this->createHtmlId($definition, $options),
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
        return $this->normalizeSortOptions(array_replace(
            $this->defaultTableOptions,
            [
                'search' => $this->searchEnabled,
                'searchBuilder' => $this->searchBuilderEnabled,
                'pageSize' => $this->defaultPageSize,
            ],
            $options,
        ));
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function normalizeSortOptions(array $options): array
    {
        $rawSorts = $options['sorts'] ?? [];

        if (null === $rawSorts) {
            $rawSorts = [];
        }

        if (!is_array($rawSorts) || !array_is_list($rawSorts)) {
            throw new \InvalidArgumentException('The datatable render option "sorts" must be a list.');
        }

        $sorts = [];

        foreach ($rawSorts as $rawSort) {
            if ($rawSort instanceof SortCriterion) {
                $sorts[] = $rawSort;

                continue;
            }

            if (!is_array($rawSort)) {
                throw new \InvalidArgumentException('Every datatable render sort must be a SortCriterion or an array.');
            }

            $field = $rawSort['field'] ?? null;
            $direction = $rawSort['direction'] ?? SortDirection::Asc->value;

            if (!is_string($field) || (!is_string($direction) && !($direction instanceof SortDirection))) {
                throw new \InvalidArgumentException('Every datatable render sort must contain a string field and a valid direction.');
            }

            $sorts[] = SortCriterion::create($field, $direction);
        }

        $sorts = SortCriterion::normalizeList($sorts);

        if ([] === $sorts) {
            $field = $options['sortField'] ?? null;
            $direction = $options['sortDirection'] ?? SortDirection::Asc;

            if (null !== $field && !is_string($field)) {
                throw new \InvalidArgumentException('The datatable render option "sortField" must be a string or null.');
            }

            if (!is_string($direction) && !($direction instanceof SortDirection)) {
                throw new \InvalidArgumentException('The datatable render option "sortDirection" must be a string or SortDirection.');
            }

            if (is_string($field) && '' !== trim($field)) {
                $sorts = [SortCriterion::create($field, $direction)];
            }
        }

        $options['sorts'] = array_map(
            static fn (SortCriterion $criterion): array => $criterion->toArray(),
            $sorts,
        );

        $primary = $sorts[0] ?? null;
        $options['sortField'] = $primary?->getField();
        $options['sortDirection'] = $primary?->getDirection()->value ?? SortDirection::Asc->value;

        return $options;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function prepareContextOptions(DatatableDefinition $definition, array $options): array
    {
        if (array_key_exists('context', $options)) {
            $renderContext = $options['context'];

            if (!is_array($renderContext)) {
                throw new \InvalidArgumentException('The datatable render option "context" must be an array of browser-safe values.');
            }

            foreach (array_keys($renderContext) as $key) {
                if (!is_string($key)) {
                    throw new \InvalidArgumentException('The datatable render option "context" must use string keys.');
                }
            }

            /** @var array<string, mixed> $renderContext */
            $definition->setContext($definition->getContext()->withBrowserValues($renderContext));
            unset($options['context']);
        }

        $instance = $options['instance'] ?? $definition->getName();

        if (!is_string($instance)) {
            throw new \InvalidArgumentException('The datatable render option "instance" must be a string.');
        }

        $instance = null !== $this->contextTransport
            ? $this->contextTransport->normalizeInstance($instance)
            : trim($instance);

        if ('' === $instance) {
            throw new \InvalidArgumentException('The datatable render option "instance" must not be empty.');
        }

        $options['instance'] = $instance;

        $token = null;
        $forceContextToken = $options['forceContextToken'] ?? false;

        if (!is_bool($forceContextToken)) {
            throw new \InvalidArgumentException('The datatable render option "forceContextToken" must be a boolean.');
        }

        if (null !== $this->contextTransport) {
            $token = $forceContextToken
                ? $this->contextTransport->createRequiredToken(
                    $definition->getName(),
                    $instance,
                    $definition->getContext(),
                )
                : $this->contextTransport->createToken(
                    $definition->getName(),
                    $instance,
                    $definition->getContext(),
                );

            if (null !== $token) {
                $options['contextToken'] = $token;
                $options['fragmentsUrl'] = $this->contextTransport->appendToUrl(
                    $this->resolveStringOption(
                        $options,
                        'fragmentsUrl',
                        sprintf('/_zhortein/datatable/%s/fragments', $definition->getName()),
                    ),
                    $token,
                    $instance,
                );
                $options['exportUrl'] = $this->contextTransport->appendToUrl(
                    $this->resolveStringOption(
                        $options,
                        'exportUrl',
                        sprintf('/_zhortein/datatable/%s/export/csv', $definition->getName()),
                    ),
                    $token,
                    $instance,
                );

                $exportUrls = $options['exportUrls'] ?? [];

                if (!is_array($exportUrls)) {
                    throw new \InvalidArgumentException('The datatable render option "exportUrls" must be an array.');
                }

                $exportFormats = $options['exportFormats'] ?? ['csv'];

                if (!is_array($exportFormats)) {
                    throw new \InvalidArgumentException('The datatable render option "exportFormats" must be an array.');
                }

                foreach ($exportFormats as $format) {
                    if (!is_string($format) || !in_array($format, ['csv', 'xlsx'], true)) {
                        continue;
                    }

                    $formatUrl = $exportUrls[$format] ?? ('csv' === $format
                        ? $options['exportUrl']
                        : sprintf('/_zhortein/datatable/%s/export/%s', $definition->getName(), $format));

                    if (!is_string($formatUrl)) {
                        throw new \InvalidArgumentException(sprintf('The datatable export URL for format "%s" must be a string.', $format));
                    }

                    $exportUrls[$format] = $this->contextTransport->appendToUrl(
                        $formatUrl,
                        $token,
                        $instance,
                    );
                }

                $options['exportUrls'] = $exportUrls;
            }
        }

        $options = $this->prepareStateOptions($definition, $options, $token);

        return $this->prepareSavedViewOptions($definition, $options, $token);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function prepareStateOptions(
        DatatableDefinition $definition,
        array $options,
        ?string $contextToken = null,
    ): array {
        if (null === $this->stateUrlSerializer) {
            return $options;
        }

        /** @var string $instance */
        $instance = $options['instance'];
        $options['stateParameter'] = $this->stateUrlSerializer->createParameterName(
            $definition->getName(),
            $instance,
            $contextToken,
        );

        return $options;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function prepareSavedViewOptions(
        DatatableDefinition $definition,
        array $options,
        ?string $contextToken,
    ): array {
        $enabled = $options['savedViews'] ?? false;

        if (!is_bool($enabled)) {
            throw new \InvalidArgumentException('The datatable render option "savedViews" must be a boolean.');
        }

        if (!$enabled) {
            return $options;
        }

        if (null === $this->csrfTokenManager) {
            throw new \LogicException('The CSRF token manager is required when named datatable views are enabled.');
        }

        $namespace = $this->resolveStringOption($options, 'savedViewsScope', 'default');
        $locale = $this->resolveStringOption($options, 'savedViewsLocale', 'und');
        $includePage = $options['savedViewsIncludePage'] ?? false;

        if (!is_bool($includePage)) {
            throw new \InvalidArgumentException('The datatable render option "savedViewsIncludePage" must be a boolean.');
        }

        /** @var string $instance */
        $instance = $options['instance'];
        $scope = DatatableViewScope::create(
            datatableName: $definition->getName(),
            instance: $instance,
            namespace: $namespace,
            locale: $locale,
            contextFingerprint: null === $contextToken ? null : hash('sha256', $contextToken),
        );
        $parameters = [
            DatatableContextTransport::INSTANCE_QUERY_PARAMETER => $instance,
            DatatableViewScope::SCOPE_QUERY_PARAMETER => $scope->getNamespace(),
            DatatableViewScope::LOCALE_QUERY_PARAMETER => $scope->getLocale(),
        ];

        if (null !== $contextToken) {
            $parameters[DatatableContextTransport::CONTEXT_QUERY_PARAMETER] = $contextToken;
        }

        $options['savedViewsUrl'] = $this->appendQueryParameters(
            $this->resolveStringOption(
                $options,
                'savedViewsUrl',
                sprintf('/_zhortein/datatable/%s/views', $definition->getName()),
            ),
            $parameters,
        );
        $options['savedViewsIncludePage'] = $includePage;
        $options['savedViewsCsrfToken'] = $this->csrfTokenManager
            ->getToken(DatatableViewCsrfTokenIdGenerator::generate(
                $definition->getName(),
                $instance,
            ))
            ->getValue()
        ;

        return $options;
    }

    /**
     * @param array<string, string> $parameters
     */
    private function appendQueryParameters(string $url, array $parameters): string
    {
        [$urlWithoutFragment, $fragment] = $this->splitOnce($url, '#');
        [$path, $query] = $this->splitOnce($urlWithoutFragment, '?');
        $queryParts = [];

        if ('' !== $query) {
            foreach (explode('&', $query) as $queryPart) {
                if ('' === $queryPart) {
                    continue;
                }

                $parameterName = urldecode(explode('=', $queryPart, 2)[0]);

                if (array_key_exists($parameterName, $parameters)) {
                    continue;
                }

                $queryParts[] = $queryPart;
            }
        }

        foreach ($parameters as $name => $value) {
            $queryParts[] = rawurlencode($name).'='.rawurlencode($value);
        }

        return $path.'?'.implode('&', $queryParts).('' === $fragment ? '' : '#'.$fragment);
    }

    /**
     * @param non-empty-string $separator
     *
     * @return array{string, string}
     */
    private function splitOnce(string $value, string $separator): array
    {
        $parts = explode($separator, $value, 2);

        return [$parts[0], $parts[1] ?? ''];
    }

    /**
     * @param array<string, mixed> $options
     */
    private function resolveStringOption(array $options, string $name, string $default): string
    {
        $value = $options[$name] ?? $default;

        if (!is_string($value)) {
            throw new \InvalidArgumentException(sprintf('The datatable render option "%s" must be a string.', $name));
        }

        return $value;
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
     * @param array<string, mixed> $options
     *
     * @return list<array{name: string, label: string|null, icon: string|null, iconPosition: string, url: string, httpMethod: string, confirmationMessage: string|null, csrfToken: string|null, className: string|null, attributes: array<string, string>, selectedRowsParameterName: string|null, translationDomain: string|null, ajax: bool, ajaxSuccessStrategy: string|null}>
     */
    private function normalizeGlobalActions(DatatableDefinition $definition, array $options): array
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
                options: $options,
            );
        }

        return $actions;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return list<array{name: string, label: string|null, icon: string|null, iconPosition: string, url: string, httpMethod: string, confirmationMessage: string|null, csrfToken: string|null, className: string|null, attributes: array<string, string>, selectedRowsParameterName: string|null, translationDomain: string|null, ajax: bool, ajaxSuccessStrategy: string|null}>
     */
    private function normalizeBulkActions(DatatableDefinition $definition, array $options): array
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
                options: $options,
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
                throw new \LogicException(sprintf('The route parameter "%s" for action "%s" requires the configured route parameter resolver.', $name, $action->getName()));
            }

            $parameters[$name] = $parameter;
        }

        return $parameters;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return list<array{cells: list<array{context: CellContext, column: ColumnDefinition, value: mixed, template: string, className: string|null, booleanDisplayMode: string, booleanTrueIcon: string|null, booleanFalseIcon: string|null, translationDomain: string|null}>, actions: list<array{name: string, label: string|null, icon: string|null, iconPosition: string, url: string, httpMethod: string, confirmationMessage: string|null, csrfToken: string|null, className: string|null, attributes: array<string, string>, selectedRowsParameterName: string|null, translationDomain: string|null, ajax: bool, ajaxSuccessStrategy: string|null}>, identifier: string|null, child: array{name: string, instance: string, depth: int, url: string, targetId: string, expandLabel: string|null, collapseLabel: string|null, translationDomain: string|null}|null}>
     */
    private function normalizeRows(DatatableDefinition $definition, DatatableResult $result, array $options = []): array
    {
        $visibleColumns = $this->getVisibleColumns($definition, $options);
        $hasChildDatatable = $this->canExpandChildDatatable($definition, $options);
        $needsRowIdentifier = $this->hasBulkActions($definition) || $this->hasAjaxRowActions($definition) || $hasChildDatatable;
        $booleanDisplayMode = $this->resolveBooleanDisplayMode($options);
        $booleanTrueIcon = $this->iconResolver?->resolve('boolean_true');
        $booleanFalseIcon = $this->iconResolver?->resolve('boolean_false');
        $normalizedRows = [];

        foreach ($result->getRows() as $rowIndex => $row) {
            $cells = [];
            $source = $result->getSource($rowIndex);

            foreach ($visibleColumns as $column) {
                $cellContext = $this->normalizeCellContext(
                    $this->cellContextFactory->create($definition, $column, $row, $source),
                );

                $cells[] = [
                    'context' => $cellContext,
                    'column' => $column,
                    'value' => $cellContext->getValue(),
                    'template' => $this->resolveCellTemplate($column),
                    'className' => $this->resolveCellClassName($column),
                    'booleanDisplayMode' => $booleanDisplayMode->value,
                    'booleanTrueIcon' => $booleanTrueIcon,
                    'booleanFalseIcon' => $booleanFalseIcon,
                    'translationDomain' => $definition->getTranslationDomain(),
                ];
            }

            $identifier = $needsRowIdentifier
                ? $this->cellContextFactory->resolveRowIdentifier($row, $definition)
                : null;
            $normalizedRow = [
                'cells' => $cells,
                'actions' => $this->normalizeRowActions($definition, $row, $options),
                'identifier' => $identifier,
                'child' => $hasChildDatatable
                    ? $this->normalizeChildDatatable($definition, $row, $identifier, $options)
                    : null,
            ];

            $normalizedRows[] = $normalizedRow;
        }

        return $normalizedRows;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $options
     *
     * @return array{name: string, instance: string, depth: int, url: string, targetId: string, expandLabel: string|null, collapseLabel: string|null, translationDomain: string|null}|null
     */
    private function normalizeChildDatatable(
        DatatableDefinition $definition,
        array $row,
        ?string $rowIdentifier,
        array $options,
    ): ?array {
        if (null === $this->childDatatableResolver || null === $this->contextTransport) {
            return null;
        }

        try {
            $child = $this->childDatatableResolver->resolve(
                parentDefinition: $definition,
                row: $row,
                rowIdentifier: $rowIdentifier,
                parentInstance: $this->resolveStringOption($options, 'instance', $definition->getName()),
                parentDepth: $this->resolveChildDepth($options),
            );
        } catch (ChildDatatableAccessDeniedException) {
            return null;
        }

        $childDefinition = $definition->getChildDatatable();

        if (null === $childDefinition) {
            throw new \LogicException(sprintf('Datatable "%s" does not define a child datatable.', $definition->getName()));
        }

        return [
            'name' => $child->getName(),
            'instance' => $child->getInstance(),
            'depth' => $child->getDepth(),
            'url' => $this->contextTransport->appendToUrl(
                $this->createChildDatatableUrl($child),
                $child->getContextToken(),
                $child->getInstance(),
            ),
            'targetId' => $this->createHtmlId($definition, $options).'_'.$child->getInstance(),
            'expandLabel' => $childDefinition->getExpandLabel(),
            'collapseLabel' => $childDefinition->getCollapseLabel(),
            'translationDomain' => $definition->getTranslationDomain(),
        ];
    }

    private function createChildDatatableUrl(ResolvedChildDatatable $child): string
    {
        if (null !== $this->urlGenerator) {
            return $this->urlGenerator->generate('zhortein_datatable_child', [
                'name' => $child->getName(),
            ]);
        }

        return sprintf('/_zhortein/datatable/%s/child', rawurlencode($child->getName()));
    }

    /**
     * @param array<string, mixed> $options
     */
    private function canExpandChildDatatable(DatatableDefinition $definition, array $options): bool
    {
        $childDefinition = $definition->getChildDatatable();

        return null !== $childDefinition
            && null !== $this->childDatatableResolver
            && null !== $this->contextTransport
            && $this->resolveChildDepth($options) < $childDefinition->getMaxDepth();
    }

    /**
     * @param array<string, mixed> $options
     */
    private function resolveChildDepth(array $options): int
    {
        $depth = $options['childDepth'] ?? 0;

        if (!is_int($depth) || $depth < 0 || $depth > ChildDatatableDefinition::MAX_DEPTH) {
            throw new \InvalidArgumentException('The datatable render option "childDepth" must be an integer between 0 and 3.');
        }

        return $depth;
    }

    private function hasAjaxRowActions(DatatableDefinition $definition): bool
    {
        foreach ($definition->getRowActions() as $action) {
            if (null !== $action->getAjaxOptions()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $options
     *
     * @return list<array{name: string, label: string|null, icon: string|null, iconPosition: string, url: string, httpMethod: string, confirmationMessage: string|null, csrfToken: string|null, className: string|null, attributes: array<string, string>, selectedRowsParameterName: string|null, translationDomain: string|null, ajax: bool, ajaxSuccessStrategy: string|null}>
     */
    private function normalizeRowActions(DatatableDefinition $definition, array $row, array $options): array
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
                options: $options,
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
     * @param array<string, mixed> $options
     *
     * @return array{name: string, label: string|null, icon: string|null, iconPosition: string, url: string, httpMethod: string, confirmationMessage: string|null, csrfToken: string|null, className: string|null, attributes: array<string, string>, selectedRowsParameterName: string|null, translationDomain: string|null, ajax: bool, ajaxSuccessStrategy: string|null}
     */
    private function normalizeAction(
        ActionDefinition|BulkActionDefinition $action,
        string $url,
        ?string $translationDomain,
        array $options,
    ): array {
        $httpMethod = strtoupper($action->getHttpMethod());
        $ajax = $action->getAjaxOptions();

        if (
            null !== $ajax
            && null !== $this->contextTransport
            && is_string($options['contextToken'] ?? null)
            && is_string($options['instance'] ?? null)
        ) {
            $url = $this->contextTransport->appendToUrl(
                $url,
                $options['contextToken'],
                $options['instance'],
            );
        }

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
            'ajax' => null !== $ajax,
            'ajaxSuccessStrategy' => $ajax?->getSuccessStrategy()->value,
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

    private function normalizeCellContext(CellContext $context): CellContext
    {
        $value = $context->getValue();
        $column = $context->getColumn();

        if (
            !$column->isNegated()
            || CellType::Boolean !== CellType::fromNullableString($column->getType())
            || null === $value
        ) {
            return $context;
        }

        return $context->withValue(!(bool) $value);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function createHtmlId(DatatableDefinition $definition, array $options): string
    {
        $instance = is_string($options['instance'] ?? null)
            ? $options['instance']
            : $definition->getName();
        $identifier = $definition->getName() === $instance
            ? $definition->getName()
            : $definition->getName().'-'.$instance;
        $name = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $identifier) ?? $identifier;

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
