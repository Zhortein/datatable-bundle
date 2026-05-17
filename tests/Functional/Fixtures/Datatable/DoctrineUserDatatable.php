<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Fixtures\Datatable;

use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineUser;

#[AsDatatable(name: 'doctrine-users')]
final class DoctrineUserDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setEntityClass(DoctrineUser::class)
            ->addColumn('e.id', label: 'ID')
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.enabled', label: 'Enabled')
            ->addColumn('e.displayName', label: 'Display Name')
            ->addJoin('organization', 'e.organization')
            ->addColumn('organization.name', label: 'Organization')
            ->addAdvancedFilterField('email', 'e.email')
            ->addAdvancedFilterField('enabled', 'e.enabled')
            ->addAdvancedFilterField('displayName', 'e.displayName')
            ->addAdvancedFilterField('organization.name', 'organization.name')
        ;
    }
}
