# Installation

This document explains how to install and wire `zhortein/datatable-bundle` in a Symfony 8+ application.

## Requirements

- PHP 8.4+
- Symfony 8+
- Composer 2+
- Twig
- Symfony Translation
- Symfony Routing
- Symfony UX Stimulus / AssetMapper for frontend interactions

Doctrine ORM is optional at package level, but required for Doctrine-backed datatables.

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

## Basic configuration

The bundle exposes configuration under the `zhortein_datatable` root key.

Example:

```yaml
# config/packages/zhortein_datatable.yaml

zhortein_datatable:
    default_provider: doctrine
    default_theme: bootstrap
    default_page_size: 25
    max_page_size: 500
    search_enabled: false
```

See [`configuration.md`](configuration.md) for details.

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
# config/routes/zhortein_datatable.yaml

zhortein_datatable:
    resource: '@ZhorteinDatatableBundle/config/routes.php'
```

The default Ajax fragments route is:

```text
/_zhortein/datatable/{name}/fragments
```

More details are available in [`routes.md`](routes.md).

## Translations

The bundle provides built-in translations under the `zhortein_datatable` domain.

Provided locales:

- English;
- French.

The host application must have Symfony Translation enabled.

In a normal Symfony application, translation files from bundles are discovered automatically.

If the host application customizes translation paths, make sure bundle translations are still loaded.

More details are available in [`configuration.md`](configuration.md#translations).

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
zhortein--datatable-bundle--datatable
```

The rendered datatable shell uses:

```html
data-controller="zhortein--datatable-bundle--datatable"
```

More details are available in [`stimulus-assetmapper.md`](stimulus-assetmapper.md).

## Doctrine-backed datatables

Doctrine ORM is the first production-oriented provider.

Install Doctrine in the host application:

```bash
composer require doctrine/orm doctrine/doctrine-bundle
```

Declare a Doctrine-backed datatable:

```php
#[AsDatatable(name: 'users', provider: 'doctrine')]
final class UserDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setEntityClass(User::class)
            ->addColumn('e.id', visible: false)
            ->addColumn('e.email', label: 'Email')
        ;
    }
}
```

Render it:

```twig
{{ zhortein_datatable('users', {
    search: true,
    pageSize: 25
}) }}
```

More details are available in [`doctrine-provider.md`](doctrine-provider.md).

## Array provider for demos/tests

The array provider can be used for demos and tests without a database.

```php
$definition
    ->addColumn('id', visible: false, sortable: false, searchable: false)
    ->addColumn('email', label: 'Email')
    ->setOption(ArrayDataProvider::OPTION_PROVIDER, ArrayDataProvider::PROVIDER_NAME)
    ->setOption(ArrayDataProvider::OPTION_ROWS, [
        [
            'id' => 1,
            'email' => 'alice@example.test',
        ],
    ])
;
```

This provider is not intended to replace Doctrine in production applications.

## Rendering a datatable

Use the Twig function:

```twig
{{ zhortein_datatable('users') }}
```

With runtime options:

```twig
{{ zhortein_datatable('users', {
    search: true,
    pageSize: 25
}) }}
```

## Current manual integration steps

Until a Symfony Flex recipe exists, a host application should:

1. install the bundle;
2. register the bundle in `config/bundles.php`;
3. import bundle routes;
4. ensure translations are enabled;
5. expose the Stimulus controller through a wrapper;
6. declare one or more datatable services;
7. render datatables with `zhortein_datatable()`.

## Current limitations

The package is still in development.

Current limitations include:

- no Symfony Flex recipe yet;
- no automatic Stimulus controller registration yet;
- route prefix is not configurable yet;
- Doctrine provider supports only main alias `e`;
- association traversal is not implemented yet;
- custom joins are not implemented yet;
- exports are not implemented yet;
- advanced filters/search builder are not implemented yet;
- frontend tests are not implemented yet.

## Bootstrap requirements

The bundle renders Bootstrap-first markup but does not install or load Bootstrap automatically.

Host applications must load Bootstrap CSS.

If dropdown-based controls are enabled, such as column visibility or export controls, host applications must also load Bootstrap JavaScript, preferably `bootstrap.bundle`.

With AssetMapper/importmap:

```bash
php bin/console importmap:require bootstrap
php bin/console importmap:require bootstrap/dist/css/bootstrap.min.css
```

Then import Bootstrap in the application entrypoint:
```js
// assets/app.js
import 'bootstrap';
import 'bootstrap/dist/css/bootstrap.min.css';
```

The bundle intentionally does not bundle Bootstrap itself to avoid imposing a frontend dependency on host applications.

## Stimulus controller

The bundle exposes its Stimulus controller as a Symfony UX-compatible package.

Enable it in the host application `assets/controllers.json`:

```json
{
  "controllers": {
    "@zhortein/datatable-bundle": {
      "datatable": {
        "enabled": true,
        "fetch": "eager"
      }
    }
  },
  "entrypoints": []
}
```

The generated controller identifier is:

```text
zhortein--datatable-bundle--datatable
```

Do not copy the controller source manually into the host application.

## Optional XLSX export dependency

CSV export works without additional dependencies.

To enable XLSX export support, install OpenSpout in the host application:

```bash
composer require openspout/openspout
```

Then enable XLSX controls when rendering a datatable:

```twig
{{ zhortein_datatable('users', {
    exportFormats: ['csv', 'xlsx']
}) }}
```

See [`xlsx-export.md`](xlsx-export.md) for details and limitations.
