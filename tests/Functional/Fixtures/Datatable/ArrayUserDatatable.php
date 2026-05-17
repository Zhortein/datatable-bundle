<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Fixtures\Datatable;

use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;

#[AsDatatable(name: 'array-users')]
final class ArrayUserDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setOption(ArrayDataProvider::OPTION_PROVIDER, ArrayDataProvider::PROVIDER_NAME)
            ->setOption(ArrayDataProvider::OPTION_ROWS, [
                ['email' => 'alice@example.test', 'enabled' => true],
                ['email' => 'bob@example.test', 'enabled' => false],
            ])
            ->addColumn('email', label: 'Email')
            ->addColumn('enabled', label: 'Enabled')
            ->addAdvancedFilterField('email', 'email')
            ->addAdvancedFilterField('enabled', 'enabled')
        ;
    }
}
