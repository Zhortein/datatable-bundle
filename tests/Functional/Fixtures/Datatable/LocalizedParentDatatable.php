<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Fixtures\Datatable;

use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\ChildContextValue;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;

#[AsDatatable(name: 'localized-parents', provider: 'array')]
final class LocalizedParentDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setOption(ArrayDataProvider::OPTION_ROWS, [
                ['id' => 42, 'name' => 'Parent row'],
            ])
            ->addColumn('id', label: 'Identifier')
            ->addColumn('name', label: 'Name')
            ->setChildDatatable('localized-children', [
                'parentId' => ChildContextValue::row('id'),
            ])
        ;
    }
}
