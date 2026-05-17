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
        'sort' => 'bi bi-arrow-down-up',
        'sort_asc' => 'bi bi-arrow-up',
        'sort_desc' => 'bi bi-arrow-down',
        'filter' => 'bi bi-funnel',
        'export_csv' => 'bi bi-filetype-csv',
        'export_excel' => 'bi bi-filetype-xlsx',
        'action_view' => 'bi bi-eye',
        'action_edit' => 'bi bi-pencil',
        'action_delete' => 'bi bi-trash',
        'action_create' => 'bi bi-plus-lg',
        'bulk_actions' => 'bi bi-collection',
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
