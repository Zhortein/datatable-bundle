# Data Providers

Data providers are responsible for fetching data and applying filters, search, and sorting.

`zhortein/datatable-bundle` supports several provider types.

## Available Providers

-   [**Doctrine Provider**](doctrine-provider.md): The primary provider for entity-backed tables. It generates DQL queries and handles joins, aggregations, and performance optimizations.
-   [**Array Provider**](array-provider.md): A simple provider for in-memory datasets, demos, and tests.

## Using a Provider

The provider is specified in the `#[AsDatatable]` attribute:

```php
#[AsDatatable(name: 'users', provider: 'doctrine')]
class UserDatatable implements DatatableInterface { /* ... */ }
```

## Creating Custom Providers

You can implement your own provider by implementing `DataProviderInterface`.

```php
use Zhortein\DatatableBundle\Contract\DataProviderInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Result\DatatableResult;

class MyCustomProvider implements DataProviderInterface
{
    public function getData(DatatableDefinition $definition, DatatableRequest $request): DatatableResult
    {
        // ... fetch data and return a DatatableResult
    }
}
```

Register your custom provider in the Symfony container and it will be available for use.

## Related documentation

- [Doctrine Provider](doctrine-provider.md)
- [Array Provider](array-provider.md)
- [Architecture](architecture.md)
