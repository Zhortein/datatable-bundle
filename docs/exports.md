# Server-side exports

This document explains the current server-side export system.

The bundle provides server-side exports. It does not rely on DataTables.net, browser-generated files or client-side export plugins.

## Status

Currently implemented:

- export request/value objects;
- export format enum;
- export mode enum;
- export writer contract;
- export writer registry;
- CSV export writer;
- export controller endpoint;
- current-view export mode;
- full-dataset export mode;
- CSV export toolbar control.

Currently supported format:

```text
csv
```

Not implemented yet:

- XLSX export;
- PDF export;
- asynchronous exports;
- background jobs;
- export progress UI;
- export size limits;
- custom export writer documentation beyond the internal contract;
- streamed Doctrine iterators for very large datasets.

## Export route

The bundle exposes this route:

```text
zhortein_datatable_export
```

Path:

```text
/_zhortein/datatable/{name}/export/{format}
```

Example:

```text
/_zhortein/datatable/users/export/csv
```

Because `csv` is the default format, Symfony may also generate:

```text
/_zhortein/datatable/users/export
```

Both refer to the CSV export route when default route parameters are used.

## Export formats

The current supported format is CSV.

Internal enum:

```php
ExportFormat::Csv
```

Content type:

```text
text/csv; charset=UTF-8
```

File extension:

```text
csv
```

XLSX is planned later, but it is intentionally not part of the enum until an XLSX writer exists.

## Export modes

Exports support two modes:

```text
current
full
```

Internal enum:

```php
ExportMode::Current
ExportMode::Full
```

### Current view

Current mode keeps pagination.

Example:

```text
/_zhortein/datatable/users/export/csv?mode=current&page=2&pageSize=25
```

This exports the current page, using the same request state as the rendered datatable.

### Full dataset

Full mode disables pagination.

Example:

```text
/_zhortein/datatable/users/export/csv?mode=full
```

Full mode keeps:

- permanent filters;
- user-facing filters;
- global search;
- sorting;
- column visibility state.

It removes pagination before loading rows from the provider.

## Export request flow

The controller builds a `DatatableExportRequest`.

The flow is:

```text
HTTP Request
→ DatatableRequestFactory
→ DatatableExportRequest
→ DatatableDefinitionFactory
→ DataProviderRegistry
→ DataProviderInterface
→ DatatableResult
→ ExportWriterRegistry
→ ExportWriterInterface
→ Response
```

The controller remains thin and delegates:

- request parsing;
- definition building;
- provider resolution;
- data loading;
- export writing.

## CSV writer

`CsvExportWriter` writes CSV responses server-side.

It uses PHP built-ins for CSV escaping.

It exports visible datatable columns only.

Column labels are used as CSV headers.

Example definition:

```php
$definition
    ->addColumn('e.id', label: 'Id', visible: false)
    ->addColumn('e.email', label: 'Email')
    ->addColumn('e.displayName', label: 'Display name')
;
```

Generated header:

```csv
Email,"Display name"
```

The hidden `e.id` column is not exported.

## Joined fields

The CSV writer supports the same column key normalization as the renderer.

A column declared as:

```php
$definition->addColumn('organization.name', label: 'Organization');
```

can read rows containing:

```php
[
    'organization_name' => 'Acme Corp',
]
```

## Value normalization

Current CSV value normalization:

- `null` becomes an empty string;
- booleans become `1` or `0`;
- `DateTimeInterface` values are formatted with `DATE_ATOM`;
- scalar values are cast to string;
- `Stringable` values are cast to string;
- other values are JSON-encoded.

This behavior may become configurable later.

## Toolbar export control

The datatable toolbar can render a CSV export control.

It is enabled by default.

Disable it:

```twig
{{ zhortein_datatable('users', {
    export: false
}) }}
```

Use a custom export URL:

```twig
{{ zhortein_datatable('users', {
    exportUrl: path('custom_users_export')
}) }}
```

The default toolbar renders two links:

```text
CSV current view
CSV full dataset
```

They target:

```text
mode=current
mode=full
```

## Interaction with filters and search

Export requests use the same normalized `DatatableRequest` as Ajax fragments.

This means exports can include:

```text
search=alice
filters[enabled]=1
sortField=e.email
sortDirection=asc
visibleColumns[]=e.email
hiddenColumns[]=e.createdAt
```

Provider behavior decides how these values affect exported data.

## Current limitations

### CSV only

Only CSV is implemented.

XLSX support is planned later and should be implemented as a separate writer.

### No asynchronous exports

Full exports are synchronous.

Large exports may need future asynchronous processing.

### No export limits yet

There is no maximum row count for full exports yet.

Host applications should be careful before exposing full exports on very large datasets.

### No streaming Doctrine iterator yet

The provider currently returns `DatatableResult` rows.

Very large Doctrine exports may require provider-level iterable/streaming support later.

### No permissions layer

The export endpoint does not include a built-in authorization layer.

Host applications should protect routes or decorate/wrap controllers if needed.

### Basic filename strategy

The default filename is based on the datatable name.

Custom filenames can be provided through export request parameters, but a richer filename strategy is not implemented yet.

## Related documentation

- [`architecture.md`](architecture.md)
- [`end-to-end-flow.md`](end-to-end-flow.md)
- [`table-controls.md`](table-controls.md)
- [`doctrine-provider.md`](doctrine-provider.md)
