# Table controls and interactions

This document explains the current datatable controls and frontend interactions.

The bundle uses a Twig-first rendering model with a vanilla Stimulus controller.

The frontend controller does not render business cells manually. It updates server-rendered fragments returned by the backend.

## Overview

Current controls:

- global search input;
- page size selector;
- sortable headers;
- pagination controls;
- loading state;
- error state;
- summary updates.

Current frontend controller:

```text
assets/controllers/datatable_controller.js
```

Current controller identifier:

```text
zhortein--datatable-bundle--datatable
```

The rendered datatable shell contains:

```html
data-controller="zhortein--datatable-bundle--datatable"
```

## Datatable shell values

The datatable shell exposes Stimulus values:

```html
data-zhortein--datatable-bundle--datatable-name-value="users"
data-zhortein--datatable-bundle--datatable-fragments-url-value="/_zhortein/datatable/users/fragments"
data-zhortein--datatable-bundle--datatable-page-value="1"
data-zhortein--datatable-bundle--datatable-page-size-value="25"
data-zhortein--datatable-bundle--datatable-sort-field-value=""
data-zhortein--datatable-bundle--datatable-sort-direction-value="asc"
```

These values are used to build Ajax fragment requests.

## Ajax request parameters

The controller sends the following query parameters to the fragments endpoint:

```text
page
pageSize
search
sortField
sortDirection
```

Example:

```text
/_zhortein/datatable/users/fragments?page=1&pageSize=25&search=alice&sortField=e.email&sortDirection=asc
```

## Ajax response shape

The fragments endpoint returns JSON:

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

The controller updates:

- body target;
- pagination target;
- summary target;
- internal page/pageSize values.

## Search input

Search can be enabled globally through configuration or at runtime.

Runtime example:

```twig
{{ zhortein_datatable('users', {
    search: true
}) }}
```

The search input uses:

```html
data-zhortein--datatable-bundle--datatable-target="searchInput"
data-action="input->zhortein--datatable-bundle--datatable#search"
```

Search behavior:

1. user types in the search input;
2. the controller updates `searchValue`;
3. current page is reset to 1;
4. refresh is debounced;
5. fragments are fetched from the backend.

Current debounce delay:

```text
300ms
```

## Page size selector

The page size selector is rendered by default.

Runtime options:

```twig
{{ zhortein_datatable('users', {
    pageSize: 25,
    allowedPageSizes: [10, 25, 50, 100],
    pageSizeSelector: true
}) }}
```

Disable the selector:

```twig
{{ zhortein_datatable('users', {
    pageSizeSelector: false
}) }}
```

The selector uses:

```html
data-zhortein--datatable-bundle--datatable-target="pageSizeInput"
data-action="change->zhortein--datatable-bundle--datatable#changePageSize"
```

Page size behavior:

1. user changes the selector value;
2. the controller updates `pageSizeValue`;
3. current page is reset to 1;
4. fragments are refreshed.

## Sortable headers

Columns marked as sortable are rendered as header buttons.

Example generated direction:

```html
<button
    type="button"
    data-action="zhortein--datatable-bundle--datatable#sort"
    data-zhortein--datatable-bundle--datatable-field-param="e.email"
>
    Email
</button>
```

Sorting behavior:

1. user clicks a sortable header;
2. if the same field is clicked again, direction toggles between `asc` and `desc`;
3. if another field is clicked, direction resets to `asc`;
4. current page is reset to 1;
5. fragments are refreshed.

## Current sorting state

The current sorting state is rendered in the datatable shell:

```html
data-zhortein--datatable-bundle--datatable-sort-field-value="e.email"
data-zhortein--datatable-bundle--datatable-sort-direction-value="asc"
```

The active header renders:

```html
aria-sort="ascending"
```

or:

```html
aria-sort="descending"
```

It also exposes a visually hidden label:

```text
sorted ascending
```

or:

```text
sorted descending
```

## Pagination controls

Pagination is rendered server-side from `DatatableResult`.

Controls use Bootstrap pagination markup and Stimulus actions:

```html
data-action="zhortein--datatable-bundle--datatable#goToPage"
data-zhortein--datatable-bundle--datatable-page-param="2"
```

Pagination behavior:

1. user clicks a page button;
2. the controller updates `pageValue`;
3. fragments are refreshed.

Pagination controls expose accessible labels such as:

```text
Previous
Next
Go to page 2
```

## Sortable header indicators

Sortable headers include visual indicators:

```text
↕ unsorted
↑ ascending
↓ descending
```

These indicators do not require an icon library.

The active sorted column also exposes `aria-sort`.

The indicator is decorative and hidden from assistive technologies.

## Loading state

During Ajax refresh, the controller:

- toggles `aria-busy` on the root element;
- toggles the `is-loading` CSS class;
- displays the loading target when present.

The loading target uses:

```html
role="status"
aria-live="polite"
```

and a Bootstrap spinner.

## Error state

When refresh fails, the controller displays the error target if present.

The error target uses:

```html
role="alert"
aria-live="polite"
```

Errors are cleared:

- before a new refresh;
- after a successful refresh.

If no error target exists, the controller falls back to `console.error()`.

## Accessibility notes

Current accessibility baseline:

- search input has an explicit accessible label;
- page size selector has an explicit accessible label;
- sortable headers expose accessible labels;
- active sort state uses `aria-sort`;
- pagination controls expose accessible labels;
- loading and error states use live regions;
- the datatable root exposes `aria-busy`.

This is not a full WCAG audit, but it provides a stronger baseline for professional back-office usage.

## Current limitations

### No frontend test suite yet

The JavaScript controller is not covered by automated frontend tests yet.

### No persisted preferences

The bundle does not persist:

- page size;
- search query;
- current sort;
- visible columns.

### No multi-column sorting

Only single-column sorting is supported.

### No advanced filter UI

Advanced filters and search-builder style interactions are not implemented yet.

### No column visibility UI

Column visibility controls are not implemented yet.

### No route prefix customization

The default fragments URL uses the bundle route.

Applications can override the fragments URL at runtime, but route prefix configuration is not implemented yet.

## Control layout

The datatable controls can use a split layout.

```twig
{{ zhortein_datatable('users', {
    controlsLayout: 'split'
}) }}
```

Split layout moves these controls below the table:

- column visibility;
- page size selector;
- summary.

Search, filters, export and global actions remain in the top toolbar.

## Related documentation

- [`stimulus-assetmapper.md`](stimulus-assetmapper.md)
- [`routes.md`](routes.md)
- [`end-to-end-flow.md`](end-to-end-flow.md)
- [`architecture.md`](architecture.md)
