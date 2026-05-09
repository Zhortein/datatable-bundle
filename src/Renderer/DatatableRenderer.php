<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Renderer;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Zhortein\DatatableBundle\Action\RowActionRouteParameterResolver;
use Zhortein\DatatableBundle\Definition\ColumnDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Result\DatatableResult;

final readonly class DatatableRenderer
{
    public function __construct(
        private Environment $twig,
        private ?UrlGeneratorInterface $urlGenerator = null,
        private ?RowActionRouteParameterResolver $routeParameterResolver = null,
        private string $theme = 'bootstrap',
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function render(DatatableDefinition $definition, array $options = []): string
    {
        return $this->twig->render(sprintf('@ZhorteinDatatable/%s/datatable.html.twig', $this->theme), [
            'definition' => $definition,
            'visibleColumns' => $this->getVisibleColumns($definition),
            'rowActions' => $definition->getRowActions(),
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
     * @return list<array{cells: list<array{column: ColumnDefinition, value: mixed}>, actions: list<array{name: string, label: string|null, icon: string|null, url: string, className: string|null, attributes: array<string, string>}>}>
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
     * @return list<array{name: string, label: string|null, icon: string|null, url: string, className: string|null, attributes: array<string, string>}>
     */
    private function normalizeRowActions(DatatableDefinition $definition, array $row): array
    {
        if (null === $this->urlGenerator || null === $this->routeParameterResolver) {
            return [];
        }

        $actions = [];

        foreach ($definition->getRowActions() as $action) {
            if ('GET' !== strtoupper($action->getHttpMethod())) {
                continue;
            }

            $actions[] = [
                'name' => $action->getName(),
                'label' => $action->getLabel(),
                'icon' => $action->getIcon(),
                'url' => $this->urlGenerator->generate(
                    $action->getRoute(),
                    $this->routeParameterResolver->resolve($action, $row),
                ),
                'className' => $action->getClassName(),
                'attributes' => $action->getAttributes(),
            ];
        }

        return $actions;
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
