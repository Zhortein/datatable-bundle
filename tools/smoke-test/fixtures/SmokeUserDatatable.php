<?php

declare(strict_types=1);

namespace App\Datatable;

use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterType;
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;

#[AsDatatable(name: 'smoke-users', provider: 'array')]
final class SmokeUserDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $rows = [
            ['id' => 1, 'email' => 'alice@example.test', 'enabled' => true],
            ['id' => 2, 'email' => 'bob@example.test', 'enabled' => false],
        ];

        foreach (range(3, 20) as $id) {
            $rows[] = [
                'id' => $id,
                'email' => sprintf('user%02d@example.test', $id),
                'enabled' => 0 === $id % 2,
            ];
        }

        $definition
            ->setOption(ArrayDataProvider::OPTION_ROWS, $rows)
            ->setOption('rowActionDisplayMode', 'dropdown')
            ->addColumn('id', visible: false, sortable: false, searchable: false, exportable: false)
            ->addColumn('email', label: 'Email')
            ->addColumn('enabled', label: 'Enabled')
            ->addFilter(
                name: 'enabled',
                field: 'enabled',
                label: 'Enabled',
                type: FilterType::Boolean,
            )
            ->addRowAction(
                name: 'view',
                route: 'app_smoke_user',
                label: 'View',
                routeParameters: ['id' => 'id'],
            )
            ->addBulkAction(
                name: 'archive',
                route: 'app_smoke_users_archive',
                label: 'Archive selected',
                confirmationMessage: 'Archive selected users?',
            )
        ;
    }
}
