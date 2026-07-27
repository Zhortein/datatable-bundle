<?php

declare(strict_types=1);

namespace App\Datatable;

use App\Entity\SmokeOrderLine;
use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\ChildContextValue;
use Zhortein\DatatableBundle\Definition\ContextFilterValue;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterOperator;

#[AsDatatable(name: 'smoke-order-lines', provider: 'doctrine')]
final class SmokeOrderLineDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setEntityClass(SmokeOrderLine::class)
            ->setContext(new DatatableContext(
                ['orderId' => null],
                ['orderId'],
            ))
            ->addPermanentFilter(
                'e.orderId',
                FilterOperator::Equals,
                ContextFilterValue::from('orderId'),
            )
            ->addColumn('e.id', visible: false, sortable: false, searchable: false, exportable: false)
            ->addColumn('e.product', label: 'Product')
            ->addColumn('e.quantity', label: 'Quantity')
            ->setChildDatatable(
                'smoke-line-events',
                ['lineId' => ChildContextValue::row('e.id')],
                maxDepth: 2,
            )
        ;
    }
}
