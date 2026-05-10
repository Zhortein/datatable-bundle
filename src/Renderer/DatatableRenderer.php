<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Renderer;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;
use Zhortein\DatatableBundle\Action\ActionVisibilityCheckerInterface;
use Zhortein\DatatableBundle\Action\ActionVisibilityContext;
use Zhortein\DatatableBundle\Action\RowActionRouteParameterResolver;
use Zhortein\DatatableBundle\Definition\ActionDefinition;
use Zhortein\DatatableBundle\Definition\ColumnDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\CellType;
use Zhortein\DatatableBundle\Result\DatatableResult;

final readonly class DatatableRenderer
{
    /**
     * @param array<string, bool> $defaultTableOptions
     */
    public function __construct(
        private Environment $twig,
        private ?UrlGeneratorInterface $urlGenerator = null,
        private ?RowActionRouteParameterResolver $routeParameterResolver = null,
        private ?ActionVisibilityCheckerInterface $actionVisibilityChecker = null,
        private ?CsrfTokenManagerInterface $csrfTokenManager = null,
        private string $theme = 'bootstrap',
        private int $defaultPageSize = 25,
        private bool $searchEnabled = false,
        private array $defaultTableOptions = [],
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function render(DatatableDefinition $definition, array $options = []): string
    {
        $options = array_replace(
            $this->defaultTableOptions,
            [
                'search' => $this->searchEnabled,
                'pageSize' => $this->defaultPageSize,
            ],
            $options,
        );

        return $this->twig->render(sprintf('@ZhorteinDatatable/%s/datatable.html.twig', $this->theme), [
            'definition' => $definition,
            'visibleColumns' => $this->getVisibleColumns($definition, $options),
            'rowActions' => $definition->getRowActions(),
            'globalActions' => $this->normalizeGlobalActions($definition),
            'hasRowActions' => [] !== $definition->getRowActions(),
            'htmlId' => $this->createHtmlId($definition),
            'options' => $options,
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function renderBody(DatatableDefinition $definition, DatatableResult $result, array $options = []): string
    {
        if ($result->isEmpty()) {
            return $this->renderEmptyBody($definition, $options);
        }

        return $this->twig->render(sprintf('@ZhorteinDatatable/%s/_body.html.twig', $this->theme), [
            'rows' => $this->normalizeRows($definition, $result, $options),
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function renderEmptyBody(DatatableDefinition $definition, array $options = []): string
    {
        return $this->twig->render(sprintf('@ZhorteinDatatable/%s/_empty.html.twig', $this->theme), [
            'visibleColumns' => $this->getVisibleColumns($definition, $options),
            'hasRowActions' => [] !== $definition->getRowActions(),
        ]);
    }

    public function renderPagination(DatatableDefinition $definition, DatatableResult $result): string
    {
        return $this->twig->render(sprintf('@ZhorteinDatatable/%s/_pagination.html.twig', $this->theme), [
            'htmlId' => $this->createHtmlId($definition),
            'result' => $result,
        ]);
    }

    public function renderPaginationPlaceholder(DatatableDefinition $definition): string
    {
        return $this->twig->render(sprintf('@ZhorteinDatatable/%s/_pagination.html.twig', $this->theme), [
            'htmlId' => $this->createHtmlId($definition),
        ]);
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
     * @return list<array{name: string, label: string|null, icon: string|null, url: string, httpMethod: string, csrfToken: string|null, className: string|null, attributes: array<string, string>}>
     */
    private function normalizeGlobalActions(DatatableDefinition $definition): array
    {
        if (null === $this->urlGenerator) {
            return [];
        }

        $actions = [];

        foreach ($definition->getGlobalActions() as $action) {
            $actions[] = $this->normalizeAction(
                action: $action,
                url: $this->urlGenerator->generate($action->getRoute(), $this->normalizeStaticRouteParameters($action)),
            );
        }

        return $actions;
    }

    /**
     * @return array<string, string>
     */
    private function normalizeStaticRouteParameters(ActionDefinition $action): array
    {
        return $action->getRouteParameters();
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return list<array{cells: list<array{column: ColumnDefinition, value: mixed, template: string, className: string|null}>, actions: list<array{name: string, label: string|null, icon: string|null, url: string, httpMethod: string, csrfToken: string|null, className: string|null, attributes: array<string, string>}>}>
     */
    private function normalizeRows(DatatableDefinition $definition, DatatableResult $result, array $options = []): array
    {
        $visibleColumns = $this->getVisibleColumns($definition, $options);
        $normalizedRows = [];

        foreach ($result->getRows() as $row) {
            $cells = [];

            foreach ($visibleColumns as $column) {
                $cells[] = [
                    'column' => $column,
                    'value' => $this->readColumnValue($row, $column),
                    'template' => $this->resolveCellTemplate($column),
                    'className' => $this->resolveCellClassName($column),
                ];
            }

            $normalizedRows[] = [
                'cells' => $cells,
                'actions' => $this->normalizeRowActions($definition, $row),
            ];
        }

        return $normalizedRows;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return list<array{name: string, label: string|null, icon: string|null, url: string, httpMethod: string, csrfToken: string|null, className: string|null, attributes: array<string, string>}>
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
                    $this->routeParameterResolver->resolve($action, $row),
                ),
            );
        }

        return $actions;
    }

    /**
     * @param array<string, mixed>|null $row
     */
    private function isActionVisible(ActionDefinition $action, DatatableDefinition $definition, ?array $row): bool
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
     * @return array{name: string, label: string|null, icon: string|null, url: string, httpMethod: string, csrfToken: string|null, className: string|null, attributes: array<string, string>}
     */
    private function normalizeAction(ActionDefinition $action, string $url): array
    {
        $httpMethod = strtoupper($action->getHttpMethod());

        return [
            'name' => $action->getName(),
            'label' => $action->getLabel(),
            'icon' => $action->getIcon(),
            'url' => $url,
            'httpMethod' => $httpMethod,
            'csrfToken' => $this->generateCsrfToken($action, $httpMethod),
            'className' => $action->getClassName(),
            'attributes' => $action->getAttributes(),
        ];
    }

    private function generateCsrfToken(ActionDefinition $action, string $httpMethod): ?string
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
            CellType::Numeric => 'text-end',
            CellType::Boolean, CellType::Enum => 'text-center',
            default => null,
        };
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
}
