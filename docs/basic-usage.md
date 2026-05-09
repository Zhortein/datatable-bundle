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

Use the `zhortein_datatable` Twig function:

```twig
{{ zhortein_datatable('users') }}
```

Runtime options can be passed as the second argument:

```twig
{{ zhortein_datatable('users', {
    search: true
}) }}
```

## Ajax behavior

The first rendering strategy will use server-rendered HTML fragments updated by a vanilla Stimulus controller.

The frontend controller will not duplicate cell rendering logic in JavaScript.

## First usable flow

A first end-to-end flow is available with the array data provider.

It is intended for tests, demos and early integration.

```php
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;

$definition
    ->addColumn('id', visible: false, sortable: false, searchable: false)
    ->addColumn('email', label: 'Email')
    ->setOption(ArrayDataProvider::OPTION_PROVIDER, ArrayDataProvider::PROVIDER_NAME)
    ->setOption(ArrayDataProvider::OPTION_ROWS, [
        [
            'id' => 1,
            'email' => 'alice@example.test',
        ],
    ])
;
```

Render it from Twig:

```twig
{{ zhortein_datatable('users', {
    search: true,
    pageSize: 25
}) }}
```

The complete flow is documented in [`end-to-end-flow.md`](end-to-end-flow.md).

## Doctrine-backed datatables

Doctrine ORM is the first production-oriented data provider.

Basic example:

```php
use App\Entity\User;
use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterOperator;

#[AsDatatable(name: 'users', provider: 'doctrine')]
final class UserDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setEntityClass(User::class)
            ->addColumn('e.id', visible: false, sortable: false, searchable: true)
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.displayName', label: 'Display name')
            ->addPermanentFilter('e.enabled', FilterOperator::Equals, true)
        ;
    }
}
```

Render it from Twig:

```twig
{{ zhortein_datatable('users', {
    search: true,
    pageSize: 25
}) }}
```

More details are available in [`doctrine-provider.md`](doctrine-provider.md).
