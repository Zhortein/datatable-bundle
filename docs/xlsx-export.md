# XLSX export

This document describes XLSX export support in `zhortein/datatable-bundle`.

XLSX export is part of the server-side export pipeline, but it is optional.

CSV remains the default dependency-free export format.

## Strategy

The XLSX export strategy is documented in:

```text
docs/decisions/0007-xlsx-export-strategy.md
```

Summary:

- XLSX export is accepted as an optional core writer.
- The implementation uses OpenSpout.
- CSV remains available without OpenSpout.
- XLSX controls should only be shown when XLSX support is available/enabled.
- XLSX full export remains synchronous for now.
- Large/async exports remain future work.

## Installation

Install the optional OpenSpout dependency in the host application:

```bash
composer require openspout/openspout
```

The bundle can then register the XLSX writer when the dependency is available.

If OpenSpout is not installed, CSV export continues to work.

## Export format

The XLSX format is represented by:

```php
ExportFormat::Xlsx
```

Metadata:

```text
extension: xlsx
content type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
```

The export route accepts:

```text
/_zhortein/datatable/{name}/export/xlsx
```

Example:

```text
/_zhortein/datatable/users/export/xlsx
```

## Export modes

XLSX uses the same export modes as CSV.

### Current mode

Current mode keeps pagination.

Example:

```text
/_zhortein/datatable/users/export/xlsx?mode=current&page=1&pageSize=25
```

Current mode includes:

- current page;
- page size;
- search;
- filters;
- sorting;
- column visibility.

### Full mode

Full mode removes pagination but keeps the current datatable state.

Example:

```text
/_zhortein/datatable/users/export/xlsx?mode=full
```

Full mode keeps:

- search;
- filters;
- sorting;
- column visibility.

This means:

```text
full export = full filtered dataset without pagination
```

It does not mean:

```text
raw unfiltered database export
```

## Rendering XLSX controls

By default, the export dropdown renders CSV controls only.

```twig
{{ zhortein_datatable('users') }}
```

To show XLSX controls, enable the format explicitly:

```twig
{{ zhortein_datatable('users', {
    exportFormats: ['csv', 'xlsx']
}) }}
```

To show only XLSX controls:

```twig
{{ zhortein_datatable('users', {
    exportFormats: ['xlsx']
}) }}
```

Custom URLs can be provided per format:

```twig
{{ zhortein_datatable('users', {
    exportFormats: ['csv', 'xlsx'],
    exportUrls: {
        csv: path('custom_users_csv_export'),
        xlsx: path('custom_users_xlsx_export')
    }
}) }}
```

## Column visibility

XLSX export respects runtime column visibility.

If a column is hidden in the current datatable state, it is not exported.

Definition-hidden columns are never exported.

## Headers

XLSX export uses column labels as header cells.

Example:

```php
$definition
    ->addColumn('e.email', label: 'Email')
    ->addColumn('e.displayName', label: 'Display name')
;
```

Generated first row:

```text
Email | Display name
```

## Values

The XLSX writer normalizes values before writing cells.

Supported value directions:

- `null`;
- string;
- integer;
- float;
- boolean;
- `DateTimeInterface`;
- `DateInterval`;
- `Stringable`;
- arrays converted to JSON strings;
- unknown objects converted to their debug type.

## Limitations

### Synchronous export

The first XLSX implementation is synchronous.

Very large exports may hit:

- PHP execution time limits;
- memory limits;
- web server timeouts;
- browser timeout expectations.

For large exports, future asynchronous exports are recommended.

### No styling

The first XLSX writer focuses on data export.

It does not currently provide:

- workbook styling;
- column width configuration;
- frozen panes;
- formulas;
- multiple sheets;
- charts;
- images.

### No streaming provider contract yet

OpenSpout is streaming-oriented, but the current export pipeline still receives a `DatatableResult`.

A future streaming provider/export contract may be needed for very large datasets.

### No XLSX-specific authorization layer

XLSX export uses the same endpoint and application security expectations as CSV export.

Host applications must protect export routes according to their own security rules.

## Recommended usage

For normal business datatables:

```twig
{{ zhortein_datatable('users', {
    exportFormats: ['csv', 'xlsx']
}) }}
```

For large datasets:

- encourage users to filter before exporting;
- keep selected columns minimal;
- consider disabling full export if needed;
- consider future async exports for large volumes.

## Memory and performance

The current XLSX writer is synchronous.

It is appropriate for small and medium filtered exports.

It should not be presented as a solution for very large exports yet.

See [`xlsx-export-performance.md`](xlsx-export-performance.md) for detailed constraints and future directions.

## XLSX export milestone completion

Milestone 0.20 completed the XLSX export decision and implementation path.

Summary:

- CSV remains the default dependency-free export format.
- XLSX is supported as an optional OpenSpout-based writer.
- XLSX export controls are conditional.
- XLSX follows the same current/full mode semantics as CSV.
- XLSX memory and performance constraints are documented.

Large XLSX exports remain a future topic and should be handled through a later async/streaming export milestone.

## Related documentation

- [Server-side exports](exports.md)
- [XLSX export strategy decision](decisions/0007-xlsx-export-strategy.md)
- [Doctrine provider performance guidance](doctrine-performance.md)
- [Configuration](configuration.md)
