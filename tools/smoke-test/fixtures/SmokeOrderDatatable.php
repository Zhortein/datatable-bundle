<?php

declare(strict_types=1);

namespace App\Datatable;

use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\ChildContextValue;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;

#[AsDatatable(name: 'smoke-orders', provider: 'array')]
final class SmokeOrderDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setOption(ArrayDataProvider::OPTION_ROWS, [
                ['id' => 101, 'reference' => 'SO-101', 'customer' => 'Alice'],
                ['id' => 102, 'reference' => 'SO-102', 'customer' => 'Bob'],
            ])
            ->addColumn('id', visible: false, sortable: false, searchable: false, exportable: false)
            ->addColumn('reference', label: 'Order')
            ->addColumn('customer', label: 'Customer')
            ->setChildDatatable(
                'smoke-order-lines',
                ['orderId' => ChildContextValue::row('id')],
                maxDepth: 2,
            )
        ;
    }
}
