# Minimal array datatable example

This example shows the smallest useful datatable based on `ArrayDataProvider`.

It does not require Doctrine.

This provider is useful for:

- demos;
- tests;
- documentation examples;
- small static datasets;
- early integration checks.

It is not intended to replace Doctrine for production entity-backed tables.

## Datatable class

Create a datatable class in your Symfony application.

Example path:

```text
src/Datatable/UserArrayDatatable.php
```

Example:

```php
<?php

declare(strict_types=1);

namespace App\Datatable;

use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterType;
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;

#[AsDatatable(name: 'demo-users', provider: 'array')]
final class UserArrayDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->addColumn(
                name: 'id',
                label: 'Id',
                visible: false,
                sortable: false,
                searchable: false,
            )
            ->addColumn(
                name: 'email',
                label: 'Email',
            )
            ->addColumn(
                name: 'displayName',
                label: 'Display name',
            )
            ->addColumn(
                name: 'enabled',
                label: 'Enabled',
                type: 'boolean',
            )
            ->addFilter(
                name: 'email',
                field: 'email',
                label: 'Email',
                type: FilterType::Text,
                placeholder: 'Search an email',
            )
            ->addFilter(
                name: 'enabled',
                field: 'enabled',
                label: 'Enabled',
                type: FilterType::Boolean,
            )
            ->setOption(ArrayDataProvider::OPTION_PROVIDER, ArrayDataProvider::PROVIDER_NAME)
            ->setOption(ArrayDataProvider::OPTION_ROWS, [
                [
                    'id' => 1,
                    'email' => 'alice@example.test',
                    'displayName' => 'Alice',
                    'enabled' => true,
                ],
                [
                    'id' => 2,
                    'email' => 'bob@example.test',
                    'displayName' => 'Bob',
                    'enabled' => false,
                ],
                [
                    'id' => 3,
                    'email' => 'charlie@example.test',
                    'displayName' => 'Charlie',
                    'enabled' => true,
                ],
            ])
        ;
    }
}
```

## Render it in Twig

Use the `zhortein_datatable()` function:

```twig
{{ zhortein_datatable('demo-users', {
    search: true,
    pageSize: 10,
    pageSizeSelector: true,
    allowedPageSizes: [10, 25, 50],
    export: true
}) }}
```

## Expected behavior

The rendered table supports:

- Bootstrap table rendering;
- global search;
- user-facing filters;
- pagination;
- page size selector;
- column visibility controls;
- CSV export controls;
- loading and error states;
- Stimulus Ajax refresh.

## Array provider behavior

`ArrayDataProvider` supports:

- pagination;
- simple scalar search;
- single-column sorting;
- filter-compatible rows for demos/tests.

It reads rows from:

```php
ArrayDataProvider::OPTION_ROWS
```

and supports definitions with:

```php
ArrayDataProvider::OPTION_PROVIDER
```

## Column names

Array provider rows may use simple keys:

```php
[
    'email' => 'alice@example.test',
]
```

When using simple array data, prefer simple column names:

```php
$definition->addColumn('email', label: 'Email');
```

Doctrine-style names such as `e.email` are mainly useful for Doctrine-backed datatables.

## Actions

You can add actions as usual, provided the route exists in your host application.

```php
$definition->addRowAction(
    name: 'view',
    route: 'app_user_show',
    label: 'View',
    routeParameters: [
        'id' => 'id',
    ],
);
```

For a static demo, you may prefer to omit actions unless your application routes are already available.

## Filters

The example uses two filters:

```php
$definition->addFilter(
    name: 'email',
    field: 'email',
    label: 'Email',
    type: FilterType::Text,
);
```

```php
$definition->addFilter(
    name: 'enabled',
    field: 'enabled',
    label: 'Enabled',
    type: FilterType::Boolean,
);
```

The rendered controls send values as:

```text
filters[email]=alice
filters[enabled]=1
```

## Limitations

Array-backed datatables are useful for simple datasets, but they have limitations:

- all rows are already in memory;
- no database-level optimization;
- no Doctrine metadata type guessing;
- no association support;
- no server-side persistence;
- not suitable for large datasets.

For production entity-backed tables, use the Doctrine provider.

See [`../doctrine-provider.md`](../doctrine-provider.md).

## Smoke-test validation

This example has been validated in a fresh Symfony application using a Composer path repository.

Validated behavior:

- datatable service discovery with `#[AsDatatable]`;
- Twig rendering through `zhortein_datatable()`;
- automatic initial Ajax load;
- global search;
- user-facing filters with `ArrayDataProvider`;
- page size selector;
- sortable headers;
- column visibility controls;
- synchronized header/body refresh;
- CSV current export;
- CSV full export;
- configurable CSV delimiter.

The array provider remains intended for tests, demos and small in-memory datasets.
