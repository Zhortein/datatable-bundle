# Provider Architecture

Data providers are responsible for loading rows from a source based on a datatable definition and a request.

## Data Provider Layer

Data loading is abstracted behind `DataProviderInterface`. A provider receives a `DatatableDefinition` and a `DatatableRequest`, and returns a `DatatableResult`.

```php
interface DataProviderInterface
{
    public function getData(DatatableDefinition $definition, DatatableRequest $request): DatatableResult;
}
```

## Provider Registry

`DataProviderRegistry` resolves providers:

- Explicitly by provider name;
- Automatically by asking providers whether they support a given definition.

Providers are regular Symfony services tagged with `zhortein_datatable.data_provider`.

## Array Provider

`ArrayDataProvider` is a simple provider intended for tests, demos, and early integration. It reads rows from datatable definition options and supports:

- Pagination;
- Simple scalar search;
- Single-column sorting.

It allows the data pipeline to be tested without a database.

## Doctrine Provider

The Doctrine provider is the primary production-oriented provider. Due to its complexity, it is documented in its own architecture page.

See [Doctrine Architecture](doctrine.md).
