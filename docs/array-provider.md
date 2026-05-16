# Array Provider

The `ArrayDataProvider` is a lightweight provider for in-memory arrays. It is ideal for demos, tests, small static datasets, or early integration checks without a database.

## Usage

To use the array provider, specify `provider: 'array'` in the `#[AsDatatable]` attribute.

### Datatable Class

```php
namespace App\Datatable;

use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;

#[AsDatatable(name: 'demo-users', provider: 'array')]
final class UserArrayDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->addColumn('id', visible: false)
            ->addColumn('email', label: 'Email')
            ->addColumn('displayName', label: 'Display name')
            ->setOption(ArrayDataProvider::OPTION_ROWS, [
                ['id' => 1, 'email' => 'alice@example.test', 'displayName' => 'Alice'],
                ['id' => 2, 'email' => 'bob@example.test', 'displayName' => 'Bob'],
            ])
        ;
    }
}
```

## Supported Features

`ArrayDataProvider` supports:

- **Pagination**: Offset-based pagination.
- **Global Search**: Simple scalar search across searchable columns.
- **Sorting**: Single-column sorting.
- **Filters**: Compatible with user-facing filters.

## Options

- `ArrayDataProvider::OPTION_ROWS` (`rows`): An array of associative arrays representing the data.
- `ArrayDataProvider::OPTION_PROVIDER` (`provider`): Internal identifier (defaults to `array`).

## Limitations

- **In-Memory**: All rows must be loaded into memory.
- **No Metadata**: Does not support Doctrine metadata type guessing.
- **Small Datasets**: Not suitable for large datasets or production entity-backed tables.

For production use cases, see the **[Doctrine Provider](doctrine-provider.md)**.
