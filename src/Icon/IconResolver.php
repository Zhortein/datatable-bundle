<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Icon;

use Zhortein\DatatableBundle\Contract\IconResolverInterface;

final readonly class IconResolver implements IconResolverInterface
{
    private const DEFAULT_ICONS = [
        'view' => 'bi bi-eye',
        'edit' => 'bi bi-pencil',
        'delete' => 'bi bi-trash',
        'add' => 'bi bi-plus-lg',
        'check' => 'bi bi-check-lg',
        'cancel' => 'bi bi-x-lg',
        'sort_neutral' => 'bi bi-arrow-down-up',
        'sort_asc' => 'bi bi-arrow-up',
        'sort_desc' => 'bi bi-arrow-down',
        'filter' => 'bi bi-funnel',
        'filter_active' => 'bi bi-funnel-fill',
        'export' => 'bi bi-download',
        'export_csv' => 'bi bi-filetype-csv',
        'export_xlsx' => 'bi bi-filetype-xlsx',
        'export_excel' => 'bi bi-filetype-xlsx',
        'action_view' => 'bi bi-eye',
        'action_edit' => 'bi bi-pencil',
        'action_delete' => 'bi bi-trash',
        'action_create' => 'bi bi-plus-lg',
        'bulk_actions' => 'bi bi-collection',
        'boolean_true' => 'bi bi-check-lg',
        'boolean_false' => 'bi bi-x-lg',
        'search_builder' => 'bi bi-sliders',
        'search_builder_add_condition' => 'bi bi-plus-lg',
        'search_builder_add_group' => 'bi bi-folder-plus',
        'search_builder_remove' => 'bi bi-trash',
        'column_visibility' => 'bi bi-layout-three-columns',
        'hierarchy_expand' => 'bi bi-chevron-right',
        'hierarchy_collapse' => 'bi bi-chevron-down',
        'pagination_previous' => 'bi bi-chevron-left',
        'pagination_next' => 'bi bi-chevron-right',
    ];

    /**
     * @param array<string, string> $icons
     */
    public function __construct(
        private array $icons = [],
    ) {
    }

    public function resolve(string $key): ?string
    {
        return $this->icons[$key] ?? self::DEFAULT_ICONS[$key] ?? null;
    }
}
