# URL state and browser history

The bundle keeps the shareable state of each datatable instance in the current
page URL. Opening a record and using the browser Back button therefore restores
the table before its fragments are loaded again.

## Included state

The version 1 state model contains:

- global search;
- simple filters;
- advanced filter expressions;
- sort field and direction;
- page and page size;
- visible and hidden columns.

Layout options, selected row identifiers, provider options and server-side
application context are not shareable state.

## URL format

Each rendered table receives a parameter name such as:

```text
_zd_state[2nP_UmF2wHhYBn1p]
```

Its value is a versioned JSON object. The opaque key is derived from:

- the datatable name;
- the explicit `instance` render option;
- the signed browser-safe context token, when one exists.

The key does not contain or reveal the context values. Several tables on one
page update only their own parameter.

Routes and locales remain naturally isolated by the page URL itself. When the
same datatable is rendered several times on one page, each occurrence must use
a unique instance:

```twig
{{ zhortein_datatable('orders', {
    instance: 'open-orders'
}) }}

{{ zhortein_datatable('orders', {
    instance: 'archived-orders'
}) }}
```

See [explicit context](context.md) for tenant and business-scope propagation.

## Precedence

When named saved views are enabled, state is resolved in this order:

```text
URL state > named default view > runtime Twig options > datatable preferences > bundle defaults
```

Twig options and `DatatablePreferenceProviderInterface` provide initial values.
A valid URL state then replaces the shareable fields when Stimulus connects.
This makes copied URLs deterministic without changing the existing preference
extension point.

## Browser and Turbo behavior

The Stimulus controller:

1. reads and validates its namespaced state when it connects;
2. restores controls before the initial fragment request;
3. writes state only after a successful refresh;
4. creates history entries for discrete interactions;
5. replaces the current entry for live input updates;
6. restores state on `popstate`;
7. commits the current state before Turbo caches the page.

When Turbo exposes its navigator history, the controller uses it so restoration
identifiers and indexes remain coherent. Other host application
`window.history.state` metadata is merged back instead of being discarded. The
standard browser History API is the fallback when Turbo is absent.

Returning to a URL without the table parameter restores the initial Twig and
preference defaults. Invalid JSON, unsupported versions and invalid typed
values are ignored safely.

See [named saved views](saved-views.md) for the opt-in default-view behavior.

## Fragments and exports

The page URL uses the canonical JSON model, while fragment and export endpoints
keep the existing 1.x query parameters:

```text
page=2
pageSize=50
search=alice
filters[status]=active
visibleColumns[]=email
```

The controller translates restored state into those parameters. Custom fragment
and export routes therefore remain compatible. Both `current` and `full`
exports include the restored search, filters, advanced expression, sort and
column visibility; only `current` includes pagination.

## PHP state API

`State\DatatableState` is the immutable canonical state object. It is separate
from `Request\DatatableRequest`, which remains the execution request passed to
providers.

`DatatableRequestFactory` can create either representation:

```php
use Symfony\Component\HttpFoundation\Request;
use Zhortein\DatatableBundle\Factory\DatatableRequestFactory;
use Zhortein\DatatableBundle\State\DatatableState;

/** @var DatatableRequestFactory $factory */
$state = $factory->createStateFromRequest(Request::createFromGlobals());

/** @var DatatableState $state */
$datatableRequest = $factory->createFromState($state);
```

`State\DatatableStateUrlSerializer` defines the public version 1 JSON format and
the per-instance parameter name. The state payload is limited to 32 KiB.

## Frontend events

The datatable root dispatches bubbling events:

| Event | Detail |
|---|---|
| `zhortein-datatable:state:change` | `state`, `source` (`push` or `replace`) and `url` |
| `zhortein-datatable:state:restore` | `state`, `source` (`connect` or `popstate`) and `url` |

These events are observation hooks. Applications should use the declarative
datatable controls instead of mutating the payload.

## Security and privacy

URL state is user-controlled input. Providers still apply declared columns,
filters, advanced-filter compatibility and authorization rules.

The bundle does not put these values in the URL:

- the current user identifier;
- session data;
- security tokens;
- server-only `DatatableContext` values;
- selected row identifiers.

It also does not use session storage, `localStorage` or an implicit user store.
Do not place secrets in searches or filter values if the resulting URL may be
shared or logged.
