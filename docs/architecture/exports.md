# Export Architecture

Server-side exports allow downloading datatable data in various formats while respecting the current table state (filters, sorting, etc.).

## Export Model

The export system uses typed objects to represent the request and delegates the HTTP result to each writer:

- `DatatableExportRequest`: Captures the datatable name, format, mode, and current request parameters.
- `ExportFormat`: Enum for supported formats (`csv`, `xlsx`).
- `ExportMode`: Enum for `current` (paged) or `full` (entire filtered dataset) modes.
- Symfony `Response`: Returned directly by the selected writer.

## Export Writer Contract

Exports are abstracted behind `ExportWriterInterface`. 

```php
interface ExportWriterInterface
{
    public function supports(ExportFormat $format): bool;
    public function write(
        DatatableExportRequest $request,
        DatatableDefinition $definition,
        DatatableResult $result,
    ): Response;
}
```

- **`ExportWriterRegistry`**: Resolves the appropriate writer for a given format.
- **Writers**: Registered as services tagged with `zhortein_datatable.export_writer`.

Custom writers construct and return their Symfony response directly. They do not need an intermediate bundle-specific result object.

## Format Implementations

### CSV Writer

`CsvExportWriter` provides native support for CSV exports without external dependencies. It uses PHP's built-in CSV handling and the resolved per-column export policy.

### XLSX Writer (Optional)

XLSX support is implemented via an optional writer that depends on `openspout/openspout`. It follows the same server-side pipeline as CSV.

## Export Flow

1. **Request**: Stimulus builds an export URL with current table state.
2. **Controller**: The `zhortein_datatable_export` endpoint resolves the datatable, provider, and writer.
3. **Data Fetching**: The provider fetches data. For `full` mode, pagination is disabled via `DatatableRequest::withoutPagination()`.
4. **Writing**: The resolved writer generates the response (e.g., streaming a file).

Before format normalization, each writer builds the same server-side
`CellContext` used by Twig. Named computed columns therefore have one resolver
for rendered and exported values. Twig templates and display-only boolean
negation are not applied to file output.

## Performance and Limitations

- **Synchronous**: Currently, exports are synchronous and data is loaded into memory before writing.
- **Memory**: Large "full" exports on datasets with millions of rows may hit PHP memory limits. A future streaming provider or async architecture is planned for these cases.

See [Cell Context and Computed Values](../cell-context.md).
