<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Fixtures\Datatable;

use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;

#[AsDatatable(name: 'localized-children', provider: 'array')]
final class LocalizedChildDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setContext(new DatatableContext(
                ['parentId' => null],
                ['parentId'],
            ))
            ->setOption(ArrayDataProvider::OPTION_ROWS, [
                ['id' => 1, 'label' => 'Child row'],
            ])
            ->addColumn('id', label: 'Identifier')
            ->addColumn('label', label: 'Label')
        ;
    }
}
