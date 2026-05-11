# Template context reference

This document describes the data available in the bundle Twig templates.

The current implementation uses arrays and domain objects as template context. This may evolve later toward dedicated context value objects, but the keys documented here should be considered the current public rendering contract.

## Global rendering context

The main datatable template receives:

```twig
{{ definition }}
{{ visibleColumns }}
{{ rowActions }}
{{ globalActions }}
{{ hasRowActions }}
{{ htmlId }}
{{ options }}
```

### `definition`

Type:

```php
Zhortein\DatatableBundle\Definition\DatatableDefinition
```

The datatable definition built by the datatable class.

Useful accessors:

```twig
{{ definition.name }}
{{ definition.columns }}
{{ definition.filters }}
{{ definition.rowActions }}
{{ definition.globalActions }}
```

### `visibleColumns`

Type:

```php
array<string, ColumnDefinition>
```

Only columns that should be rendered for the current view.

This already accounts for:

- definition-level visibility;
- runtime `visibleColumns`;
- runtime `hiddenColumns`.

### `rowActions`

Type:

```php
array<string, ActionDefinition>
```

Raw row action definitions.

Most templates should use normalized row actions from row context instead.

### `globalActions`

Type:

```php
list<array<string, mixed>>
```

Normalized global actions ready to render.

### `hasRowActions`

Type:

```php
bool
```

Whether the datatable definition declares row actions.

### `htmlId`

Type:

```php
string
```

Stable HTML id generated from the datatable name.

Example:

```text
zhortein-datatable-users
```

### `options`

Type:

```php
array<string, mixed>
```

Runtime render options merged from:

```text
runtime Twig options > datatable preferences > bundle defaults
```

Common keys:

```text
search
pageSize
fragmentsUrl
sortField
sortDirection
visibleColumns
hiddenColumns
columnVisibility
export
exportUrl
allowedPageSizes
pageSizeSelector
```

## Toolbar template context

Template:

```text
bootstrap/_toolbar.html.twig
```

Receives the global context from `datatable.html.twig`.

It derives local variables:

```twig
page_size
page_size_selector_enabled
allowed_page_sizes
column_visibility_enabled
runtime_visible_columns
runtime_hidden_columns
export_enabled
```

## Header template context

Template:

```text
bootstrap/_header.html.twig
```

Receives:

```twig
visibleColumns
hasRowActions
options
```

It derives:

```twig
current_sort_field
current_sort_direction
```

Per-column loop variables:

```twig
column
is_current_sort
```

## Body template context

Template:

```text
bootstrap/_body.html.twig
```

Receives:

```twig
rows
```

`rows` is a list of normalized row contexts.

## Row template context

Template:

```text
bootstrap/_row.html.twig
```

Receives:

```twig
row
```

Each row contains:

```text
cells
actions
```

## Cell template context

Template:

```text
bootstrap/_cell.html.twig
```

Receives:

```twig
column
value
template
class_name
```

### `column`

Type:

```php
ColumnDefinition
```

### `value`

The raw resolved value for the current cell.

### `template`

The resolved cell template path.

Example:

```text
@ZhorteinDatatable/bootstrap/cell/string.html.twig
```

### `class_name`

The final cell CSS class.

This can come from:

- explicit column `className`;
- default alignment by cell type;
- null when no class applies.

## Built-in cell template context

Templates:

```text
bootstrap/cell/*.html.twig
```

Receive:

```twig
column
value
```

Built-in templates should not depend on the full datatable context.

This keeps custom cell templates easier to write.

## Action cell context

Template:

```text
bootstrap/_actions.html.twig
```

Receives:

```twig
actions
```

`actions` is a list of normalized row actions.

## Single action context

Template:

```text
bootstrap/_action.html.twig
```

Receives:

```twig
action
```

Current action keys:

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

### `name`

Action identifier.

### `label`

Human-readable action label.

May be null.

### `icon`

Optional CSS class for an icon.

### `url`

Generated URL.

### `httpMethod`

Uppercase HTTP method.

Examples:

```text
GET
POST
DELETE
```

### `csrfToken`

String token for non-GET actions when a CSRF manager is available.

Null otherwise.

### `className`

CSS class for the link or button.

May be null.

### `attributes`

Additional HTML attributes.

Type:

```php
array<string, string>
```

## Filter context

Templates:

```text
bootstrap/_filters.html.twig
bootstrap/_filter.html.twig
```

`_filters.html.twig` receives:

```twig
definition
htmlId
```

`_filter.html.twig` receives:

```twig
filter
htmlId
```

`filter` is a `UserFilterDefinition`.

Useful accessors:

```twig
{{ filter.name }}
{{ filter.field }}
{{ filter.label }}
{{ filter.type.value }}
{{ filter.choices }}
{{ filter.placeholder }}
{{ filter.required }}
{{ filter.options }}
```

## Column visibility context

Template:

```text
bootstrap/_column_visibility.html.twig
```

Receives:

```twig
definition
htmlId
runtime_visible_columns
runtime_hidden_columns
```

## Export context

Template:

```text
bootstrap/_export.html.twig
```

Receives:

```twig
definition
options
```

It derives:

```twig
export_url
```

## Pagination context

Template:

```text
bootstrap/_pagination.html.twig
```

Receives:

```twig
htmlId
result
```

`result` may be undefined when rendering a placeholder.

When defined, `result` is a `DatatableResult`.

Useful accessors:

```twig
{{ result.page }}
{{ result.pageSize }}
{{ result.totalItems }}
{{ result.filteredItems }}
{{ result.totalPages }}
```

## Empty state context

Template:

```text
bootstrap/_empty.html.twig
```

Receives:

```twig
visibleColumns
hasRowActions
```

## Stability notes

The following are currently stable enough to use in application overrides:

- `definition`;
- `visibleColumns`;
- `htmlId`;
- `options`;
- `row.cells`;
- `row.actions`;
- `cell.column`;
- `cell.value`;
- `cell.template`;
- `cell.className`;
- `action.name`;
- `action.label`;
- `action.url`;
- `action.httpMethod`;
- `action.csrfToken`;
- `action.className`;
- `action.attributes`.

The following are more likely to evolve before 1.0:

- filter rendering context;
- column visibility context;
- export control context;
- action icon strategy;
- raw `rowActions` and `globalActions` availability in high-level templates.

## Recommendation

When overriding templates, prefer small targeted overrides:

1. custom column template;
2. cell template override;
3. action template override;
4. toolbar partial override;
5. full datatable template override only when necessary.
