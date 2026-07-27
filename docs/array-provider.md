# Array Provider

The `ArrayDataProvider` is a lightweight provider for in-memory arrays. It is ideal for demos, tests, small static datasets, or early integration checks without a database.

## Usage

To use the array provider, specify `provider: 'array'` in the `#[AsDatatable]` attribute and provide the rows in the definition.

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
            ->setOption(ArrayDataProvider::OPTION_ROWS, [
                ['id' => 1, 'email' => 'alice@example.test', 'displayName' => 'Alice'],
                ['id' => 2, 'email' => 'bob@example.test', 'displayName' => 'Bob'],
            ])
            ->addColumn('id', visible: false)
            ->addColumn('email', label: 'Email')
            ->addColumn('displayName', label: 'Display name')
        ;
    }
}
```

## Supported Features

`ArrayDataProvider` supports:

- **Pagination**: Offset-based pagination.
- **Global Search**: Simple scalar search across searchable columns.
- **Sorting**: Ordered multi-column sorting on declared sortable columns.
- **Filters**: Compatible with user-facing filters.
- **Permanent filters**: Literal or context-backed filters are applied before result counts.
- **Cell source**: Each returned associative row is also available as the server-side cell `source`.
- **Computed values**: Named cell resolvers run after filtering, sorting and pagination.

## Options

- `ArrayDataProvider::OPTION_ROWS` (`rows`): An array of associative arrays representing the data.
- `ArrayDataProvider::OPTION_PROVIDER` (`provider`): Explicit provider identifier. The attribute is preferred for normal usage.

Context-backed permanent filters use the same typed value as the Doctrine
provider:

```php
use Zhortein\DatatableBundle\Definition\ContextFilterValue;
use Zhortein\DatatableBundle\Enum\FilterOperator;

$definition->addPermanentFilter(
    'orderId',
    FilterOperator::Equals,
    ContextFilterValue::from('orderId'),
);
```

The definition must declare and browser-allowlist `orderId` through
`DatatableContext`. See [hierarchical datatables](hierarchical-datatables.md)
for a complete parent/child example.

## Limitations

- **In-Memory**: All rows must be loaded into memory.
- **No Metadata**: Does not support Doctrine metadata type guessing.
- **Small Datasets**: Not suitable for large datasets or production entity-backed tables.
- **Computed sorting/filtering**: PHP-computed columns are display/export values and are not searchable or sortable.

For production use cases, see the **[Doctrine Provider](doctrine-provider.md)**.

For rich templates and computed values, see [Cell Context and Computed Values](cell-context.md).
For initial state, interaction and provider semantics, see [Multi-column Sorting](sorting.md).
