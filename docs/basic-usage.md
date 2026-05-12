# Basic usage

This document introduces the main developer experience of `zhortein/datatable-bundle`.

The bundle is still under active development, but the current API already supports:

- PHP datatable declarations;
- `#[AsDatatable]` service discovery;
- Twig rendering through `zhortein_datatable()`;
- server-side Ajax fragments;
- array-backed demo datatables;
- Doctrine-backed datatables;
- row and global actions;
- typed and custom cell templates.

For the full architecture, see [`architecture.md`](architecture.md).

---

## 1. Declare a datatable

A datatable is declared as a PHP class in the host application.

The class must:

- use the `#[AsDatatable]` attribute;
- implement `DatatableInterface`;
- configure a `DatatableDefinition`.

Example:

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
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.displayName', label: 'Display name')
            ->addColumn('e.createdAt', label: 'Created at', searchable: false)
        ;
    }
}
```

The datatable name is the public identifier used by Twig, Ajax routes and the frontend controller.

---

## 2. Render a datatable in Twig

Use the `zhortein_datatable` Twig function:

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

Current supported rendering options include:

- `search`: displays the global search input;
- `pageSize`: defines the initial page size;
- `fragmentsUrl`: overrides the Ajax fragments URL.

Example with a custom fragments URL:

```twig
{{ zhortein_datatable('users', {
    search: true,
    pageSize: 50,
    fragmentsUrl: path('custom_users_fragments')
}) }}
```

---

## 3. How Ajax refresh works

The rendered datatable shell contains Stimulus values and targets.

The frontend controller calls the fragments endpoint and receives server-rendered HTML fragments.

Current request parameters:

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

Current response shape:

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

The Stimulus controller updates:

- table body;
- pagination;
- summary;
- loading state;
- error state.

The frontend does not render business cells manually.

The complete flow is documented in [`end-to-end-flow.md`](end-to-end-flow.md).

---

## 4. Use the array provider for demos and tests

The array provider is useful for tests, demos and early integration.

It should not be considered the main production provider.

Example:

```php
<?php

declare(strict_types=1);

namespace App\Datatable;

use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;

#[AsDatatable(name: 'demo-users', provider: 'array')]
final class DemoUserDatatable implements DatatableInterface
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

Render it from Twig:

```twig
{{ zhortein_datatable('demo-users', {
    search: true,
    pageSize: 25
}) }}
```

The array provider currently supports:

- pagination;
- simple scalar search;
- single-column sorting.

---

## 5. Use Doctrine-backed datatables

Doctrine ORM is the first production-oriented provider.

Example:

```php
<?php

declare(strict_types=1);

namespace App\Datatable;

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
            ->setTranslationDomain('user')
            ->addColumn('e.id', visible: false, sortable: false, searchable: true)
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.displayName', label: 'Display name')
            ->addColumn('e.createdAt', label: 'Created at', searchable: false, type: 'datetime')
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

The Doctrine provider currently supports:

- entity-class based datatables;
- main alias `e`;
- simple scalar columns;
- offset pagination;
- permanent filters;
- simple global search;
- single-column sorting;
- typed result output.

More details are available in [`doctrine-provider.md`](doctrine-provider.md).

---

## 6. Add permanent filters

Permanent filters are backend-defined filters.

They are never controlled by the frontend.

Example:

```php
use Zhortein\DatatableBundle\Enum\FilterOperator;

$definition->addPermanentFilter('e.deletedAt', FilterOperator::IsNull);
```

Another example:

```php
$definition->addPermanentFilter('e.enabled', FilterOperator::Equals, true);
```

Permanent filters apply to:

- loaded rows;
- total visible item count;
- filtered item count.

This means `totalItems` represents the visible universe for the datatable context, not necessarily the full database table.

---

## 7. Add row actions

Row actions are rendered for each row.

Example:

```php
$definition->addRowAction(
    name: 'view',
    route: 'app_user_show',
    label: 'View',
    routeParameters: [
        'id' => 'e.id',
    ],
    className: 'btn btn-sm btn-outline-primary',
);
```

Route parameters are resolved from row values.

Supported row key styles include:

```text
id
e_id
e.id
```

GET row actions are rendered as links.

Non-GET row actions are rendered as forms.

Example delete action:

```php
$definition->addRowAction(
    name: 'delete',
    route: 'app_user_delete',
    label: 'Delete',
    httpMethod: 'DELETE',
    routeParameters: [
        'id' => 'e.id',
    ],
    className: 'btn btn-sm btn-danger',
);
```

When a CSRF token manager is available, non-GET action forms include a CSRF token.

---

## 8. Add global actions

Global actions are rendered in the datatable toolbar.

They are useful for operations such as create, import or future batch actions.

Example:

```php
$definition->addGlobalAction(
    name: 'create',
    route: 'app_user_create',
    label: 'Create',
    className: 'btn btn-sm btn-primary',
);
```

GET global actions are rendered as links.

Non-GET global actions are rendered as forms with CSRF token support when available.

More details are available in [`actions-and-cells.md`](actions-and-cells.md).

---

## 9. Use typed cell templates

Column rendering can use built-in cell types.

Supported initial types:

```text
default
string
numeric
boolean
datetime
array
enum
```

Examples:

```php
$definition->addColumn('e.createdAt', label: 'Created at', type: 'datetime');
$definition->addColumn('e.enabled', label: 'Enabled', type: 'boolean');
$definition->addColumn('e.amount', label: 'Amount', type: 'numeric', className: 'text-end');
```

Doctrine-backed datatables can receive inferred cell types through Doctrine metadata.

Explicit column types are preserved.

---

## 10. Use custom column templates

A column can define its own Twig template:

```php
$definition->addColumn(
    name: 'e.status',
    label: 'Status',
    template: 'admin/datatable/cell/status.html.twig',
    type: 'string',
);
```

Custom templates take precedence over built-in type-specific templates.

A custom cell template receives:

```twig
{{ column.name }}
{{ column.label }}
{{ value }}
```

Example:

```twig
<span class="badge text-bg-info">
    {{ value }}
</span>
```

More details are available in [`actions-and-cells.md`](actions-and-cells.md).

---

## 11. Table controls

The bundle currently supports several table controls:

- search input;
- page size selector;
- sortable headers;
- pagination controls;
- loading state;
- error state;
- summary updates.

Example:

```twig
{{ zhortein_datatable('users', {
    search: true,
    pageSize: 25,
    allowedPageSizes: [10, 25, 50, 100],
    pageSizeSelector: true
}) }}
```

More details are available in [`table-controls.md`](table-controls.md).

---

## 12. User-facing filters

Filters can be declared in the datatable definition:

```php
use Zhortein\DatatableBundle\Enum\FilterType;

$definition
    ->addFilter(
        name: 'email',
        field: 'e.email',
        label: 'Email',
        type: FilterType::Text,
        placeholder: 'Search an email',
    )
    ->addFilter(
        name: 'enabled',
        field: 'e.enabled',
        label: 'Enabled',
        type: FilterType::Boolean,
    )
;
```

Filters are rendered in the toolbar and sent as request parameters:

```text
filters[email]=alice
filters[enabled]=1
```

More details are available in [`filters.md`](filters.md).

---

## 13. Runtime column visibility

Column visibility can be controlled at render time:

```twig
{{ zhortein_datatable('users', {
    visibleColumns: ['e.email', 'e.displayName']
}) }}
```

Columns can also be hidden at runtime:

```twig
{{ zhortein_datatable('users', {
    hiddenColumns: ['e.createdAt']
}) }}
```

Definition-level hidden columns remain hidden even if requested in `visibleColumns`.

---

## 14. Column visibility and preferences

Runtime column visibility can be controlled through Twig options:

```twig
{{ zhortein_datatable('users', {
    visibleColumns: ['e.email', 'e.displayName'],
    hiddenColumns: ['e.createdAt']
}) }}
```

The toolbar can render a column visibility dropdown by default.

Disable it if needed:

```twig
{{ zhortein_datatable('users', {
    columnVisibility: false
}) }}
```

Applications can provide user-specific defaults by implementing `DatatablePreferenceProviderInterface`.

More details are available in [`preferences.md`](preferences.md).

---

## 15. Server-side exports

CSV exports are available server-side.

The default toolbar renders two CSV export links:

- current view;
- full dataset.

Disable export controls:

```twig
{{ zhortein_datatable('users', {
    export: false
}) }}
```

Use a custom export URL:

```twig
{{ zhortein_datatable('users', {
    exportUrl: path('custom_users_export')
}) }}
```

More details are available in [`exports.md`](exports.md).

---

## 16. Template overrides

The bundle uses Twig-first rendering and Bootstrap templates.

Host applications can override bundle templates with Symfony override paths:

```text
templates/bundles/ZhorteinDatatableBundle/bootstrap/_toolbar.html.twig
```

For a single custom cell, prefer a column template:

```php
$definition->addColumn(
    name: 'e.status',
    template: 'admin/datatable/cell/status.html.twig',
);
```

More details are available in [`templates.md`](templates.md).

---

## 17. Bootstrap table variants

Table display variants can be configured at render time:

```twig
{{ zhortein_datatable('users', {
    tableStriped: true,
    tableHover: true,
    tableBordered: true,
    tableSmall: true
}) }}
```

Disable the responsive wrapper:

```twig
{{ zhortein_datatable('users', {
    tableResponsive: false
}) }}
```

---

## 18. Examples

For a complete minimal example without Doctrine, see [`examples/array-datatable.md`](examples/array-datatable.md).

For a complete Doctrine-backed example, see [`examples/doctrine-datatable.md`](examples/doctrine-datatable.md).

---

## 19. Additional CSS classes

You can append custom CSS classes to the generated datatable markup:

```twig
{{ zhortein_datatable('users', {
    rootClass: 'my-datatable',
    tableWrapperClass: 'my-table-wrapper',
    tableClass: 'my-table'
}) }}
```

Available options:

| Option | Target |
|---|---|
| `rootClass` | Root datatable container |
| `tableWrapperClass` | Table responsive wrapper |
| `tableClass` | `<table>` element |

Classes are appended to existing Bootstrap classes.

---

## 20. Current limitations

The bundle is still under active development.

Current limitations include:

- Doctrine provider supports only the main alias `e`;
- association traversal is not implemented yet;
- custom joins are not implemented yet;
- advanced filters are not implemented yet;
- multi-column sorting is not implemented yet;
- exports are not implemented yet;
- action visibility/security voters are not implemented yet;
- batch selected-row actions are not implemented yet;
- built-in labels are not fully translated yet;
- frontend test suite is not implemented yet.

See [`roadmap.md`](roadmap.md) for planned work.
