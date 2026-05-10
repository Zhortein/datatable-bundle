# Stimulus and AssetMapper integration

This document explains how to integrate the bundle frontend controller in a Symfony application using AssetMapper and Symfony UX Stimulus.

The bundle frontend is intentionally lightweight:

- vanilla JavaScript only;
- Stimulus as the integration layer;
- no jQuery;
- no DataTables.net;
- no Webpack Encore requirement;
- no NPM build step required for the default Symfony 8 AssetMapper setup.

## Requirements

The host application should use:

- Symfony 8+
- AssetMapper
- Symfony UX Stimulus

Expected packages in the host application:

```bash
composer require symfony/asset-mapper symfony/stimulus-bundle
```

The exact packages may already be installed in a Symfony WebApp skeleton.

## Controller location

The bundle provides the controller at:

```text
assets/controllers/datatable_controller.js
```

The controller identifier is expected to be:

```text
zhortein-datatable
```

Generated datatable markup uses:

```html
<div
    data-controller="zhortein-datatable"
    data-zhortein-datatable-name-value="users"
    data-zhortein-datatable-fragments-url-value="/_zhortein/datatable/users/fragments"
>
    <!-- Datatable markup -->
</div>
```

## Importing the controller in the host application

Until a Symfony Flex recipe or automatic controller registration strategy is added, the host application can import the controller manually.

Example:

```js
// assets/controllers/zhortein_datatable_controller.js

export { default } from '../../vendor/zhortein/datatable-bundle/assets/controllers/datatable_controller.js';
```

Depending on the project structure, the relative path may need to be adjusted.

## Registering the controller

In a standard Symfony UX Stimulus setup, controllers placed in `assets/controllers` are automatically discovered.

The local wrapper file:

```text
assets/controllers/zhortein_datatable_controller.js
```

should register the controller as:

```text
zhortein-datatable
```

This matches the `data-controller="zhortein-datatable"` attribute rendered by the bundle.

## AssetMapper importmap

If needed, update the importmap:

```bash
php bin/console importmap:require @hotwired/stimulus
```

Most Symfony UX projects already have Stimulus configured.

## Datatable shell values

The rendered datatable shell exposes Stimulus values:

```html
data-zhortein-datatable-name-value="users"
data-zhortein-datatable-fragments-url-value="/_zhortein/datatable/users/fragments"
data-zhortein-datatable-page-value="1"
data-zhortein-datatable-page-size-value="25"
```

These values are read by the controller.

## Datatable targets

The controller expects several optional targets:

```html
data-zhortein-datatable-target="body"
data-zhortein-datatable-target="pagination"
data-zhortein-datatable-target="summary"
data-zhortein-datatable-target="searchInput"
data-zhortein-datatable-target="loading"
data-zhortein-datatable-target="error"
data-zhortein-datatable-target="globalActions"
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
data-action="input->zhortein-datatable#search"
```

Search is debounced before refreshing fragments.

## Pagination behavior

Pagination buttons use:

```html
data-action="zhortein-datatable#goToPage"
data-zhortein-datatable-page-param="2"
```

The controller updates the page value and refreshes fragments.

## Sorting behavior

Sorting support is prepared in the controller but the current UI markup is still minimal.

Expected action direction:

```html
data-action="zhortein-datatable#sort"
data-zhortein-datatable-field-param="e.email"
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
data-controller="zhortein-datatable"
```

### Ajax endpoint returns 404

Ensure bundle routes are imported.

See [`routes.md`](routes.md).

### Search input does not refresh

Check that the rendered search input contains:

```html
data-action="input->zhortein-datatable#search"
```

Check that the root element contains a valid fragments URL value.
