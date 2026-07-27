# Routes

This document describes the bundle routes imported from `@ZhorteinDatatableBundle/config/routes.php`.

## Route set

| Name | Path | Methods | Purpose |
|---|---|---|---|
| `zhortein_datatable_fragments` | `/_zhortein/datatable/{name}/fragments` | `GET`, `POST` | Refresh rendered datatable fragments |
| `zhortein_datatable_child` | `/_zhortein/datatable/{name}/child` | `GET` | Render one signed lazy child datatable shell |
| `zhortein_datatable_export` | `/_zhortein/datatable/{name}/export/{format}` | `GET`, `POST` | Generate CSV or XLSX exports |
| `zhortein_datatable_views_list` | `/_zhortein/datatable/{name}/views` | `GET` | List named views |
| `zhortein_datatable_views_create` | `/_zhortein/datatable/{name}/views` | `POST` | Create a named view |
| `zhortein_datatable_views_load` | `/_zhortein/datatable/{name}/views/{viewIdentifier}` | `GET` | Load a named view |
| `zhortein_datatable_views_mutate` | `/_zhortein/datatable/{name}/views/{viewIdentifier}` | `PATCH` | Rename, update or set a default view |
| `zhortein_datatable_views_delete` | `/_zhortein/datatable/{name}/views/{viewIdentifier}` | `DELETE` | Delete a named view |

The export `format` requirement accepts `csv` and `xlsx`. CSV is the default, so Symfony can generate a URL without the final `/csv` segment.

## Importing the routes

Create `config/routes/zhortein_datatable.yaml`:

```yaml
zhortein_datatable:
    resource: '@ZhorteinDatatableBundle/config/routes.php'
```

PHP routing configuration is also supported:

```php
<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->import('@ZhorteinDatatableBundle/config/routes.php');
};
```

Verify the import:

```bash
php bin/console debug:router zhortein_datatable_fragments
php bin/console debug:router zhortein_datatable_child
php bin/console debug:router zhortein_datatable_export
php bin/console debug:router zhortein_datatable_views_list
```

## Generated URLs

For a datatable named `users`, the default URLs are:

```text
/_zhortein/datatable/users/fragments
/_zhortein/datatable/order-lines/child
/_zhortein/datatable/users/export
/_zhortein/datatable/users/export/xlsx
/_zhortein/datatable/users/views
```

The Stimulus controller generates fragments and export requests from the URLs rendered into the datatable HTML.

When a definition enables explicit browser-safe context, the renderer adds a
signed context token and an instance key to these URLs. The bundle validates
them before calling the provider. The parameter names and token format are
implementation details; applications should use the documented
[`DatatableContext`](context.md) API instead of constructing them manually.

Child URLs are generated only from a parent row configured through
`setChildDatatable()`. They carry a signed context token, an opaque instance
and bounded recursion depth. Treat the URL and its parameters as implementation
details; use the [hierarchical datatables](hierarchical-datatables.md)
declaration API instead of creating or modifying them.

## Security

The generic routes do not add application-specific authorization rules. Protect them through the host application's firewall and `access_control` configuration.

If access depends on the datatable name, enforce that rule in an application security layer before exposing the endpoint.

Signed context prevents modification but not replay. Tenant and business-scope
authorization must still be checked by the host application.

Hierarchical requests additionally call the replaceable
`ChildDatatableAuthorizationCheckerInterface` when a child URL is issued and
when it is consumed. The default checker allows requests, so scoped
applications must install their own checker and permanent provider filters.

Named-view mutations add CSRF validation. Their ownership and authorization
remain delegated to the host through the documented contracts. All named-view
operations are denied by default. See [named saved views](saved-views.md).

## Current limitations

- The route prefix is not configurable.
- Multiple route contexts, such as separate public and administration prefixes, are not built in.
