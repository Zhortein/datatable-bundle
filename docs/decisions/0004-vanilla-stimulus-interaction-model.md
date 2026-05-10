# 0004 - Vanilla Stimulus interaction model

## Status

Proposed

## Context

The bundle must provide Ajax-powered datatables without depending on jQuery or DataTables.net.

The rendering strategy is Twig-first: the backend renders HTML fragments and the frontend updates these fragments through a Stimulus controller.

The Stimulus controller must remain lightweight and focused on interactions, not business rendering.

## Decision

The bundle will provide a vanilla Stimulus controller responsible for orchestrating datatable interactions.

The controller will:

- read configuration from HTML data attributes;
- submit Ajax requests with the current table state;
- update server-rendered HTML fragments;
- manage loading and error states;
- bind sorting, pagination, search and page size interactions.

The controller will not:

- build table rows manually from raw JSON data;
- render business cells in JavaScript;
- depend on jQuery;
- depend on DataTables.net;
- depend on Bootstrap JavaScript.

## Controller name

The initial controller name should be:

```text
zhortein--datatable-bundle--datatable
```

Usage example:

```html
<div
    data-controller="zhortein--datatable-bundle--datatable"
    data-zhortein--datatable-bundle--datatable-name-value="users"
    data-zhortein--datatable-bundle--datatable-data-url-value="/_zhortein/datatable/users/data"
>
    <!-- Datatable markup -->
</div>
```

## Required values

The controller should support these Stimulus values:

```js
static values = {
    name: String,
    dataUrl: String,
    page: Number,
    pageSize: Number,
    search: String,
    sort: String,
    direction: String,
    debounceDelay: Number,
};
```

Initial defaults:

- `page`: `1`
- `pageSize`: `25`
- `search`: empty string
- `sort`: empty string
- `direction`: `asc`
- `debounceDelay`: `300`

## Targets

The controller should support these targets:

```js
static targets = [
    'body',
    'pagination',
    'summary',
    'searchInput',
    'pageSize',
    'error',
    'loading',
];
```

Expected responsibilities:

- `body`: receives rendered table rows.
- `pagination`: receives rendered pagination HTML.
- `summary`: receives rendered count/summary text or HTML.
- `searchInput`: global search input.
- `pageSize`: page size selector.
- `error`: error container.
- `loading`: loading indicator.

## Ajax request method

The initial implementation should use `GET` requests.

Reasons:

- table state is naturally query-like;
- URLs are easier to debug;
- it supports browser/devtool inspection;
- it keeps requests simple.

A later version may support `POST` for complex filters or large payloads.

## Ajax request parameters

The controller should send the following query parameters:

```text
page
pageSize
search
sort
direction
```

Example:

```text
/_zhortein/datatable/users/data?page=2&pageSize=25&search=admin&sort=e.email&direction=asc
```

The backend must validate and normalize these values.

## Expected Ajax response

The response should contain server-rendered HTML fragments:

```json
{
  "body": "<tr>...</tr>",
  "pagination": "<nav>...</nav>",
  "summary": "Showing 26 to 50 of 183 entries",
  "page": 2,
  "pageSize": 25,
  "totalItems": 183,
  "totalPages": 8
}
```

The controller should update only the provided fragments.

Missing optional fragments should not break the controller.

## Loading state

Before sending a request, the controller should:

- mark the root element as busy;
- show loading target if present;
- hide the error target if present;
- prevent duplicate concurrent updates where possible.

Expected markup behavior:

```html
<div data-controller="zhortein--datatable-bundle--datatable" aria-busy="true">
    ...
</div>
```

The controller may also add a CSS class:

```text
is-loading
```

## Error state

On Ajax failure, the controller should:

- keep the current table content if possible;
- show a generic error message;
- expose a safe message only;
- log technical details to `console.error()` in development-friendly fashion.

The default user-facing message should be translatable server-side when rendered in Twig.

The frontend must not display raw exception traces.

## Search behavior

The global search input should trigger a refresh with debounce.

Expected behavior:

- user types in the search field;
- controller waits `debounceDelay`;
- page is reset to `1`;
- table refreshes.

Example markup:

```html
<input
    type="search"
    class="form-control"
    data-zhortein--datatable-bundle--datatable-target="searchInput"
    data-action="input->zhortein--datatable-bundle--datatable#search"
/>
```

## Sorting behavior

Sortable headers should trigger sorting through Stimulus action parameters.

Example markup:

```html
<button
    type="button"
    class="btn btn-link p-0 text-decoration-none"
    data-action="zhortein--datatable-bundle--datatable#sort"
    data-zhortein--datatable-bundle--datatable-sort-field-param="e.email"
>
    Email
</button>
```

Expected behavior:

- clicking a new field sorts ascending;
- clicking the current field toggles direction;
- page is reset to `1`;
- table refreshes.

Multi-column sorting is out of scope for the first implementation.

## Pagination behavior

Pagination links/buttons should use Stimulus action parameters.

Example markup:

```html
<button
    type="button"
    class="page-link"
    data-action="zhortein--datatable-bundle--datatable#goToPage"
    data-zhortein--datatable-bundle--datatable-page-param="2"
>
    2
</button>
```

Expected behavior:

- controller updates the current page;
- table refreshes;
- disabled buttons should not trigger requests.

## Page size behavior

The page size selector should trigger a refresh and reset the page to `1`.

Example markup:

```html
<select
    class="form-select"
    data-zhortein--datatable-bundle--datatable-target="pageSize"
    data-action="change->zhortein--datatable-bundle--datatable#changePageSize"
>
    <option value="10">10</option>
    <option value="25">25</option>
    <option value="50">50</option>
</select>
```

## Progressive enhancement

The generated HTML should remain understandable without JavaScript.

The initial implementation may require JavaScript for Ajax navigation, but the markup should not be opaque or fully client-rendered.

A later version may add non-JavaScript fallback links if needed.

## Browser history

The first implementation should not update browser history automatically.

Reasons:

- keep the controller simple;
- avoid unexpected back-button behavior;
- wait for real usage feedback.

A later version may support optional URL synchronization.

## Accessibility

The controller and templates should preserve accessibility:

- use real buttons for actions;
- use `aria-busy` during loading;
- preserve focus when possible;
- expose current page in pagination markup;
- avoid clickable non-interactive elements;
- keep table semantics valid.

## Events

The controller may dispatch custom events for extension points.

Initial event names:

```text
zhortein--datatable-bundle--datatable:before-load
zhortein--datatable-bundle--datatable:after-load
zhortein--datatable-bundle--datatable:error
```

These events should be documented when implemented.

## Consequences

This model keeps JavaScript small and focused.

It preserves Symfony/Twig as the rendering authority.

It avoids recreating DataTables.net in custom JavaScript.

The trade-off is that Ajax responses contain HTML fragments rather than only raw data.

For business datatables, this is acceptable and consistent with the Bootstrap-first, Symfony-first goal.

## Follow-up tasks

- Implement the Stimulus controller skeleton.
- Define exact data attributes in Twig templates.
- Implement Ajax endpoint returning HTML fragments.
- Add JavaScript unit tests if a test runner is introduced.
- Add functional tests for rendered attributes.
