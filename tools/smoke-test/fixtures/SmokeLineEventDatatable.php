<?php

declare(strict_types=1);

namespace App\Datatable;

use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\ContextFilterValue;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterOperator;
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;

#[AsDatatable(name: 'smoke-line-events', provider: 'array')]
final class SmokeLineEventDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setContext(new DatatableContext(
                ['lineId' => null],
                ['lineId'],
            ))
            ->setOption(ArrayDataProvider::OPTION_ROWS, [
                ['id' => 1001, 'lineId' => 1, 'event' => 'Added to order'],
                ['id' => 1002, 'lineId' => 1, 'event' => 'Quality checked'],
                ['id' => 1003, 'lineId' => 3, 'event' => 'Packed separately'],
            ])
            ->addPermanentFilter(
                'lineId',
                FilterOperator::Equals,
                ContextFilterValue::from('lineId'),
            )
            ->addColumn('id', visible: false, sortable: false, searchable: false, exportable: false)
            ->addColumn('event', label: 'Event')
        ;
    }
}
