# Quick Start

This guide helps you create your first datatable with `zhortein/datatable-bundle`.

## 1. Create a Datatable Class

Datatables are defined as PHP classes implementing `DatatableInterface`. Use the `#[AsDatatable]` attribute to register them.

### Array-backed Example

```php
namespace App\Datatable;

use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Datatable\DatatableDefinition;
use Zhortein\DatatableBundle\Datatable\DatatableInterface;
use Zhortein\DatatableBundle\Provider\Array\ArrayDataProvider;

#[AsDatatable(name: 'quickstart_array')]
final class QuickstartArrayDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->addColumn('id', label: 'ID')
            ->addColumn('name', label: 'Name')
            ->setOption(ArrayDataProvider::OPTION_PROVIDER, ArrayDataProvider::PROVIDER_NAME)
            ->setOption(ArrayDataProvider::OPTION_ROWS, [
                ['id' => 1, 'name' => 'First Item'],
                ['id' => 2, 'name' => 'Second Item'],
            ])
        ;
    }
}
```

### Doctrine-backed Example

```php
namespace App\Datatable;

use App\Entity\User;
use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Datatable\DatatableDefinition;
use Zhortein\DatatableBundle\Datatable\DatatableInterface;

#[AsDatatable(name: 'users', provider: 'doctrine')]
final class UserDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setEntityClass(User::class)
            ->addColumn('e.id', label: 'ID')
            ->addColumn('e.email', label: 'Email', searchable: true, sortable: true)
            ->addColumn('e.createdAt', label: 'Created At', sortable: true)
        ;
    }
}
```

## 2. Render in Twig

You can render the datatable in any Twig template using the `zhortein_datatable()` function.

```twig
{# templates/user/index.html.twig #}

{% extends 'base.html.twig' %}

{% block body %}
    <h1>Users</h1>

    {{ zhortein_datatable('users') }}
{% endblock %}
```

## 3. Enable Features

Add features like search, pagination, and exports via options:

```twig
{{ zhortein_datatable('users', {
    search: true,
    pageSize: 10,
    exportFormats: ['csv', 'xlsx']
}) }}
```

## Next Steps

- [Providers](providers.md): Learn about Doctrine and Array providers.
- [Filters](filters.md): Add interactive filters.
- [Actions](actions.md): Add row and global actions.
- [UI/UX](ui-ux.md): Customize the look and feel.
- [Exports](exports.md): Configure data exports.
