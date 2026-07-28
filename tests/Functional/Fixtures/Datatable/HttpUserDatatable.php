<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Fixtures\Datatable;

use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Provider\Http\HttpProviderCapabilities;
use Zhortein\DatatableBundle\Provider\Http\HttpProviderConfiguration;
use Zhortein\DatatableBundle\Provider\HttpDataProvider;

#[AsDatatable(name: 'http-users', provider: 'http')]
final class HttpUserDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->addColumn('id')
            ->addColumn('email')
            ->setOption(HttpDataProvider::OPTION_CONFIGURATION, new HttpProviderConfiguration(
                endpoint: 'https://api.example.test/users',
                capabilities: new HttpProviderCapabilities(search: true),
            ))
        ;
    }
}
