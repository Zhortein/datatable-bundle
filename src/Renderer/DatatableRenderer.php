<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Renderer;

use Twig\Environment;
use Zhortein\DatatableBundle\Definition\ColumnDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Result\DatatableResult;

final readonly class DatatableRenderer
{
    public function __construct(
        private Environment $twig,
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
     * @return list<array{cells: list<array{column: ColumnDefinition, value: mixed}>}>
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
            ];
        }

        return $normalizedRows;
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
