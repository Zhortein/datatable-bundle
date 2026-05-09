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
