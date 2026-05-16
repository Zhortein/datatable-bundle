# Installation

This guide explains how to install and configure `zhortein/datatable-bundle` in your Symfony 8+ application.

## Requirements

- **PHP**: 8.4+
- **Symfony**: 8.0+
- **Frontend**: Bootstrap 5 (CSS/JS), Symfony UX Stimulus, and AssetMapper.
- **Optional**: Doctrine ORM (required only for Doctrine-backed datatables).

## 1. Install the Bundle

Install the bundle using Composer:

```bash
composer require zhortein/datatable-bundle
```

> **Note**: As this bundle is currently in alpha, there is no Symfony Flex recipe yet. You must perform some manual configuration steps.

## 2. Register the Bundle

If the bundle was not automatically registered, add it to your `config/bundles.php`:

```php
// config/bundles.php
return [
    // ...
    Zhortein\DatatableBundle\ZhorteinDatatableBundle::class => ['all' => true],
];
```

## 3. Import Routes

The bundle uses Ajax fragments for dynamic updates. You must import its routes:

```yaml
# config/routes/zhortein_datatable.yaml
zhortein_datatable:
    resource: '@ZhorteinDatatableBundle/config/routes.php'
```

## 4. Enable Stimulus Controller

The bundle provides a Stimulus controller for handling table interactions and Ajax refreshes. Enable it in your `assets/controllers.json`:

```json
{
  "controllers": {
    "@zhortein/datatable-bundle": {
      "datatable": {
        "enabled": true,
        "fetch": "eager"
      }
    }
  }
}
```

## 5. Bootstrap Requirement

The bundle is designed to work with **Bootstrap 5**. It does not include Bootstrap itself. You must ensure Bootstrap CSS and JS are loaded in your application.

### Using AssetMapper

If you are using AssetMapper, you can require Bootstrap via importmap:

```bash
php bin/console importmap:require bootstrap
```

Then, import it in your main entrypoint (e.g., `assets/app.js`):

```js
import 'bootstrap';
import 'bootstrap/dist/css/bootstrap.min.css';
```

## 6. Configuration (Optional)

A default configuration is applied automatically. If you need to override defaults, create a configuration file:

```yaml
# config/packages/zhortein_datatable.yaml
zhortein_datatable:
    default_provider: doctrine
    default_theme: bootstrap
    default_page_size: 25
    max_page_size: 500
    search_enabled: false
```

## Troubleshooting

### Datatable not registered
Ensure your datatable class is in a directory that is autowired and tagged as a service. By default, classes implementing `DatatableInterface` are automatically registered if autoconfiguration is enabled.

### Stimulus controller not loading
- Check if `symfony/stimulus-bundle` is installed.
- Verify `assets/controllers.json` contains the correct entry for `@zhortein/datatable-bundle`.
- Run `php bin/console asset-mapper:compile` or check your browser console for loading errors.

### Bootstrap dropdowns not opening
Ensure that `bootstrap.bundle.js` (which includes Popper.js) is properly loaded. Dropdown-based controls like column visibility or export menus require Bootstrap's JavaScript.

### Routes missing
Run `php bin/console debug:router` and look for routes starting with `_zhortein_datatable_`. If they are missing, verify your route import in `config/routes/`.

### Exports not working
- **CSV**: Works out of the box.
- **XLSX**: Requires `openspout/openspout`. Install it via `composer require openspout/openspout`.
- Ensure that the export routes are imported and that the `exportFormats` option is enabled in your Twig call.
