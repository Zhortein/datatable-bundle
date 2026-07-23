# Data Providers

Data providers are responsible for fetching data, applying filters, sorting, and handling pagination.

The `zhortein/datatable-bundle` supports multiple providers through a common `DataProviderInterface`.

## Available Providers

- **[Doctrine ORM Provider](doctrine-provider.md)**: The primary production-ready provider for entity-backed datatables.
- **[Array Provider](array-provider.md)**: A lightweight provider for in-memory arrays, ideal for demos, tests, and small static datasets.

## Provider Selection

You can specify the provider in the `#[AsDatatable]` attribute:

```php
#[AsDatatable(name: 'users', provider: 'doctrine')]
// OR
#[AsDatatable(name: 'demo', provider: 'array')]
```

If no provider is specified, the bundle uses the `default_provider` configured in `zhortein_datatable.yaml`.

The default is used when it supports the definition. Otherwise, the registry falls back to another compatible provider. The built-in providers detect compatibility as follows:

- Doctrine supports definitions with an entity class;
- Array supports definitions with the `rows` option or an explicit `provider: 'array'`.

Select a custom provider with the same attribute:

```php
#[AsDatatable(name: 'remote-users', provider: 'api')]
```

## Custom Providers

You can implement your own data provider by creating a class that implements `DataProviderInterface`. Custom providers should be registered with the `zhortein_datatable.data_provider` tag and a unique `name` attribute:

```php
// config/services.php
use App\Datatable\Provider\ApiDataProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(ApiDataProvider::class)
        ->tag('zhortein_datatable.data_provider', ['name' => 'api'])
    ;
};
```

See [Architecture: Providers](architecture/providers.md) for internal implementation details.
