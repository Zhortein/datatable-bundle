# Array Provider

The `ArrayDataProvider` is a simple provider for in-memory datasets. It is ideal for demos, tests, small static datasets, or when you are starting a new project and don't have a database yet.

## Usage

Set the provider to `array` in the `#[AsDatatable]` attribute and provide the rows in the datatable definition.

```php
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;

#[AsDatatable(name: 'demo', provider: 'array')]
class DemoDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->addColumn('id', label: 'ID')
            ->addColumn('name', label: 'Name')
            ->setOption(ArrayDataProvider::OPTION_ROWS, [
                ['id' => 1, 'name' => 'Alice'],
                ['id' => 2, 'name' => 'Bob'],
            ]);
    }
}
```

## Features

-   **Pagination**: Supported.
-   **Sorting**: Supports single-column sorting on any array key.
-   **Search**: Simple scalar search across all provided columns.
-   **Filters**: Supports text and boolean filters matching the array keys.

## Limitations

-   **Memory**: All rows must be loaded into memory before rendering. Not suitable for large datasets.
-   **Type Guessing**: Does not support automatic type guessing like the Doctrine provider.
-   **Associations**: No support for complex associations or joins.

For a full example, see the [Array Datatable Example](examples/array-datatable.md).

## Related documentation

- [Data Providers](providers.md)
- [Doctrine Provider](doctrine-provider.md)
- [Array Datatable Example](examples/array-datatable.md)
