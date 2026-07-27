<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Fixtures\Datatable;

use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineUser;

#[AsDatatable(name: 'array-doctrine-users', provider: 'array')]
final class ArrayDoctrineUserDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setEntityClass(DoctrineUser::class)
            ->addColumn('e.enabled')
        ;
    }
}
