# Routes

This document explains the bundle route loading strategy.

## Current route set

The bundle currently exposes one generic Ajax fragments route:

```text
zhortein_datatable_fragments
```

Path:

```text
/_zhortein/datatable/{name}/fragments
```

Allowed methods:

```text
GET
POST
```

The route is used by the Stimulus controller to refresh server-rendered datatable fragments.

## Importing routes in a Symfony application

Host applications should import the bundle routes explicitly.

Example with PHP routing config:

```php
<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->import('@ZhorteinDatatableBundle/config/routes.php');
};
```

Example with YAML routing config:

```yaml
zhortein_datatable:
    resource: '@ZhorteinDatatableBundle/config/routes.php'
```

## Generated URL

The default generated URL looks like:

```text
/_zhortein/datatable/users/fragments
```

For a datatable named `users`.

## Route naming rules

Bundle-owned route names must be prefixed with:

```text
zhortein_datatable_
```

This avoids collisions with application routes.

## Application-specific routes

Applications may wrap or replace the default route later if they need:

- firewall-specific paths;
- customer portal paths;
- admin-only paths;
- custom access control;
- additional route parameters.

The bundle route must remain generic and application-agnostic.

## Current limitations

The route prefix is not configurable yet.

Multiple route contexts, such as internal and external portals, are not implemented yet.

Route-level security is left to the host application configuration.
