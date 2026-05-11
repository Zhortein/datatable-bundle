# Twig templates and override strategy

This document describes the Twig template structure used by `zhortein/datatable-bundle` and how host applications can override templates.

The bundle uses a Twig-first rendering strategy. Business cell rendering, actions, pagination and toolbar controls are rendered server-side.

## Current theme

The current supported theme is:

```text
bootstrap
```

The default bundle templates live under:

```text
templates/bootstrap/
```

Tailwind and custom themes are not implemented yet.

## Template override strategy

Host applications should use Symfony bundle template override mechanisms.

Expected override path:

```text
templates/bundles/ZhorteinDatatableBundle/bootstrap/...
```

Example:

```text
templates/bundles/ZhorteinDatatableBundle/bootstrap/_toolbar.html.twig
```

This overrides the bundle toolbar template.

## Template tree

Current template tree:

```text
templates/
└── bootstrap/
    ├── datatable.html.twig
    ├── _action.html.twig
    ├── _actions.html.twig
    ├── _body.html.twig
    ├── _cell.html.twig
    ├── _column_visibility.html.twig
    ├── _empty.html.twig
    ├── _export.html.twig
    ├── _filter.html.twig
    ├── _filters.html.twig
    ├── _header.html.twig
    ├── _pagination.html.twig
    ├── _row.html.twig
    ├── _toolbar.html.twig
    └── cell/
        ├── array.html.twig
        ├── boolean.html.twig
        ├── datetime.html.twig
        ├── default.html.twig
        ├── enum.html.twig
        ├── numeric.html.twig
        └── string.html.twig
```

## Public override templates

These templates are intended to be safe override points.

### `bootstrap/datatable.html.twig`

Renders the datatable shell.

Responsibilities:

- root container;
- Stimulus values;
- toolbar include;
- loading/error targets;
- table wrapper;
- header include;
- empty body state;
- summary target;
- pagination include.

Use this override when you need to change the global datatable layout.

### `bootstrap/_toolbar.html.twig`

Renders the top toolbar.

Responsibilities:

- search input;
- filters;
- column visibility controls;
- export controls;
- page size selector;
- global actions.

Use this override to customize toolbar layout.

### `bootstrap/_header.html.twig`

Renders table headers.

Responsibilities:

- visible column headers;
- sortable header controls;
- current sort state;
- row action column header.

Use this override to customize sorting controls or header markup.

### `bootstrap/_body.html.twig`

Renders all rows.

Usually, overriding `_row.html.twig` or `_cell.html.twig` is more precise.

### `bootstrap/_row.html.twig`

Renders a single row.

Responsibilities:

- cells;
- row actions.

Use this override for row-level markup.

### `bootstrap/_cell.html.twig`

Renders a single table cell wrapper.

Responsibilities:

- cell class;
- cell template include.

Use this override if you want to change `<td>` markup globally.

### `bootstrap/_actions.html.twig`

Renders the actions cell.

Use this override to change row action layout, such as button groups or dropdowns.

### `bootstrap/_action.html.twig`

Renders a single action.

Responsibilities:

- GET action links;
- non-GET action forms;
- CSRF hidden field;
- labels;
- icons;
- HTML attributes.

Use this override to customize action rendering globally.

### `bootstrap/_pagination.html.twig`

Renders pagination.

Use this override to customize Bootstrap pagination markup.

### `bootstrap/_empty.html.twig`

Renders the empty state.

Use this override to customize the empty state message or layout.

### `bootstrap/_filters.html.twig`

Renders the filters container.

Use this override to change filter group layout.

### `bootstrap/_filter.html.twig`

Renders a single filter control.

Use this override to customize form controls or add richer filter widgets.

### `bootstrap/_column_visibility.html.twig`

Renders the column visibility dropdown.

Use this override to customize the column visibility UI.

### `bootstrap/_export.html.twig`

Renders export controls.

Use this override to customize export buttons or dropdowns.

## Built-in cell templates

Cell templates live under:

```text
templates/bootstrap/cell/
```

Current built-in cell templates:

```text
array.html.twig
boolean.html.twig
datetime.html.twig
default.html.twig
enum.html.twig
numeric.html.twig
string.html.twig
```

They are selected by `ColumnDefinition::getType()`.

Unknown types fall back to `default`.

## Custom column templates

A column can define its own Twig template:

```php
$definition->addColumn(
    name: 'e.status',
    label: 'Status',
    template: 'admin/datatable/cell/status.html.twig',
    type: 'string',
);
```

Custom column templates take precedence over built-in type-specific templates.

## Cell template context

Custom cell templates receive:

```twig
{{ column }}
{{ value }}
```

### `column`

The `ColumnDefinition` object.

Useful properties/methods:

```twig
{{ column.name }}
{{ column.label }}
{{ column.type }}
{{ column.className }}
```

### `value`

The raw cell value resolved from the provider result row.

The value may be:

- string;
- number;
- boolean;
- DateTimeInterface;
- enum or object;
- array;
- null.

Twig auto-escaping remains enabled.

## Action template context

`_action.html.twig` receives:

```twig
{{ action }}
```

Current normalized action keys:

```text
name
label
icon
url
httpMethod
csrfToken
className
attributes
```

Example:

```twig
{{ action.label ?? action.name }}
{{ action.url }}
{{ action.httpMethod }}
```

For non-GET actions, `csrfToken` may be available when Symfony CSRF is configured.

## Row template context

`_row.html.twig` receives:

```twig
{{ row }}
```

Current normalized row keys:

```text
cells
actions
```

Each cell contains:

```text
column
value
template
className
```

Each action contains the normalized action context documented above.

## Header template context

`_header.html.twig` receives:

```twig
{{ visibleColumns }}
{{ hasRowActions }}
{{ options }}
```

`visibleColumns` is an array of `ColumnDefinition` objects.

`options` contains runtime render options, including current sorting state.

## Toolbar template context

`_toolbar.html.twig` receives the main render context:

```twig
{{ definition }}
{{ htmlId }}
{{ globalActions }}
{{ options }}
```

It also derives local variables for:

- page size;
- column visibility state;
- export controls;
- filters.

## Pagination template context

`_pagination.html.twig` may receive:

```twig
{{ result }}
{{ htmlId }}
```

When no `result` is present, it renders a placeholder container.

When a `DatatableResult` is present, it renders Bootstrap pagination controls.

## Datatable shell context

`datatable.html.twig` receives:

```twig
{{ definition }}
{{ visibleColumns }}
{{ rowActions }}
{{ globalActions }}
{{ hasRowActions }}
{{ htmlId }}
{{ options }}
```

This is the highest-level template context.

## Internal and unstable templates

All templates are currently considered overrideable, but some are more likely to evolve:

- `_filter.html.twig`;
- `_column_visibility.html.twig`;
- `_export.html.twig`;
- `_action.html.twig`.

These areas are still evolving because filtering, exports, actions and preferences are actively being expanded.

Prefer overriding narrowly where possible.

## Recommended override strategy

Prefer this order:

1. Use PHP definition options first.
2. Use a custom column template for one cell.
3. Override a small partial such as `_cell.html.twig` or `_action.html.twig`.
4. Override `datatable.html.twig` only if you need a different global layout.

## Examples

### Override one cell

```php
$definition->addColumn(
    name: 'e.status',
    label: 'Status',
    template: 'admin/datatable/cell/status.html.twig',
);
```

### Override the toolbar globally

Create:

```text
templates/bundles/ZhorteinDatatableBundle/bootstrap/_toolbar.html.twig
```

### Override the empty state globally

Create:

```text
templates/bundles/ZhorteinDatatableBundle/bootstrap/_empty.html.twig
```

## Current limitations

### Bootstrap theme only

Only the Bootstrap template tree is maintained.

### No theme registry yet

The theme is currently a string passed to `DatatableRenderer`.

A richer theme registry may be added later.

### Template context may still evolve

The bundle is not stable yet, so some template contexts may still change before 1.0.

### No template context DTOs yet

Templates currently receive arrays and domain objects.

A future milestone may introduce explicit context value objects if needed.

## Related documentation

- [`architecture.md`](architecture.md)
- [`actions-and-cells.md`](actions-and-cells.md)
- [`table-controls.md`](table-controls.md)
- [`preferences.md`](preferences.md)
