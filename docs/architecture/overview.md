# Architecture Overview

This document describes the high-level architecture of `zhortein/datatable-bundle`.

The bundle is designed as a Symfony 8+ reusable datatable system with:

- PHP-first datatable declarations;
- Symfony service discovery;
- provider-based data loading;
- Twig-first rendering;
- a theme registry with Bootstrap-first templates;
- Ajax fragment updates;
- vanilla Stimulus interactions;
- extensibility toward multiple data sources.

## High-level flow

The core server-side datatable flow is:

```text
Datatable class
→ DatatableDefinitionFactory
→ DatatableDefinition
→ DatatableRequestFactory
→ DataProviderRegistry
→ DataProviderInterface
→ DatatableResult
→ DatatableRenderer
→ ThemeRegistry
→ Twig fragments
→ Ajax JSON response
→ Stimulus DOM update
```

## Configuration

The bundle exposes configuration under the `zhortein_datatable` root key. Runtime services consume these bundle configuration values.

- `DatatableRenderer` receives defaults for theme, page size, and search enabled flags.
- `ThemeRegistry` resolves the selected `ThemeInterface` and immutable metadata.
- `DatatableRequestFactory` receives defaults for page size and maximum page size.

## Datatable declaration layer

### Datatable class

A datatable is declared as a PHP class in the host application implementing `DatatableInterface`. These are regular Symfony services discovered through autoconfiguration.

### Datatable registry

`DatatableRegistry` resolves registered datatable services by public name through Symfony dependency injection.

### Datatable definition factory

`DatatableDefinitionFactory` centralizes definition building by resolving the datatable service and building its `DatatableDefinition`.

## Definition model

### DatatableDefinition

Stores high-level configuration: name, entity class, translation domain, columns, actions, filters, and provider metadata.

### ColumnDefinition

Stores column metadata: name, label, visibility, sortable/searchable flags, CSS class, and template.

### ActionDefinition

Describes row and global actions: route, label, icon, HTTP method, and confirmation message.

### FilterDefinition

Represents backend-defined permanent filters applied by providers.

## Request and result objects

### DatatableRequest

Providers receive a typed `DatatableRequest` instead of parsing Symfony HTTP requests directly. It stores page, search, sort, and runtime options.

### DatatableRequestFactory

Converts Symfony HTTP requests into typed `DatatableRequest` objects,
normalizing parameters and applying defaults. A dedicated
`DatatableRequestInputSanitizer` bounds transport complexity, rejects
client-controlled internal options and reduces fields to the resolved
definition before provider execution.

### DatatableResult

Providers return a typed `DatatableResult` containing rows and pagination metadata, keeping outputs explicit and testable.

## Ajax controller layer

The Ajax controller (`zhortein_datatable_fragments`) connects the server-side pipeline. It remains thin, delegating request parsing, data loading, and rendering.

The endpoint returns rendered `body`, `pagination`, and `summary` fragments plus metadata in a JSON response.

The export endpoint has a parallel capability flow:

```text
authorization and count preflight
→ StreamingDataProviderInterface
→ iterable<ExportRow>
→ StreamingExportWriterInterface
→ streamed CSV or XLSX response
```

When either additive streaming capability is unavailable, the unchanged
`DataProviderInterface` → `DatatableResult` → `ExportWriterInterface` flow is
used for 1.x compatibility.

Opt-in asynchronous jobs reuse the canonical request and streaming provider,
but replace the HTTP writer with an artifact and host-provided persistence:

```text
job submission
→ ExportJobRepositoryInterface
→ optional Messenger message
→ ExportJobRunner
→ ExportArtifactWriterInterface
→ ExportJobResultStorageInterface
→ owner-bound download
```

## Test and quality architecture

The project adheres to strict quality gates: PHPUnit, PHPStan (max level), PHP-CS-Fixer, and twigcs.

### Unit vs Functional tests

- **Unit tests**: Isolated behavior (value objects, registries, factories).
- **Functional tests**: Symfony integration using a minimal in-memory kernel and SQLite database.
- **Frontend tests**: Vitest with jsdom for Stimulus controller behavior.

## Documentation navigation

The documentation entry point is `docs/index.md`. Architecture details are split into:

- [Overview](overview.md)
- [Providers](providers.md)
- [Rendering](rendering.md)
- [Stimulus](stimulus.md)
- [Exports](exports.md)
- [Doctrine](doctrine.md)
