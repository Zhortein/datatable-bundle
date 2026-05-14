# XLSX export memory and performance constraints

This document reviews memory and performance constraints for XLSX exports in `zhortein/datatable-bundle`.

XLSX export is useful for business users, but it is more complex than CSV export.

The first XLSX implementation is synchronous and should be used with realistic dataset sizes.

## Current status

Implemented or planned in the current XLSX milestone:

- `xlsx` export format;
- optional OpenSpout-based XLSX writer;
- conditional XLSX export controls;
- frontend URL generation tests;
- documentation.

Not implemented yet:

- async exports;
- queued export jobs;
- export progress UI;
- streaming provider contract;
- export size limits;
- per-format authorization rules;
- XLSX styling;
- multi-sheet exports.

## Export pipeline reminder

The current export pipeline is:

```text
HTTP request
→ DatatableRequest
→ DataProviderInterface
→ DatatableResult
→ ExportWriterInterface
→ Response
```

The important part is:

```text
DataProviderInterface returns DatatableResult
```

This means the provider currently loads rows before the writer starts writing the file.

Even if the XLSX writer itself is streaming-oriented, the current provider contract is not a full streaming pipeline yet.

## Current/full mode behavior

XLSX uses the same modes as CSV.

### Current mode

Current mode keeps pagination.

It exports the current page only.

This is the safest mode for interactive UI usage.

### Full mode

Full mode disables pagination but keeps:

- permanent filters;
- user-facing filters;
- global search;
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

## Memory risks

Potential memory pressure comes from:

- loading all rows into `DatatableResult`;
- selected column count;
- value normalization;
- temporary file content;
- HTTP response buffering;
- PHP memory limit;
- web server buffering.

For large datasets, synchronous XLSX export can be expensive even if OpenSpout writes efficiently.

## Time risks

Potential timeout risks:

- Doctrine query time;
- row hydration time;
- XLSX generation time;
- response transmission time;
- reverse proxy timeout;
- browser download timeout expectations.

This is especially relevant for:

- full exports;
- wide tables;
- unfiltered exports;
- joined queries;
- aggregate columns;
- custom joins;
- slow database indexes.

## Recommended default usage

For most datatables:

- enable XLSX current export freely;
- enable XLSX full export only when the expected dataset size is reasonable;
- encourage users to filter before exporting;
- avoid exposing full export on very large tables without additional safeguards.

## Suggested soft limits

These are conservative guidance values, not hard-coded limits.

For synchronous XLSX exports:

| Dataset shape | Recommendation |
|---|---|
| up to a few thousand rows | Usually acceptable |
| tens of thousands of rows | Test carefully |
| hundreds of thousands of rows | Prefer async/streaming strategy |
| millions of rows | Do not use synchronous XLSX export |

Actual safe limits depend on:

- PHP memory limit;
- row width;
- query complexity;
- server hardware;
- database performance;
- web server/proxy configuration.

## Host application recommendations

Host applications should decide whether full XLSX export is appropriate for each datatable.

Recommended checks:

- expected maximum row count;
- average selected column count;
- query complexity;
- presence of joins/custom joins;
- active filters;
- database indexes;
- export frequency;
- user expectations.

## Configuration direction

The current milestone does not introduce hard export limits.

Future configuration may include:

```yaml
zhortein_datatable:
    export:
        xlsx:
            max_rows: 50000
            allow_full_export: true
```

This is intentionally postponed until real-world usage provides better constraints.

## Provider contract limitation

The current provider contract is:

```php
public function getData(DatatableDefinition $definition, DatatableRequest $request): DatatableResult;
```

This is not ideal for very large exports.

A future streaming contract may look like:

```php
interface StreamingDataProviderInterface
{
    public function iterateData(DatatableDefinition $definition, DatatableRequest $request): iterable;
}
```

or a dedicated export provider contract.

This should be considered before claiming large XLSX export support.

## Async export direction

For large exports, a future async export workflow is preferable.

Possible future flow:

```text
User requests export
→ export job is queued
→ worker generates file
→ user receives notification/download link
```

Possible Symfony components:

- Messenger;
- Lock;
- Filesystem;
- Notifier;
- custom export storage.

This is out of scope for the current XLSX milestone.

## Streaming writer vs streaming provider

OpenSpout can help write spreadsheet data efficiently.

But a streaming writer alone does not solve memory usage if all rows are already loaded before writing.

Both sides matter:

```text
streaming provider + streaming writer
```

The current milestone only introduces the writer side.

## XLSX controls and full export

When rendering XLSX controls, applications may choose:

```twig
{{ zhortein_datatable('users', {
    exportFormats: ['csv', 'xlsx']
}) }}
```

If a datatable is potentially large, consider disabling full export in a future UI option or using only current export.

Potential future option:

```twig
{{ zhortein_datatable('users', {
    exportFormats: ['csv', 'xlsx'],
    exportModes: ['current']
}) }}
```

This option is not implemented yet.

## Current decision

For this milestone:

- XLSX export remains synchronous.
- No hard row limit is introduced.
- Documentation must clearly warn about large exports.
- Large XLSX exports are not claimed as supported.
- Async/streaming support is deferred to a future milestone.

## Accepted risk

The accepted risk is that small and medium XLSX exports work now, while very large exports require future work.

This is acceptable because:

- the feature is alpha-stage;
- CSV export remains available;
- business apps often need XLSX for filtered result sets;
- the limitation is documented;
- future streaming/async support can be added without removing the current writer.

## Follow-up issues to consider

Potential future issues:

- Add XLSX full export size limit configuration.
- Add export mode configuration per datatable.
- Add streaming export provider contract.
- Add async export job support.
- Add export file storage abstraction.
- Add export progress/notification UI.
- Add XLSX writer memory benchmark.
- Add browser smoke test for XLSX downloads.

## Related documentation

- [XLSX export](xlsx-export.md)
- [Server-side exports](exports.md)
- [Doctrine provider performance guidance](doctrine-performance.md)
- [XLSX export strategy decision](decisions/0007-xlsx-export-strategy.md)
- [Roadmap](roadmap.md)
