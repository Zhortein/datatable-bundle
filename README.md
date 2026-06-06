# Zhortein Datatable Bundle

A Symfony 8+ bundle for Bootstrap-first business datatables driven by PHP definitions.

## Status

**Alpha Stage**. This bundle is under active development. The public API may change before the 1.0.0 release.

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
- **Actions**: Declarative row and global actions with CSRF-aware non-GET support.
- **Exports**: Server-side CSV and optional XLSX exports.
- **Customization**: Flexible UI/UX customization via Twig blocks and themes.
- **Type Safety**: Automatic Doctrine type detection and typed cell rendering.

## Installation Summary

1. **Install the package**:
   ```bash
   composer require zhortein/datatable-bundle
   ```

> Note that there is no automatic recipe for this bundle for now.

2. **Register the bundle** (if not done by Flex) in `config/bundles.php`.

3. **Import routes**:
   ```yaml
   # config/routes/zhortein_datatable.yaml
   zhortein_datatable:
       resource: '@ZhorteinDatatableBundle/config/routes.php'
   ```

4. **Expose the Stimulus controller**:
   Ensure `symfony/stimulus-bundle` is installed and the controller is enabled in `assets/controllers.json`.

5. **Bootstrap requirement**:
   Ensure Bootstrap 5 CSS and JS are loaded in your layout.

See [Installation Guide](docs/installation.md) for detailed instructions.

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
            ->addColumn('e.createdAt', label: 'Created At', searchable: false)
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
- [Actions & Security](docs/actions.md)
- [Bulk Actions & Selection](docs/bulk-actions.md)
- [Exports](docs/exports.md)
- [UI/UX & Controls](docs/ui-ux.md)
- [Icon System](docs/icons.md)
- [Theming & Templates](docs/theming.md)
- [Frontend Test Strategy](docs/frontend-tests.md)
- [Roadmap](docs/roadmap.md)

## License

MIT
