# Doctrine Provider Architecture

The Doctrine provider is the primary production-oriented data source, responsible for generating DQL queries based on datatable definitions.

## Metadata Resolution

### `DoctrineFieldTypeGuesser`
Isolates Doctrine metadata inspection. It reads ORM metadata and returns a `DoctrineFieldType` value object (DBAL type, cell type, searchable/sortable flags).

### `DoctrineFieldMetadataResolver`
Resolves alias metadata for the main alias `e` and explicitly declared join aliases. It provides helpers to check field existence and read types from normalized `DoctrineFieldReference` objects.

### `DoctrineDatatableDefinitionEnricher`
Enriches definitions with inferred column types for columns without explicit metadata.

## Query Building

`DoctrineOrmDataProvider` delegates query-building responsibilities to internal collaborators:

- **`DoctrineFieldReferenceResolver`**: Normalizes field references.
- **`DoctrineJoinApplier`**: Applies inner and left joins.
- **`DoctrinePaginationApplier`**: Applies offset pagination.

### Joins

- **Explicit Joins**: Inner and left joins declared in the datatable class. Chained joins are supported but must be declared explicitly.
- **Custom Joins**: Backend-only declarations for entities without mapped associations. Aliases are strictly validated to avoid reserved DQL keywords.

### Filtering and Search

- **Permanent Filters**: Backend-defined filters applied to both rows and counts.
- **User Filters**: Frontend-controlled filters mapped to DQL expressions.
- **Global Search**: Portable `LIKE` search on searchable columns.

### Aggregate Columns

Explicit backend declarations for `count`, `sum`, `min`, `max`, and `avg`. They trigger grouping by selected non-aggregate columns.

## Count Strategy

`DoctrineCountExpressionFactory` isolates count expression generation.

- **Standard**: `COUNT(e)` when no row duplication is possible.
- **Distinct**: `COUNT(DISTINCT e.id)` when custom joins or aggregate columns are present, preventing inflated counts.

## Performance

The provider is designed to be explicit to ensure predictable query generation. Detailed performance guidance is available in [`doctrine-performance.md`](../doctrine-performance.md).
