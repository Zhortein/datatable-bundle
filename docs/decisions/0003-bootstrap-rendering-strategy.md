# 0003 - Bootstrap rendering strategy

## Status

Proposed

## Context

The bundle must provide Bootstrap-first datatables for Symfony applications.

The rendering system must be:

- Symfony-friendly;
- Twig-based;
- easy to override;
- compatible with Stimulus Ajax refresh;
- independent from DataTables.net;
- independent from jQuery;
- suitable for business applications.

The legacy implementation used server-side data preparation but relied heavily on DataTables.net on the frontend.

The new bundle must not reproduce that coupling.

## Decision

The bundle will use a server-rendered Twig-first strategy.

The frontend Stimulus controller will not build table cells manually from raw JSON data.

Instead, the backend will render HTML fragments through Twig templates, and Stimulus will update the relevant parts of the datatable.

This keeps rendering logic in Symfony/Twig and avoids duplicating formatting rules in JavaScript.

## Rendering responsibilities

### Twig renderer

The Twig renderer is responsible for:

- rendering the datatable shell;
- rendering table headers;
- rendering rows;
- rendering cells;
- rendering row actions;
- rendering global actions;
- rendering pagination;
- rendering empty state;
- rendering loading state;
- rendering error state.

### Stimulus controller

The Stimulus controller is responsible for:

- submitting Ajax requests;
- reading current page, search and sort state;
- updating HTML fragments returned by the backend;
- displaying loading state;
- displaying error state;
- binding click/change/submit events.

It must use vanilla JavaScript only.

### Data provider

The data provider is responsible for:

- loading rows;
- applying pagination;
- applying search;
- applying sorting;
- applying permanent filters.

It must not render HTML.

## Initial Twig API

The first public Twig helper should be:

```twig
{{ zhortein_datatable('users') }}
```

With optional runtime options:

```twig
{{ zhortein_datatable('users', {
    pageSize: 25,
    search: true
}) }}
```

The exact PHP implementation may use a Twig function or a Twig component later.

The initial target is a Twig function because it is simple and stable.

## Initial HTML structure

The generated HTML should follow this general structure:

```html
<div
    class="zhortein-datatable"
    data-controller="zhortein-datatable"
    data-zhortein-datatable-name-value="users"
    data-zhortein-datatable-data-url-value="/_zhortein/datatable/users/data"
>
    <div class="zhortein-datatable__toolbar">
        <!-- Search, global actions, page size selector -->
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead>
                <!-- Headers rendered by Twig -->
            </thead>

            <tbody data-zhortein-datatable-target="body">
                <!-- Rows rendered by Twig -->
            </tbody>
        </table>
    </div>

    <div data-zhortein-datatable-target="pagination">
        <!-- Pagination rendered by Twig -->
    </div>
</div>
```

## Bootstrap conventions

The default table should use Bootstrap classes:

```html
<table class="table table-striped table-hover align-middle">
```

Default alignment rules:

- text columns: default alignment;
- numeric columns: `text-end`;
- boolean/status columns: `text-center`;
- actions column: `text-end`;
- selector column: `text-center`.

These defaults may be overridden per column.

## Template structure

Initial templates should be split like this:

```text
templates/
└── bootstrap/
    ├── datatable.html.twig
    ├── _toolbar.html.twig
    ├── _table.html.twig
    ├── _header.html.twig
    ├── _body.html.twig
    ├── _row.html.twig
    ├── _cell.html.twig
    ├── _actions.html.twig
    ├── _pagination.html.twig
    ├── _empty.html.twig
    ├── _loading.html.twig
    └── _error.html.twig
```

Cell templates by type should be introduced later:

```text
templates/
└── bootstrap/
    └── cell/
        ├── default.html.twig
        ├── string.html.twig
        ├── numeric.html.twig
        ├── boolean.html.twig
        ├── datetime.html.twig
        ├── array.html.twig
        ├── selector.html.twig
        └── actions.html.twig
```

## Override strategy

Host applications should be able to override templates through standard Symfony bundle template override mechanisms.

Expected override path:

```text
templates/bundles/ZhorteinDatatableBundle/bootstrap/...
```

The bundle should also support explicit template overrides at column level later:

```php
$definition->addColumn(
    name: 'e.status',
    template: 'admin/datatable/cell/status.html.twig',
);
```

## Ajax response strategy

The initial Ajax endpoint should return rendered HTML fragments instead of raw row data.

Expected response shape:

```json
{
  "body": "<tr>...</tr>",
  "pagination": "<nav>...</nav>",
  "summary": "Showing 1 to 25 of 183 entries",
  "page": 1,
  "pageSize": 25,
  "totalItems": 183,
  "totalPages": 8
}
```

This keeps formatting, translations, actions and cell templates in Twig.

A future JSON-only mode may be considered later, but it is not part of the initial rendering strategy.

## Sorting

Sortable headers should be rendered as buttons or links with Bootstrap-compatible markup.

Example direction:

```html
<button
    type="button"
    class="btn btn-link p-0 text-decoration-none"
    data-action="zhortein-datatable#sort"
    data-zhortein-datatable-sort-field-param="e.email"
>
    Email
</button>
```

The exact markup will be finalized during implementation.

## Search

The default toolbar may include a global search input.

Search should not submit a full page reload.

Stimulus should debounce input changes and refresh the table through Ajax.

## Pagination

Pagination should use Bootstrap pagination markup:

```html
<nav aria-label="Datatable pagination">
    <ul class="pagination">
        <!-- items -->
    </ul>
</nav>
```

Pagination must remain accessible:

- disabled states;
- current page indication;
- meaningful labels;
- keyboard-friendly controls.

## Empty state

When no rows are available, the body should render a single row spanning all visible columns.

Example:

```html
<tr>
    <td colspan="5" class="text-center text-body-secondary py-4">
        No data available.
    </td>
</tr>
```

The message must be translatable.

## Loading state

The initial implementation may use a simple Bootstrap spinner and an `aria-busy` attribute.

The table wrapper should expose loading state through a CSS class or data attribute.

## Error state

Ajax errors should display a Bootstrap alert.

Example:

```html
<div class="alert alert-danger" role="alert">
    Unable to load datatable data.
</div>
```

In debug mode, the backend may expose more details later, but the default response must remain safe.

## Consequences

This strategy favors Symfony/Twig consistency over client-side table rendering.

It reduces JavaScript complexity and keeps business rendering rules server-side.

It also makes custom cell templates, translations and actions easier to implement.

The trade-off is that Ajax responses may be slightly heavier because they contain HTML fragments instead of raw JSON rows.

For business-oriented back-office datatables, this trade-off is acceptable.

## Follow-up tasks

- Implement Twig rendering service.
- Implement Twig function `zhortein_datatable`.
- Add Bootstrap templates.
- Add functional rendering tests.
- Implement Ajax data endpoint returning HTML fragments.
- Implement Stimulus controller to update fragments.
