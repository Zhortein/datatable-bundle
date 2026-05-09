# First end-to-end datatable flow

This document describes the first usable server-side datatable flow implemented by the bundle.

The current flow is intentionally limited but already connects the main architectural pieces:

```text
Datatable class
→ DatatableDefinitionFactory
→ DatatableDefinition
→ DatatableRequestFactory
→ DataProviderRegistry
→ DataProviderInterface
→ DatatableResult
→ DatatableRenderer
→ Twig fragments
→ Ajax JSON response
→ Stimulus DOM update
```

## Current status

The current implementation supports:

- datatable discovery through `#[AsDatatable]`;
- datatable resolution through `DatatableRegistry`;
- definition building through `DatatableDefinitionFactory`;
- typed HTTP request parsing through `DatatableRequestFactory`;
- provider resolution through `DataProviderRegistry`;
- demo/test data loading through `ArrayDataProvider`;
- typed provider results through `DatatableResult`;
- Bootstrap-first Twig rendering;
- row and cell rendering;
- pagination rendering;
- Ajax fragments endpoint;
- vanilla Stimulus controller skeleton.

The current implementation does not support yet:

- Doctrine ORM data provider;
- advanced filters;
- row actions rendering;
- global actions rendering;
- CSRF-protected actions;
- exports;
- column visibility preferences;
- i18n message catalog integration.

## 1. Declare a datatable

A datatable is declared as a PHP class in the host application.

Example using the current array provider:

```php
<?php

declare(strict_types=1);

namespace App\Datatable;

use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;

#[AsDatatable(name: 'users')]
final class UserDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->addColumn('id', visible: false, sortable: false, searchable: false)
            ->addColumn('email', label: 'Email')
            ->addColumn('displayName', label: 'Display name')
            ->setOption(ArrayDataProvider::OPTION_PROVIDER, ArrayDataProvider::PROVIDER_NAME)
            ->setOption(ArrayDataProvider::OPTION_ROWS, [
                [
                    'id' => 1,
                    'email' => 'alice@example.test',
                    'displayName' => 'Alice',
                ],
                [
                    'id' => 2,
                    'email' => 'bob@example.test',
                    'displayName' => 'Bob',
                ],
            ])
        ;
    }
}
```

The array provider exists for tests, demos and early integration only.

The future production provider will be Doctrine ORM.

## 2. Resolve and build the definition

`DatatableDefinitionFactory` centralizes definition building.

It:

1. resolves the datatable class from `DatatableRegistry`;
2. creates a new `DatatableDefinition`;
3. calls `buildDatatable()`;
4. returns the completed definition.

This avoids duplicating definition-building logic in Twig extensions, controllers and future services.

## 3. Render the datatable shell

The public Twig API is:

```twig
{{ zhortein_datatable('users') }}
```

Runtime options can be passed as the second argument:

```twig
{{ zhortein_datatable('users', {
    search: true,
    pageSize: 25
}) }}
```

The Twig function delegates to:

- `DatatableDefinitionFactory`;
- `DatatableRenderer`.

The generated shell includes:

- Bootstrap table markup;
- Stimulus controller attributes;
- Ajax fragments URL;
- optional search input;
- loading target;
- error target;
- summary target;
- table body target;
- pagination target.

## 4. Frontend refresh flow

The Stimulus controller uses the fragments URL exposed by the datatable shell.

It sends requests with the following query parameters:

```text
page
pageSize
search
sortField
sortDirection
```

Example request:

```text
/_zhortein/datatable/users/fragments?page=1&pageSize=25&search=alice
```

The controller uses:

- `fetch()`;
- `URL`;
- `URLSearchParams`;
- native DOM APIs.

It does not use:

- jQuery;
- DataTables.net;
- client-side cell rendering.

## 5. Parse the HTTP request

The Ajax controller delegates HTTP parsing to `DatatableRequestFactory`.

The factory converts Symfony `Request` data into a typed `DatatableRequest`.

It normalizes:

- page;
- page size;
- search query;
- sort field;
- sort direction;
- runtime options.

Invalid values are handled safely through defaults.

## 6. Resolve the data provider

The controller uses `DataProviderRegistry` to resolve the provider.

The current array provider supports definitions with:

```php
$definition->setOption(ArrayDataProvider::OPTION_PROVIDER, ArrayDataProvider::PROVIDER_NAME);
```

or definitions containing array rows:

```php
$definition->setOption(ArrayDataProvider::OPTION_ROWS, [...]);
```

Later, Doctrine-backed datatables will resolve to a Doctrine ORM provider.

## 7. Load data

A provider receives:

```text
DatatableDefinition
DatatableRequest
```

and returns:

```text
DatatableResult
```

`DatatableResult` contains:

- rows;
- current page;
- page size;
- total items;
- filtered items;
- total pages.

## 8. Render body and pagination fragments

The Ajax controller delegates rendering to `DatatableRenderer`.

The renderer produces:

- `body`: rendered rows or empty state;
- `pagination`: Bootstrap pagination controls;
- `summary`: textual result summary.

Rows and cells are rendered through Twig templates.

Cell values are escaped by default.

## 9. Ajax JSON response

The fragments endpoint returns JSON shaped like this:

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

When the search filters data:

```json
{
  "summary": "Showing 1 to 5 of 5 entries, filtered from 83 total entries"
}
```

When there are no rows:

```json
{
  "summary": "Showing 0 entries",
  "filteredItems": 0,
  "totalPages": 0
}
```

## 10. Stimulus updates the DOM

The Stimulus controller updates:

- body target;
- pagination target;
- summary target;
- loading state;
- error state.

The controller does not interpret business values.

Rendering decisions stay in Twig.

## Current limitations

### Array provider only

The first provider is array-backed and intended for demos/tests.

Doctrine ORM support is not implemented yet.

### Simple search

Search is basic and applies to scalar values of searchable columns.

### Simple sorting

Sorting is single-column only.

### No actions yet

Row and global actions are declared by definition objects but not rendered yet.

### No i18n integration yet

User-facing labels such as `Search`, `Loading...`, `Previous`, `Next` and empty state messages are currently plain strings.

They will be moved to Symfony translations later.

### No frontend test suite yet

The JavaScript controller is not covered by automated frontend tests yet.

The current PHP test suite validates backend rendering and endpoint behavior.
