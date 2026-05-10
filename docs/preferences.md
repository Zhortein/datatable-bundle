# Column visibility and datatable preferences

This document explains runtime column visibility and the datatable preference extension point.

The bundle supports column visibility controls and a preference abstraction without imposing a specific user model or persistence layer.

## Status

Implemented:

- runtime column visibility state;
- column visibility toolbar controls;
- Stimulus refresh on visibility changes;
- HTTP request normalization for visible/hidden columns;
- `DatatablePreference` value object;
- `DatatablePreferenceProviderInterface`;
- default `NullDatatablePreferenceProvider`;
- applying preferences to initial rendering defaults.

Not implemented yet:

- built-in persistence;
- user entity integration;
- database preference storage;
- save preferences action;
- reset preferences action;
- column ordering;
- drag-and-drop;
- per-user security integration.

## Runtime column visibility

Columns can be controlled at render time with Twig options.

Render only selected columns:

```twig
{{ zhortein_datatable('users', {
    visibleColumns: ['e.email', 'e.displayName']
}) }}
```

Hide selected columns:

```twig
{{ zhortein_datatable('users', {
    hiddenColumns: ['e.createdAt']
}) }}
```

You can also combine both:

```twig
{{ zhortein_datatable('users', {
    visibleColumns: ['e.email', 'e.displayName', 'e.createdAt'],
    hiddenColumns: ['e.createdAt']
}) }}
```

Rules:

- definition-level hidden columns remain hidden;
- `visibleColumns` acts as a whitelist;
- `hiddenColumns` excludes matching columns;
- runtime column names must match declared column names.

## Definition-level visibility

A column can be hidden by default:

```php
$definition->addColumn(
    name: 'e.id',
    label: 'Id',
    visible: false,
    sortable: false,
    searchable: false,
);
```

Definition-hidden columns remain hidden even if the frontend sends them as visible.

## Column visibility controls

The toolbar can render a column visibility dropdown.

It is enabled by default.

Disable it at runtime:

```twig
{{ zhortein_datatable('users', {
    columnVisibility: false
}) }}
```

The rendered controls expose:

```html
data-zhortein-datatable-column-visibility-control="true"
data-zhortein-datatable-column-name="e.email"
```

Definition-hidden columns are marked with:

```html
data-zhortein-datatable-definition-hidden="true"
```

## Stimulus behavior

Changing a column visibility checkbox calls:

```text
zhortein-datatable#changeColumnVisibility
```

The controller:

1. resets the current page to 1;
2. serializes column visibility state;
3. refreshes datatable fragments.

Sent parameters:

```text
visibleColumns[]=e.email
hiddenColumns[]=e.createdAt
```

Definition-hidden columns are not serialized by the controller.

## Request normalization

`DatatableRequestFactory` reads column visibility state from query parameters or request payloads.

Examples:

```text
visibleColumns[]=e.email
visibleColumns[]=e.displayName
hiddenColumns[]=e.createdAt
```

The normalized state is available through:

```php
$request->getVisibleColumns();
$request->getHiddenColumns();
$request->hasColumnVisibilityState();
$request->getColumnVisibilityOptions();
```

## DatatablePreference

`DatatablePreference` represents optional rendering defaults for a datatable.

It can store:

- page size;
- sort field;
- sort direction;
- visible columns;
- hidden columns.

Example:

```php
use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Preference\DatatablePreference;

$preference = DatatablePreference::create(
    pageSize: 50,
    sortField: 'e.email',
    sortDirection: SortDirection::Desc,
    visibleColumns: ['e.email', 'e.displayName'],
    hiddenColumns: ['e.createdAt'],
);
```

Preferences can be converted to render options:

```php
$options = $preference->toRenderOptions();
```

## Preference provider

Applications can provide datatable preferences by implementing:

```php
use Zhortein\DatatableBundle\Preference\DatatablePreference;
use Zhortein\DatatableBundle\Preference\DatatablePreferenceProviderInterface;

final class UserDatatablePreferenceProvider implements DatatablePreferenceProviderInterface
{
    public function getPreference(string $datatableName): DatatablePreference
    {
        // Load preferences from the host application storage.
    }
}
```

The default implementation is:

```php
NullDatatablePreferenceProvider
```

It always returns an empty preference.

## Replacing the preference provider

Host applications can replace or decorate:

```php
Zhortein\DatatableBundle\Preference\DatatablePreferenceProviderInterface
```

Example direction with service decoration:

```yaml
services:
    App\Datatable\UserDatatablePreferenceProvider:
        decorates: Zhortein\DatatableBundle\Preference\DatatablePreferenceProviderInterface
```

The exact integration depends on the host application.

The bundle does not assume:

- a specific User entity;
- Symfony Security user storage;
- database tables;
- session storage;
- Redis/cache storage.

## Rendering precedence

When rendering a datatable, options are merged with this precedence:

```text
runtime Twig options > datatable preferences > bundle defaults
```

Example:

```php
$preference = DatatablePreference::create(
    pageSize: 50,
);
```

If Twig renders:

```twig
{{ zhortein_datatable('users', {
    pageSize: 10
}) }}
```

the effective page size is:

```text
10
```

because runtime options are explicit and take precedence.

## Current limitations

### No built-in persistence

The bundle does not store preferences itself.

Applications must provide their own preference provider if they want persistence.

### No save/reset UI

The column visibility UI affects the current datatable refresh but does not save preferences yet.

### No user identity integration

The bundle does not know about application users.

This is intentional to avoid coupling the bundle to a specific security model.

### No column ordering

Column order customization is not implemented.

### No drag-and-drop

Column reordering through drag-and-drop is not implemented.

### No preference synchronization

The bundle does not synchronize preferences across browser tabs or devices.

## Recommended integration strategy

For applications that need persisted preferences:

1. Create an application-level persistence model.
2. Implement `DatatablePreferenceProviderInterface`.
3. Return a `DatatablePreference` for the current user and datatable name.
4. Add application-specific endpoints later to save/reset preferences.

The bundle provides the read-side extension point first. The write-side preference API will be designed later.
