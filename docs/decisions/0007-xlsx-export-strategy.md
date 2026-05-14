# 0007 - XLSX export strategy

## Status

Accepted.

## Context

The bundle already supports server-side CSV exports through the export pipeline.

Business users often expect spreadsheet exports, usually called "Excel export" in application interfaces. The actual format should be XLSX rather than legacy XLS.

Adding XLSX support has consequences:

- it introduces a third-party spreadsheet writer dependency;
- it may increase installation size;
- it can have memory and runtime implications;
- it may require streaming behavior for large datasets;
- it must not break applications that only need CSV exports;
- it should preserve the current server-side export model.

The bundle should remain Bootstrap-first, Symfony-oriented and dependency-light.

## Decision

XLSX export will be supported as an optional core writer based on OpenSpout.

The bundle will not make XLSX mandatory for all users.

The selected strategy is:

```text
core export pipeline + optional OpenSpout-based XLSX writer
```

This means:

- `ExportFormat` may support `xlsx`;
- the CSV writer remains dependency-free;
- the XLSX writer is available only when the required dependency is installed;
- XLSX controls must not be rendered if the writer is unavailable or disabled;
- documentation must explain the optional dependency;
- full export remains synchronous for now, with clear limitations;
- async/queued exports remain out of scope for this milestone.

## Rationale

OpenSpout is a pragmatic choice because it is designed for reading and writing spreadsheet files with a streaming-oriented approach.

Using an optional writer keeps the bundle useful for simple applications while enabling spreadsheet exports for business back-offices.

A separate package was considered, but would create extra maintenance and installation friction too early in the bundle lifecycle.

Keeping XLSX as an optional core writer is the best compromise for the alpha phase.

## Considered options

### Option 1 - No XLSX support

Pros:

- no dependency;
- no implementation cost;
- no memory risk.

Cons:

- weak business UX;
- many users expect spreadsheet exports;
- users may implement incompatible custom exports.

Rejected because spreadsheet export is a common professional back-office requirement.

### Option 2 - Mandatory OpenSpout dependency in core

Pros:

- simple implementation;
- writer always available;
- easier documentation.

Cons:

- forces every user to install a spreadsheet dependency;
- increases core package footprint;
- unnecessary for CSV-only applications.

Rejected because the bundle should stay dependency-light.

### Option 3 - Optional OpenSpout writer in core

Pros:

- keeps CSV-only installs lightweight;
- provides official XLSX support;
- reuses the existing export pipeline;
- avoids a separate package too early;
- keeps documentation and support centralized.

Cons:

- requires conditional service registration;
- UI must detect available export formats;
- missing dependency errors must be clear.

Accepted.

### Option 4 - Separate XLSX package

Pros:

- clean dependency isolation;
- core remains minimal.

Cons:

- additional package to maintain;
- more installation steps;
- premature split before the export API stabilizes.

Deferred. This can be revisited before stable 1.0 if the writer becomes large or needs independent release cycles.

## Implementation direction

The next implementation issues should follow this order:

1. Add XLSX format support.
2. Add optional OpenSpout dependency strategy.
3. Implement `XlsxExportWriter`.
4. Register the writer only when OpenSpout is available.
5. Render XLSX export controls only when enabled/available.
6. Extend frontend export URL tests for XLSX.
7. Document installation, usage and limitations.
8. Review memory/performance constraints.

## Dependency strategy

OpenSpout should be declared as an optional Composer suggestion first.

Example direction:

```json
{
  "suggest": {
    "openspout/openspout": "Required to enable XLSX export support."
  }
}
```

If implementation or service discovery needs stronger Composer integration, the dependency strategy can be revisited.

The bundle must fail gracefully when XLSX is requested without the writer dependency.

## UI strategy

Export controls must be based on available writer formats.

CSV remains available by default.

XLSX controls are rendered only when:

- the XLSX writer is registered;
- XLSX export is enabled by configuration or defaults;
- the current datatable allows export.

The UI must not render broken XLSX links.

## Runtime behavior

XLSX exports follow the same semantics as CSV exports:

- current-view export keeps pagination;
- full-dataset export removes pagination;
- filters, search, sorting and column visibility are preserved.

## Performance constraints

The first XLSX support remains synchronous.

The documentation must be conservative:

- avoid promising huge exports;
- recommend filtered exports;
- warn about memory/time limits;
- recommend async exports for future large datasets.

A future milestone may introduce:

- export size limits;
- streaming provider contracts;
- queued exports;
- export jobs and notifications.

## Consequences

Positive consequences:

- official spreadsheet export path;
- no mandatory spreadsheet dependency;
- existing CSV behavior remains unchanged;
- clear extension point for future export formats.

Negative consequences:

- conditional registration adds complexity;
- tests must cover missing dependency behavior;
- support documentation must be precise.

## Follow-up work

- Add XLSX export format support.
- Implement optional XLSX writer.
- Add conditional export controls.
- Test XLSX export URL generation.
- Document XLSX export strategy and usage.
- Review XLSX memory/performance constraints.
