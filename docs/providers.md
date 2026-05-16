# Data Providers

Data providers are responsible for fetching data, applying filters, sorting, and handles pagination.

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

## Custom Providers

You can implement your own data provider by creating a class that implements `DataProviderInterface`. Custom providers should be registered as services and tagged with `zhortein_datatable.provider`.

See [Architecture: Providers](architecture.md) for internal implementation details.
