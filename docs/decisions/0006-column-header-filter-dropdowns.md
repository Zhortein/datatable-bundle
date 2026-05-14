# 0006 - Column header filter dropdowns

## Status

Accepted

## Context

The bundle currently renders user-facing filters in the datatable toolbar.

This works, but it becomes visually heavy when a datatable has many filters.

A more compact UI is desired:

- keep the toolbar focused on global search and actions;
- expose per-column filters directly from column headers;
- use a small filter icon in the header;
- open a compact filter control when the icon is clicked.

The desired UI should stay:

- Bootstrap-first;
- server-rendered where possible;
- Stimulus-enhanced;
- accessible;
- dependency-light;
- independent from DataTables.net.

## Decision

Column header filters will use **Bootstrap dropdowns**, not Bootstrap popovers.

Each filterable column header may render a small filter button next to the label/sort control.

Clicking the filter button opens a Bootstrap dropdown containing the filter control for that column.

## Why dropdowns instead of popovers

Bootstrap dropdowns are a better first implementation because:

- they can contain interactive form controls more naturally;
- they are already used by column visibility and export controls;
- they require no additional positioning API beyond Bootstrap;
- they are easier to reason about in Twig templates;
- they can be controlled with plain HTML + Bootstrap JS;
- they do not require custom popover content lifecycle management.

Bootstrap popovers are useful for simple informational overlays, but interactive form controls inside popovers require more lifecycle work and can be harder to manage accessibly.

## Proposed UX

A filterable column header should render:

```text
Column label  [sort indicator]  [filter icon]
```

The filter icon opens a dropdown.

Example visual direction:

```text
Email  ↕  🔍
```

When the filter is active, the filter icon should have an active visual state.

Example:

```text
Email  ↑  🔎*
```

The final icon can be textual or CSS-class based. No mandatory icon library should be required.

## Proposed markup direction

Example direction:

```twig
<th scope="col">
    <div class="d-inline-flex align-items-center gap-1">
        <button
            type="button"
            class="btn btn-link p-0 text-decoration-none zhortein-datatable__sort-button"
            data-action="zhortein--datatable-bundle--datatable#sort"
            data-zhortein--datatable-bundle--datatable-field-param="e.email"
        >
            <span>Email</span>
            <span aria-hidden="true">↕</span>
        </button>

        <div class="dropdown">
            <button
                type="button"
                class="btn btn-sm btn-link p-0 zhortein-datatable__column-filter-toggle"
                data-bs-toggle="dropdown"
                aria-expanded="false"
                aria-label="Filter Email"
            >
                <span aria-hidden="true">⌕</span>
            </button>

            <div class="dropdown-menu p-2">
                {# column filter control #}
            </div>
        </div>
    </div>
</th>
```

## Filter declaration mapping

Column header filters should be based on existing `UserFilterDefinition` objects.

A filter can target a field:

```php
$definition->addFilter(
    name: 'email',
    field: 'e.email',
    label: 'Email',
    type: FilterType::Text,
);
```

A column can be matched to a filter when:

```text
filter.field === column.name
```

Example:

```text
column.name = e.email
filter.field = e.email
```

For joined fields:

```text
column.name = organization.name
filter.field = organization.name
```

## Multiple filters for one column

For the first implementation, only one filter per field should be rendered in the column header.

If multiple filters target the same field, the renderer should use the first declared filter and leave advanced cases to the toolbar or future filter UI.

## Toolbar filters vs header filters

The bundle should support two filter layouts:

```text
toolbar
header
```

Possible runtime option:

```twig
{{ zhortein_datatable('users', {
    filterLayout: 'header'
}) }}
```

Default for alpha should remain:

```text
toolbar
```

This avoids breaking current behavior.

Potential modes:

- `toolbar`: current behavior;
- `header`: render matching filters in headers;
- `none`: hide filter controls but keep backend parsing possible.

A future `both` mode can be considered but is not necessary initially.

## Stimulus behavior

The existing `changeFilter` action can be reused.

Filter controls inside header dropdowns should still render:

```html
data-zhortein--datatable-bundle--datatable-filter-control="true"
data-action="input->zhortein--datatable-bundle--datatable#changeFilter change->zhortein--datatable-bundle--datatable#changeFilter"
```

The controller already serializes filter controls by selector:

```js
[data-zhortein-datatable-filter-control="true"]
```

or, with the UX controller naming:

```js
[data-zhortein--datatable-bundle--datatable-filter-control="true"]
```

So header filter controls can reuse the existing serialization path.

## Active filter state

When a filter has a value, the column filter button should expose an active state.

Possible markup:

```html
data-zhortein--datatable-bundle--datatable-filter-active="true"
```

Possible class:

```text
is-filtered
```

Possible Bootstrap class:

```text
text-primary
```

The first implementation can render active state server-side from request/render options when possible.

Stimulus may update active state client-side after input changes.

## Clear filter behavior

A header filter dropdown should include a small clear action for that column.

Example:

```html
<button
    type="button"
    class="btn btn-sm btn-link"
    data-action="zhortein--datatable-bundle--datatable#clearColumnFilter"
    data-zhortein--datatable-bundle--datatable-filter-name-param="email"
>
    Clear
</button>
```

This requires a new Stimulus method:

```js
clearColumnFilter(event)
```

It should:

1. find controls for the filter name;
2. clear their value;
3. reset page to 1;
4. refresh fragments.

## Accessibility requirements

Column filter buttons must have accessible labels.

Example:

```text
Filter Email
```

Dropdown controls must have labels.

The filter icon must be decorative:

```html
aria-hidden="true"
```

The active filter state should not be conveyed by color only.

A visually hidden text can be rendered when active:

```html
<span class="visually-hidden">Filter active</span>
```

## Translation keys

Suggested translation keys:

```yaml
zhortein_datatable:
    filters:
        column_filter: 'Filter %column%'
        column_filter_active: 'Filter active'
        clear_column: 'Clear filter'
```

French:

```yaml
zhortein_datatable:
    filters:
        column_filter: 'Filtrer %column%'
        column_filter_active: 'Filtre actif'
        clear_column: 'Effacer le filtre'
```

## Template structure

Suggested new/updated templates:

```text
templates/bootstrap/_header.html.twig
templates/bootstrap/_column_filter.html.twig
templates/bootstrap/_filter.html.twig
```

`_column_filter.html.twig` should receive:

```twig
filter
column
htmlId
isActive
```

It can reuse `_filter.html.twig` internally if the context remains compatible.

## Request/response impact

The current Ajax fragments endpoint already returns:

- header;
- body;
- pagination;
- summary.

This is important because active filter state in headers must update after filter changes.

No new response field should be needed if the header fragment is rendered from current options/request state.

## Implementation plan

Recommended follow-up issues:

1. Add filter layout option.
2. Render column header filter dropdowns.
3. Add active filter state for header filters.
4. Add clear column filter Stimulus action.
5. Document header filter UI.

## Out of scope for first implementation

- SearchBuilder-style complex conditions.
- Multiple filters per column.
- Custom filter widgets.
- Select2 integration.
- Datepicker dependency.
- Popover-based implementation.
- Persisted filter UI state.
- Drag-and-drop or advanced column menus.

## Consequences

Positive:

- toolbar becomes less crowded;
- filters are closer to their columns;
- UI feels more modern;
- existing filter request model can be reused;
- Bootstrap dropdown dependency already exists.

Trade-offs:

- header markup becomes more complex;
- narrow columns may need responsive handling;
- header filters require careful accessibility handling;
- dropdown controls inside table headers need browser testing.

## Decision summary

Use Bootstrap dropdowns for column header filters.

Keep toolbar filters as the default for now.

Introduce header filters as an opt-in layout first.

Avoid Bootstrap popovers for the first implementation.
