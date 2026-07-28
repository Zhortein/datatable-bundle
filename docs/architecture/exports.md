# Export Architecture

Server-side exports allow downloading datatable data in various formats while respecting the current table state (filters, sorting, etc.).

## Export Model

The export system uses typed objects to represent the request and delegates the HTTP result to each writer:

- `DatatableExportRequest`: Captures the datatable name, format, mode, and current request parameters.
- `DatatableExportAuthorizationContext`: Exposes the definition, format, mode, normalized state, Symfony request and signed business/child context to a replaceable checker.
- `ExportRowCountProviderInterface`: Additive provider capability used to count or conservatively bound filtered rows before materialization.
- `StreamingDataProviderInterface`: Additive provider capability yielding normalized `ExportRow` values incrementally.
- `StreamingExportWriterInterface`: Additive writer capability consuming those rows without a complete `DatatableResult`.
- `ExportStreamContext`: Immutable batch size, expected row count and cooperative cancellation signal.
- `ExportLimitResolver`: Resolves definition, format and global row-limit precedence.
- `ExportJobRequest`, `ExportJobIdentifier`, `ExportJobStatus` and `ExportJobResultMetadata`: Immutable background-job protocol.
- `ExportJobRepositoryInterface` and `ExportJobResultStorageInterface`: Host-owned persistence and chunked result delivery.
- `ExportArtifactWriterInterface`: Additive CSV/XLSX capability producing a bounded temporary artifact.
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

Streaming writers implement the separate capability without changing the
stable writer signature:

```php
interface StreamingExportWriterInterface
{
    public function writeStream(
        DatatableExportRequest $request,
        DatatableDefinition $definition,
        iterable $rows,
        ExportStreamContext $context,
    ): Response;
}
```

## Format Implementations

### CSV Writer

`CsvExportWriter` provides native support for CSV exports without external dependencies. Its streaming path writes header and rows directly to `php://output`.

### XLSX Writer (Optional)

XLSX support is implemented via an optional writer that depends on
`openspout/openspout`. Rows are fed incrementally into a temporary XLSX file,
then the completed archive is transferred to the response in fixed-size
chunks. The complete workbook is never read into a PHP string.

## Export Flow

1. **Request**: Stimulus builds an export URL with current table state.
2. **Controller**: The `zhortein_datatable_export` endpoint resolves the datatable and normalized request.
3. **Authorization**: The replaceable checker runs before provider access.
4. **Preflight**: The provider counts the filtered rows and the effective synchronous limit is enforced.
5. **Capability negotiation**: Streaming is selected only when both provider and writer expose their additive interfaces.
6. **Data Fetching**: The streaming provider yields bounded rows. Otherwise the provider returns the historical `DatatableResult`.
7. **Writing**: The streaming writer consumes rows incrementally. Otherwise the historical `write()` method remains in use.

## Background flow

```text
authorized canonical submission
→ owner + idempotency binding
→ repository pending job
→ RunExportJobMessage
→ ExportJobRunner
→ StreamingDataProviderInterface
→ ExportArtifactWriterInterface
→ ExportJobResultStorageInterface
→ authorized chunked download
```

The message contains only an opaque identifier. Definitions, Symfony requests
and security tokens never cross the transport. The worker rebuilds server-side
state and applies only the stored browser-safe context. Repository, result
storage, clock, expiry policy, owner resolution and dispatch are replaceable.

The bundled in-memory repository/storage are deterministic test adapters, not
cross-process production persistence. See [asynchronous exports](../async-exports.md).

Before format normalization, each writer builds the same server-side
`CellContext` used by Twig. Named computed columns therefore have one resolver
for rendered and exported values. Twig templates and display-only boolean
negation are not applied to file output.

## Performance and Limitations

- **Synchronous**: The request stays open while the export is produced.
- **Memory**: Doctrine batches, CSV output and XLSX generation are bounded; row limits still protect request duration and resource usage.
- **UnitOfWork**: Doctrine streams scalar projections and never clears the host application's entity manager.
- **Cancellation**: The default signal reflects a native client disconnect. Custom signals can replace the service alias.
- **Late errors**: Exceptions raised after streaming starts propagate; a partial download is never reported as a successful complete export.
- **Compatibility**: A provider or writer without streaming capability uses the materialized 1.x fallback.
- **Very long jobs**: The same provider/writer streaming capabilities feed asynchronous artifacts without keeping an HTTP request open.

See [Cell Context and Computed Values](../cell-context.md).
