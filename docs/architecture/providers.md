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
- Ordered multi-column sorting.

It allows the data pipeline to be tested without a database.

The final associative rows are also attached as server-side sources. This
gives cell resolvers one consistent source contract without additional
lookups.

## Doctrine Provider

The Doctrine provider is the primary production-oriented provider. Due to its complexity, it is documented in its own architecture page.

It keeps scalar projections and does not attach hydrated entities to
`DatatableResult`. Computed columns are excluded from the SQL/DQL selection;
their named resolver runs later from already selected fields. This prevents
implicit lazy association traversal and N+1 queries in Twig.

Custom providers may attach one source array/object per normalized row. The
provider owns batching and eager-loading responsibilities.

For exports, `StreamingDataProviderInterface` is an optional capability layered
on top of the unchanged provider contract. It yields one `ExportRow` at a time
and receives the controller-created `ExportStreamContext` containing the
bounded batch size, preflight row count and cancellation signal. Doctrine uses
scalar batch queries; Array stays on the compatible materialized fallback.

See [Doctrine Architecture](doctrine.md).

See [Cell Context and Computed Values](../cell-context.md).
