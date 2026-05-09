<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Renderer;

use Twig\Environment;
use Zhortein\DatatableBundle\Definition\ColumnDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;

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

    private function createHtmlId(DatatableDefinition $definition): string
    {
        $name = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $definition->getName()) ?? $definition->getName();

        return 'zhortein-datatable-'.strtolower(trim($name, '-'));
    }
}
