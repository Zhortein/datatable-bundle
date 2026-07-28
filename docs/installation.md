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
- `assets/stimulus_bootstrap.js`;
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
php bin/console debug:router zhortein_datatable_fragments
php bin/console debug:router zhortein_datatable_export
```

Both commands should display their route. See the [route reference](routes.md) for their paths and methods.

## 4. Install Bootstrap and, optionally, an icon provider

The Bootstrap theme uses Bootstrap CSS and JavaScript but does not ship them.
Bootstrap Icons remain the default icon mapping for backward compatibility,
but the icon font is optional: applications can override the mappings, use
textual fallbacks or select the Symfony UX Icons provider.

Add the packages to the application import map:

```bash
php bin/console importmap:require bootstrap
php bin/console importmap:require bootstrap-icons/font/bootstrap-icons.min.css
```

Import them in `assets/app.js`. Keep any application-specific imports already present:

```js
import './stimulus_bootstrap.js';
import 'bootstrap';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.min.css';
import './styles/app.css';
```

Import application styles after Bootstrap when they are intended to override Bootstrap defaults.

Bootstrap JavaScript is required for dropdown controls. Bootstrap Icons provide
the default CSS icons used for sorting, filters, actions and exports when the
default provider is retained.

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

The application entrypoint must import `assets/stimulus_bootstrap.js`, as shown in step 4. Without these two pieces, the table shell can render while Ajax loading, sorting, filtering and pagination remain inactive.

## 7. Optional bundle configuration

The defaults work without a configuration file. To override them, create `config/packages/zhortein_datatable.yaml`:

```yaml
zhortein_datatable:
    default_provider: doctrine
    default_theme: bootstrap
    default_page_size: 25
    max_page_size: 500
    search_enabled: false
    search_builder_enabled: false
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
- Verify that `assets/app.js` imports `./stimulus_bootstrap.js`.
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

With `icon_provider: css`, verify that the stylesheet matching the configured
classes is imported. With `icon_provider: ux_icons`, verify that
`symfony/ux-icons` is installed and that the configured values are valid UX
Icons names such as `bi:eye`. The bundle falls back to CSS-class markup when the
UX adapter is unavailable.

### Dropdowns do not open

Verify that `assets/app.js` imports `bootstrap`. The CSS alone is not enough for export, visibility and action dropdowns.

### Fragments or exports return 404

Run:

```bash
php bin/console debug:router zhortein_datatable_fragments
php bin/console debug:router zhortein_datatable_export
```

If the routes are absent, recheck `config/routes/zhortein_datatable.yaml`.

### Symfony reports an unrecognized `zhortein_datatable` option

- Put bundle options in `config/packages/zhortein_datatable.yaml`. The file in `config/routes/` must contain only the route import from step 3.
- Compare the installed package with the documentation version:

```bash
composer show zhortein/datatable-bundle
php bin/console debug:config zhortein_datatable
```

- Check `composer.lock` when the reported package version differs from the expected version. An obsolete configuration example can contain options that the locked bundle does not support.
- Clear and rebuild the application cache after changing package configuration:

```bash
php bin/console cache:clear
php bin/console cache:warmup
```

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
