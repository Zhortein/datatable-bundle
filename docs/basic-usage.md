# Basic usage

This document describes the expected developer experience.

The API is still under development.

## Declare a datatable

A datatable is declared as a PHP class in the host application.

```php
<?php

declare(strict_types=1);

namespace App\Datatable;

use App\Entity\User;
use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;

#[AsDatatable(name: 'users')]
final class UserDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setEntityClass(User::class)
            ->setTranslationDomain('user')
            ->addColumn('e.id', visible: false, sortable: false, searchable: false)
            ->addColumn('e.email')
            ->addColumn('e.displayName')
            ->addColumn('e.createdAt', searchable: false)
        ;
    }
}
```

## Add a permanent filter

Permanent filters are backend-defined filters.

They are never controlled by the frontend.

```php
use Zhortein\DatatableBundle\Enum\FilterOperator;

$definition->addPermanentFilter('e.deletedAt', FilterOperator::IsNull);
```

## Add row actions

```php
$definition
    ->addRowAction('view', route: 'app_user_show', label: 'View', routeParameters: ['id' => 'id'])
    ->addRowAction('edit', route: 'app_user_edit', label: 'Edit', routeParameters: ['id' => 'id'])
;
```

## Render the datatable

Expected Twig direction:

```twig
{{ zhortein_datatable('users') }}
```

This Twig helper is not implemented yet.

## Ajax behavior

The first rendering strategy will use server-rendered HTML fragments updated by a vanilla Stimulus controller.

The frontend controller will not duplicate cell rendering logic in JavaScript.
