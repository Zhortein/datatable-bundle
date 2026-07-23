# Installation

This guide installs `zhortein/datatable-bundle` in a Symfony 8 application using AssetMapper, Stimulus and Bootstrap 5.

Complete every numbered step before following the [Quick Start](quick-start.md).

## Requirements

- PHP 8.4 or newer;
- Symfony 8.0 or newer;
- Twig;
- Bootstrap 5;
- AssetMapper and StimulusBundle;
- Doctrine ORM only for Doctrine-backed datatables.

The bundle does not currently provide a Symfony Flex recipe, so its bundle registration, routes and Stimulus controller must be configured manually.

## 1. Install the PHP packages

Install the bundle:

```bash
composer require zhortein/datatable-bundle
```

Ensure AssetMapper and StimulusBundle are available:

```bash
composer require symfony/asset-mapper symfony/asset symfony/stimulus-bundle
```

The Symfony recipes for AssetMapper and StimulusBundle normally create:

- `assets/app.js`;
- `assets/bootstrap.js`;
- `assets/controllers.json`;
- `importmap.php`;
- the `{{ importmap('app') }}` call in `templates/base.html.twig`.

Do not replace existing application files blindly. Merge the snippets below with the files created by the Symfony recipes.

For Doctrine-backed datatables, also install Doctrine ORM and DoctrineBundle if the application does not already use them:

```bash
composer require doctrine/doctrine-bundle doctrine/orm
```

## 2. Register the bundle

Check `config/bundles.php` and add the bundle when it is not already present:

```php
<?php

return [
    // ...
    Zhortein\DatatableBundle\ZhorteinDatatableBundle::class => ['all' => true],
];
```

## 3. Import the bundle routes

Create `config/routes/zhortein_datatable.yaml`:

```yaml
zhortein_datatable:
    resource: '@ZhorteinDatatableBundle/config/routes.php'
```

The import exposes:

| Route | Purpose |
|---|---|
| `zhortein_datatable_fragments` | Ajax refresh of headers, rows, pagination and summary |
| `zhortein_datatable_export` | CSV and XLSX exports |

Verify the import:

```bash
php bin/console debug:router zhortein_datatable
```

Both routes should be listed. See the [route reference](routes.md) for their paths and methods.

## 4. Install Bootstrap and Bootstrap Icons

The bundle uses Bootstrap CSS, Bootstrap JavaScript and Bootstrap Icons, but does not ship them.

Add the packages to the application import map:

```bash
php bin/console importmap:require bootstrap
php bin/console importmap:require bootstrap-icons/font/bootstrap-icons.min.css
```

Import them in `assets/app.js`. Keep any application-specific imports already present:

```js
import './bootstrap.js';
import 'bootstrap';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.min.css';
import './styles/app.css';
```

Import application styles after Bootstrap when they are intended to override Bootstrap defaults.

Bootstrap JavaScript is required for dropdown controls. Bootstrap Icons provide the default icons used for sorting, filters, actions and exports.

## 5. Enable the Stimulus controller

Add the bundle controller to `assets/controllers.json`:

```json
{
  "controllers": {
    "@zhortein/datatable-bundle": {
      "datatable": {
        "enabled": true,
        "fetch": "lazy"
      }
    }
  },
  "entrypoints": []
}
```

If the file already contains other controllers or entrypoints, merge only the `@zhortein/datatable-bundle` entry.

The controller is lazy by default: it is downloaded only when a datatable is present on the page.

## 6. Check the application entrypoint

The base layout must render the AssetMapper entrypoint:

```twig
{# templates/base.html.twig #}
{% block javascripts %}
    {% block importmap %}{{ importmap('app') }}{% endblock %}
{% endblock %}
```

The application entrypoint must import `assets/bootstrap.js`, as shown in step 4. Without these two pieces, the table shell can render while Ajax loading, sorting, filtering and pagination remain inactive.

## 7. Optional bundle configuration

The defaults work without a configuration file. To override them, create `config/packages/zhortein_datatable.yaml`:

```yaml
zhortein_datatable:
    default_provider: doctrine
    default_theme: bootstrap
    default_page_size: 25
    max_page_size: 500
    search_enabled: false
```

See the [configuration reference](configuration.md) before changing other values.

## 8. Verify the frontend integration

Check that AssetMapper exposes the bundle assets:

```bash
php bin/console debug:asset-map '@zhortein/datatable-bundle'
```

For production deployments, compile assets with the Symfony command:

```bash
php bin/console asset-map:compile
```

The command is `asset-map:compile`, not `asset-mapper:compile`.

The installation is now complete. Continue with the [Quick Start](quick-start.md) to create and display a working in-memory datatable.

## Troubleshooting

### The datatable class is not registered

- Ensure the class has `#[AsDatatable]`.
- Ensure it implements `DatatableInterface`.
- Ensure the application service configuration loads its namespace with autoconfiguration enabled.
- Run:

```bash
php bin/console debug:container --tag=zhortein_datatable.datatable
```

### The table shell appears but stays empty

- Check the browser console for Stimulus errors.
- Verify `assets/controllers.json`.
- Verify that `assets/app.js` imports `./bootstrap.js`.
- Verify that the base layout renders `{{ importmap('app') }}`.
- Check the fragments request in the browser network panel.

### The browser reports an unknown Stimulus controller

The expected HTML controller identifier is:

```text
zhortein--datatable-bundle--datatable
```

Recheck step 5 and run:

```bash
php bin/console debug:asset-map '@zhortein/datatable-bundle'
```

### The table has no Bootstrap styling

Verify that `bootstrap/dist/css/bootstrap.min.css` is present in `importmap.php` and imported by `assets/app.js`.

### Icons are missing

Verify that `bootstrap-icons/font/bootstrap-icons.min.css` is present in `importmap.php` and imported by `assets/app.js`.

### Dropdowns do not open

Verify that `assets/app.js` imports `bootstrap`. The CSS alone is not enough for export, visibility and action dropdowns.

### Fragments or exports return 404

Run:

```bash
php bin/console debug:router zhortein_datatable
```

If the routes are absent, recheck `config/routes/zhortein_datatable.yaml`.

### CSV works but XLSX is unavailable

XLSX support is optional:

```bash
composer require openspout/openspout
```

Then enable it in the Twig options:

```twig
{{ zhortein_datatable('users', {
    exportFormats: ['csv', 'xlsx']
}) }}
```
