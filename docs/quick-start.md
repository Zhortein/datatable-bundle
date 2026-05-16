# Quick Start

Get your first datatable running in minutes using the built-in `ArrayDataProvider`.

## 1. Define your Datatable

Create a PHP class that implements `DatatableInterface`. Use the `#[AsDatatable]` attribute to register it.

```php
// src/Datatable/DemoUserDatatable.php
namespace App\Datatable;

use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;

#[AsDatatable(name: 'demo-users', provider: 'array')]
final class DemoUserDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->addColumn('id', visible: false)
            ->addColumn('email', label: 'Email')
            ->addColumn('role', label: 'Role')
            ->setOption(ArrayDataProvider::OPTION_ROWS, [
                ['id' => 1, 'email' => 'admin@example.com', 'role' => 'ROLE_ADMIN'],
                ['id' => 2, 'email' => 'user@example.com', 'role' => 'ROLE_USER'],
            ])
        ;
    }
}
```

## 2. Render in Twig

In your Twig template, use the `zhortein_datatable()` function with the name you defined in the attribute.

```twig
{# templates/user/index.html.twig #}
{% extends 'base.html.twig' %}

{% block body %}
    <h1>User List</h1>
    {{ zhortein_datatable('demo-users', { search: true }) }}
{% endblock %}
```

## 3. Expected Behavior

When you visit the page:
1. The table structure is rendered immediately.
2. A Stimulus controller automatically triggers an Ajax request to fetch the data fragments.
3. The table body, pagination, and summary are populated.
4. If `search: true` is provided, a global search input is displayed.

## Next Steps

- **Doctrine Provider**: Connect your datatable to a database using the [Doctrine Provider](usage/doctrine-provider.md).
- **Filters**: Add user-facing [Filters](usage/filters.md) to your columns.
- **Actions**: Add row or global [Actions](usage/actions.md).
- **Exports**: Enable server-side [Exports](usage/exports.md) (CSV/XLSX).
- **Theming**: Customize the look and feel in [Theming & Customization](usage/theming.md).
