# Zhortein Datatable Bundle

A Symfony 8+ bundle for Bootstrap-first business datatables driven by PHP definitions.

## Status

**Stable 1.x**. The documented public API follows Semantic Versioning and is covered by the [compatibility policy](docs/public-api.md).

## Requirements

- **PHP**: 8.4+
- **Symfony**: 8.0+
- **Frontend**: Bootstrap 5 (CSS/JS), Symfony UX Stimulus, and AssetMapper.

## Features

- **PHP-first Definitions**: Declare your datatables as PHP classes with attributes.
- **Twig Rendering**: Render tables with a single Twig function: `{{ zhortein_datatable() }}`.
- **Ajax Fragments**: Seamless server-side updates using vanilla Stimulus.
- **Data Providers**: Native support for **Doctrine ORM** and **Array** providers.
- **Filtering**: Built-in global search, toolbar/header filters, and advanced **Search Builder**.
- **Multi-column Sorting**: Ordered, accessible sorting shared by providers, URL state, saved views, and exports.
- **Shareable State**: Per-instance URL state with browser history and Turbo restoration.
- **Named Views**: Optional saved views with replaceable ownership, authorization and storage contracts.
- **Guarded Exports**: Server-side CSV/XLSX row limits, preflight counting and replaceable authorization.
- **Actions**: Declarative row, global and bulk actions with CSRF-aware forms and opt-in Ajax execution.
- **Exports**: Server-side CSV and optional XLSX exports.
- **Customization**: Flexible UI/UX customization via Twig blocks and themes.
- **Type Safety**: Automatic Doctrine type detection and typed cell rendering.
- **Rich Enums**: Localized enum labels with optional badges, colors and icons, shared by filters and exports.
- **Rich Cells**: Complete server-side cell context and reusable computed values shared with exports.
- **Hierarchical Tables**: Accessible lazy child datatables with signed context, isolated state, and independent providers.

## Installation Summary

Install the PHP dependencies:

```bash
composer require zhortein/datatable-bundle
composer require symfony/asset-mapper symfony/asset symfony/stimulus-bundle
```

The bundle does not currently provide a Symfony Flex recipe. The host application must:

- register `ZhorteinDatatableBundle` in `config/bundles.php`;
- import `@ZhorteinDatatableBundle/config/routes.php`;
- enable `@zhortein/datatable-bundle/datatable` in `assets/controllers.json`;
- install and import Bootstrap 5 and Bootstrap Icons;
- render the AssetMapper `app` entrypoint in the base layout.

Add the frontend dependencies:

```bash
php bin/console importmap:require bootstrap
php bin/console importmap:require bootstrap-icons/font/bootstrap-icons.min.css
```

Follow the [Installation Guide](docs/installation.md) for the exact file contents, verification commands and troubleshooting. Then use the [Quick Start](docs/quick-start.md) to create a complete working page.

## Minimal Example

### 1. Define your Datatable

```php
namespace App\Datatable;

use App\Entity\User;
use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;

#[AsDatatable(name: 'users')]
final class UserDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setEntityClass(User::class)
            ->addColumn('e.id', visible: false)
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.createdAt', label: 'Created At', searchable: false, type: 'datetime')
        ;
    }
}
```

### 2. Render it in Twig

```twig
{{ zhortein_datatable('users', { search: true }) }}
```

## Documentation

- [Documentation Index](docs/index.md)
- [Installation](docs/installation.md)
- [Quick Start](docs/quick-start.md)
- [Providers Overview](docs/providers.md)
- [Doctrine Provider](docs/doctrine-provider.md)
- [Filters](docs/filters.md)
- [Advanced Filters](docs/advanced-filters.md)
- [Enum Presentation](docs/enum-presentation.md)
- [Multi-column Sorting](docs/sorting.md)
- [URL State & Browser History](docs/url-state.md)
- [Named Saved Views](docs/saved-views.md)
- [Actions & Security](docs/actions.md)
- [Bulk Actions & Selection](docs/bulk-actions.md)
- [Exports](docs/exports.md)
- [UI/UX & Controls](docs/ui-ux.md)
- [Icon System](docs/icons.md)
- [Theming & Templates](docs/theming.md)
- [Cell Context & Computed Values](docs/cell-context.md)
- [Hierarchical Datatables](docs/hierarchical-datatables.md)
- [Public API & Compatibility](docs/public-api.md)
- [Frontend Test Strategy](docs/frontend-tests.md)
- [Roadmap](docs/roadmap.md)

## License

MIT
