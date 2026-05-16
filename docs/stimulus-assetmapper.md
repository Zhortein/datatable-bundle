# Stimulus and AssetMapper integration

This document explains the technical details of the Stimulus controller integration.

For installation instructions, see [Installation](installation.md).

## Stimulus Controller Registration

Enable it in the host application `assets/controllers.json` (see [Installation](installation.md#4-enable-stimulus-controller) for details).

The generated Stimulus identifier is:

```text
zhortein--datatable-bundle--datatable
```

The rendered datatable shell uses:

```html
data-controller="zhortein--datatable-bundle--datatable"
```

Do not copy the controller manually into the host application.

## Bootstrap JavaScript

Some controls depend on Bootstrap JavaScript, especially dropdowns. See [Installation](installation.md#5-bootstrap-requirement) for details on how to load it.

The bundle does not load Bootstrap automatically.

## Smoke-test status

This integration has been validated in a fresh Symfony application using a Composer path repository.

Validated behavior:

- controller is loaded through Symfony UX / AssetMapper;
- controller connects successfully;
- initial data auto-load works;
- Ajax fragments refresh works;
- filters refresh data;
- column visibility refreshes header and body;
- CSV export links include current state.

## Requirements

The host application should use Symfony 8+, AssetMapper, and Symfony UX Stimulus. See [Installation](installation.md#requirements) for details.

## Controller location

The bundle provides the controller at:

```text
assets/controllers/datatable_controller.js
```

The controller identifier is expected to be:

```text
zhortein--datatable-bundle--datatable
```

Generated datatable markup uses:

```html
<div
    data-controller="zhortein--datatable-bundle--datatable"
    data-zhortein--datatable-bundle--datatable-name-value="users"
    data-zhortein--datatable-bundle--datatable-fragments-url-value="/_zhortein/datatable/users/fragments"
>
    <!-- Datatable markup -->
</div>
```

## AssetMapper importmap

If needed, update the importmap:

```bash
php bin/console importmap:require @hotwired/stimulus
```

Most Symfony UX projects already have Stimulus configured.

## Datatable shell values

The rendered datatable shell exposes Stimulus values:

```html
data-zhortein--datatable-bundle--datatable-name-value="users"
data-zhortein--datatable-bundle--datatable-fragments-url-value="/_zhortein/datatable/users/fragments"
data-zhortein--datatable-bundle--datatable-page-value="1"
data-zhortein--datatable-bundle--datatable-page-size-value="25"
```

These values are read by the controller.

## Datatable targets

The controller expects several optional targets:

```html
data-zhortein--datatable-bundle--datatable-target="body"
data-zhortein--datatable-bundle--datatable-target="pagination"
data-zhortein--datatable-bundle--datatable-target="summary"
data-zhortein--datatable-bundle--datatable-target="searchInput"
data-zhortein--datatable-bundle--datatable-target="loading"
data-zhortein--datatable-bundle--datatable-target="error"
data-zhortein--datatable-bundle--datatable-target="globalActions"
```

The bundle templates already render the required targets.

## Ajax refresh behavior

The controller sends `GET` requests to the fragments endpoint.

Current query parameters:

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

The response is expected to contain server-rendered fragments:

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

The controller updates the DOM with the returned fragments.

It does not render business cells manually.

## Search behavior

When a search input is rendered, it is wired to:

```html
data-action="input->zhortein--datatable-bundle--datatable#search"
```

Search is debounced before refreshing fragments.

## Pagination behavior

Pagination buttons use:

```html
data-action="zhortein--datatable-bundle--datatable#goToPage"
data-zhortein--datatable-bundle--datatable-page-param="2"
```

The controller updates the page value and refreshes fragments.

## Sorting behavior

Sorting support is prepared in the controller but the current UI markup is still minimal.

Expected action direction:

```html
data-action="zhortein--datatable-bundle--datatable#sort"
data-zhortein--datatable-bundle--datatable-field-param="e.email"
```

Sorting UI will be improved later.

## Loading and error states

The controller toggles:

```html
aria-busy="true"
```

on the datatable root element during refresh.

It also updates optional loading and error targets.

## Current limitations

### Manual controller import

The controller currently needs to be imported manually in the host application.

A Symfony Flex recipe or improved automatic registration can be added later.

### No frontend test suite yet

The JavaScript controller is not currently covered by a frontend test suite.

### No advanced sorting UI yet

The controller has sorting logic, but the Twig header markup does not yet expose a complete sorting UI.

### No column visibility UI yet

Column visibility controls are not implemented yet.

### No persisted user preferences yet

The bundle does not persist page size, sorting or search preferences yet.

## Troubleshooting

### Controller is not loaded

Check that the wrapper controller exists:

```text
assets/controllers/zhortein_datatable_controller.js
```

Check that Symfony UX Stimulus is installed and enabled.

Check the generated HTML contains:

```html
data-controller="zhortein--datatable-bundle--datatable"
```

### Ajax endpoint returns 404

Ensure bundle routes are imported.

See [`routes.md`](routes.md).

### Search input does not refresh

Check that the rendered search input contains:

```html
data-action="input->zhortein--datatable-bundle--datatable#search"
```

Check that the root element contains a valid fragments URL value.
