# Installation

## Requirements

- PHP 8.4+
- Symfony 8+
- Composer 2+

## Install the bundle

The package is not released yet.

Expected future installation command:

```bash
composer require zhortein/datatable-bundle
```

During local development, the bundle can be installed as a path repository from a Symfony test application.

Example:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../datatable-bundle",
      "options": {
        "symlink": true
      }
    }
  ],
  "require": {
    "zhortein/datatable-bundle": "*"
  }
}
```

Then run:

```bash
composer update zhortein/datatable-bundle
```

## Enable the bundle

Symfony Flex should eventually enable the bundle automatically.

Manual registration direction:

```php
// config/bundles.php

return [
    Zhortein\DatatableBundle\ZhorteinDatatableBundle::class => ['all' => true],
];
```

## Assets

The bundle will provide a Stimulus controller later.

The final asset installation strategy is not implemented yet.

Expected direction:

- Symfony AssetMapper compatibility;
- Symfony UX Stimulus integration;
- vanilla JavaScript only;
- no jQuery;
- no DataTables.net.

## Doctrine

Doctrine ORM is optional at package level, but required for Doctrine-backed datatables.

Expected future command:

```bash
composer require doctrine/orm
```

The Doctrine provider is not implemented yet.

## Routes

Import the bundle routes in your Symfony application.

PHP routing config example:

```php
<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->import('@ZhorteinDatatableBundle/config/routes.php');
};
```

YAML routing config example:

```yaml
zhortein_datatable:
    resource: '@ZhorteinDatatableBundle/config/routes.php'
```

The default Ajax fragments route is:

```text
/_zhortein/datatable/{name}/fragments
```

## Stimulus and AssetMapper

The bundle provides a vanilla Stimulus controller.

Install Symfony UX Stimulus and AssetMapper in the host application if they are not already installed:

```bash
composer require symfony/asset-mapper symfony/stimulus-bundle
```

Until automatic controller registration is provided, create a wrapper controller in the host application:

```js
// assets/controllers/zhortein_datatable_controller.js

export { default } from '../../vendor/zhortein/datatable-bundle/assets/controllers/datatable_controller.js';
```

This registers the controller as:

```text
zhortein-datatable
```

More details are available in [`stimulus-assetmapper.md`](stimulus-assetmapper.md).
