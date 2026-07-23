<?php

declare(strict_types=1);

namespace App\Datatable;

use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;

#[AsDatatable(name: 'smoke-users', provider: 'array')]
final class SmokeUserDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setOption(ArrayDataProvider::OPTION_ROWS, [
                ['email' => 'alice@example.test', 'enabled' => true],
                ['email' => 'bob@example.test', 'enabled' => false],
            ])
            ->addColumn('email', label: 'Email')
            ->addColumn('enabled', label: 'Enabled')
        ;
    }
}
