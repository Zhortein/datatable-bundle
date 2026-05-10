<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Renderer;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;
use Zhortein\DatatableBundle\Action\RowActionRouteParameterResolver;
use Zhortein\DatatableBundle\Definition\ActionDefinition;
use Zhortein\DatatableBundle\Definition\ColumnDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\CellType;
use Zhortein\DatatableBundle\Result\DatatableResult;

final readonly class DatatableRenderer
{
    public function __construct(
        private Environment $twig,
        private ?UrlGeneratorInterface $urlGenerator = null,
        private ?RowActionRouteParameterResolver $routeParameterResolver = null,
        private ?CsrfTokenManagerInterface $csrfTokenManager = null,
        private string $theme = 'bootstrap',
        private int $defaultPageSize = 25,
        private bool $searchEnabled = false,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function render(DatatableDefinition $definition, array $options = []): string
    {
        $options = array_replace([
            'search' => $this->searchEnabled,
            'pageSize' => $this->defaultPageSize,
        ], $options);

        return $this->twig->render(sprintf('@ZhorteinDatatable/%s/datatable.html.twig', $this->theme), [
            'definition' => $definition,
            'visibleColumns' => $this->getVisibleColumns($definition),
            'rowActions' => $definition->getRowActions(),
            'globalActions' => $this->normalizeGlobalActions($definition),
            'hasRowActions' => [] !== $definition->getRowActions(),
            'htmlId' => $this->createHtmlId($definition),
            'options' => $options,
        ]);
    }

    public function renderBody(DatatableDefinition $definition, DatatableResult $result): string
    {
        if ($result->isEmpty()) {
            return $this->renderEmptyBody($definition);
        }

        return $this->twig->render(sprintf('@ZhorteinDatatable/%s/_body.html.twig', $this->theme), [
            'rows' => $this->normalizeRows($definition, $result),
        ]);
    }

    public function renderEmptyBody(DatatableDefinition $definition): string
    {
        return $this->twig->render(sprintf('@ZhorteinDatatable/%s/_empty.html.twig', $this->theme), [
            'visibleColumns' => $this->getVisibleColumns($definition),
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
     * @return array<string, ColumnDefinition>
     */
    private function getVisibleColumns(DatatableDefinition $definition): array
    {
        return array_filter(
            $definition->getColumns(),
            static fn (ColumnDefinition $column): bool => $column->isVisible(),
        );
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
     * @return list<array{cells: list<array{column: ColumnDefinition, value: mixed, template: string}>, actions: list<array{name: string, label: string|null, icon: string|null, url: string, httpMethod: string, csrfToken: string|null, className: string|null, attributes: array<string, string>}>}>
     */
    private function normalizeRows(DatatableDefinition $definition, DatatableResult $result): array
    {
        $visibleColumns = $this->getVisibleColumns($definition);
        $normalizedRows = [];

        foreach ($result->getRows() as $row) {
            $cells = [];

            foreach ($visibleColumns as $column) {
                $cells[] = [
                    'column' => $column,
                    'value' => $this->readColumnValue($row, $column),
                    'template' => $this->resolveCellTemplate($column),
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

    /**
     * @param array<string, mixed> $row
     */
    private function readColumnValue(array $row, ColumnDefinition $column): mixed
    {
        $columnName = $column->getName();

        if (array_key_exists($columnName, $row)) {
            return $row[$columnName];
        }

        $normalizedName = $this->normalizeColumnName($columnName);

        return $row[$normalizedName] ?? null;
    }

    private function normalizeColumnName(string $columnName): string
    {
        if (!str_contains($columnName, '.')) {
            return $columnName;
        }

        $parts = explode('.', $columnName);
        $lastPart = $parts[array_key_last($parts)];

        return '' !== $lastPart ? $lastPart : $columnName;
    }

    private function createHtmlId(DatatableDefinition $definition): string
    {
        $name = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $definition->getName()) ?? $definition->getName();

        return 'zhortein-datatable-'.strtolower(trim($name, '-'));
    }
}
