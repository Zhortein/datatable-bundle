# Doctrine-backed datatables

This document explains how to declare Doctrine ORM backed datatables.

Doctrine ORM is the first production-oriented data provider supported by the bundle.

## Status

Doctrine support is currently in foundation stage.

Implemented features:

- entity-class based datatables;
- visible column selection on the main Doctrine alias;
- offset pagination;
- permanent filters;
- simple global search;
- single-column sorting;
- typed `DatatableResult` output;
- Bootstrap/Twig rendering through the existing renderer pipeline.

Not implemented yet:

- association traversal;
- custom joins;
- advanced filters;
- search builder;
- multi-column sorting;
- row actions rendering;
- global actions rendering;
- export support;
- Doctrine-specific performance tuning.

## Requirements

Doctrine-backed datatables require Doctrine ORM and DoctrineBundle in the host application.

Expected packages:

```bash
composer require doctrine/orm doctrine/doctrine-bundle
```

The bundle keeps Doctrine support isolated in dedicated provider classes so non-Doctrine providers can exist later.

## Basic declaration

A Doctrine datatable is declared with a PHP class.

```php
<?php

declare(strict_types=1);

namespace App\Datatable;

use App\Entity\User;
use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;

#[AsDatatable(name: 'users', provider: 'doctrine')]
final class UserDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setEntityClass(User::class)
            ->setTranslationDomain('user')
            ->addColumn('e.id', visible: false, sortable: false, searchable: true)
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.displayName', label: 'Display name')
            ->addColumn('e.createdAt', label: 'Created at', searchable: false, className: 'text-end')
        ;
    }
}
```

The `provider` attribute value is optional when Doctrine becomes the default provider, but declaring it explicitly is recommended for readability.

## Main alias

The current Doctrine provider uses the main alias:

```text
e
```

Example columns:

```php
$definition
    ->addColumn('e.email')
    ->addColumn('e.displayName')
;
```

For convenience, the provider also accepts field names without alias in some internal contexts, but public examples should use the explicit `e.` alias.

## Column options

`addColumn()` supports:

```php
$definition->addColumn(
    name: 'e.email',
    label: 'Email',
    visible: true,
    sortable: true,
    searchable: true,
    className: null,
    template: null,
    type: null,
);
```

### `visible`

Controls whether the column is rendered and selected.

### `sortable`

Controls whether the column may be used for server-side sorting.

Unknown or non-sortable fields are ignored safely by the Doctrine provider.

### `searchable`

Controls whether the column participates in global search.

Only declared searchable columns are used.

### `className`

Adds CSS classes to rendered cells.

Example:

```php
$definition->addColumn('e.createdAt', className: 'text-end');
```

### `template`

Reserved for custom cell templates.

Type-specific and custom cell templates are not fully implemented yet.

### `type`

Reserved for explicit cell type hints.

Automatic Doctrine type guessing exists internally and will be expanded later.

## Pagination

The Doctrine provider supports offset pagination through `DatatableRequest`.

The Ajax endpoint receives:

```text
page
pageSize
```

Example:

```text
/_zhortein/datatable/users/fragments?page=2&pageSize=25
```

The provider returns a `DatatableResult` containing:

- current page;
- page size;
- total items;
- filtered items;
- total pages;
- rows.

## Global search

The Doctrine provider supports simple global search through:

```text
search
```

Example:

```text
/_zhortein/datatable/users/fragments?search=alice
```

Current behavior:

- string-like fields use portable `LIKE`;
- search is case-insensitive through `LOWER(field) LIKE :query`;
- integer-like fields are searched only when the query is numeric;
- unsupported field types are ignored;
- non-searchable columns are ignored;
- if a search query is provided and no searchable expression can be built, no rows are returned.

Database-specific behavior such as PostgreSQL `ILIKE` is intentionally not used by default.

## Sorting

The Doctrine provider supports single-column sorting through:

```text
sortField
sortDirection
```

Example:

```text
/_zhortein/datatable/users/fragments?sortField=e.email&sortDirection=desc
```

Rules:

- only declared columns can be sorted;
- only columns marked `sortable: true` can be sorted;
- unknown fields are ignored safely;
- supported directions are `asc` and `desc`.

Multi-column sorting is not implemented yet.

## Permanent filters

Permanent filters are backend-defined filters declared in PHP.

They are never controlled by the frontend.

Example:

```php
use Zhortein\DatatableBundle\Enum\FilterOperator;

$definition->addPermanentFilter('e.enabled', FilterOperator::Equals, true);
```

Supported operators currently include:

- equals;
- not equals;
- greater than;
- greater than or equals;
- less than;
- less than or equals;
- in;
- not in;
- is null;
- is not null;
- between;
- like;
- not like.

Permanent filters apply to:

- loaded rows;
- total visible item count;
- filtered item count.

This means `totalItems` represents the total visible universe for the datatable context, not necessarily the full database table.

## Rendering

Doctrine-backed datatables use the same Twig-first rendering pipeline as other providers.

Expected Twig usage:

```twig
{{ zhortein_datatable('users', {
    search: true,
    pageSize: 25
}) }}
```

The rendered HTML shell includes Stimulus values for:

- datatable name;
- fragments URL;
- current page;
- page size.

The Stimulus controller refreshes server-rendered fragments through Ajax.

## Ajax endpoint

The current fragments endpoint returns JSON:

```json
{
  "body": "<tr>...</tr>",
  "pagination": "<div>...</div>",
  "summary": "Showing 1 to 25 of 83 entries",
  "page": 1,
  "pageSize": 25,
  "totalItems": 83,
  "filteredItems": 83,
  "totalPages": 4
}
```

The `body` and `pagination` values are server-rendered Twig fragments.

## Current limitations

### Main alias only

The current provider supports simple fields on the main alias `e`.

Associations and custom joins are not implemented yet.

### No custom joins

Custom joins will require explicit value objects and careful query design.

### No advanced filters

Only permanent filters are supported.

User-controlled advanced filters and search-builder behavior are not implemented yet.

### No multi-column sorting

Only one sort field is supported.

### No export support

Exports will be implemented later.

### No row/global action rendering

Action definitions exist but rendering is not implemented yet.

## Association test fixtures

The bundle test suite includes Doctrine fixtures for association-related provider tests:

- `DoctrineUser`;
- `DoctrineOrganization`.

`DoctrineUser` has a nullable ManyToOne association to `DoctrineOrganization`.

These fixtures are used to validate explicit Doctrine join support.

## Recommended first usage

For now, use Doctrine-backed datatables for simple back-office tables with:

- one entity;
- simple scalar fields;
- pagination;
- basic search;
- single-column sorting;
- backend-defined permanent filters.

More advanced cases should wait for association support, custom joins and action rendering.
