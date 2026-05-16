# Doctrine Provider Performance Guidance

This document gives practical performance guidance for Doctrine-backed datatables. For usage instructions, see the **[Doctrine ORM Provider](doctrine-provider.md)**.

Performance depends heavily on:

- entity mappings;
- database indexes;
- selected columns;
- joins;
- filters;
- search strategy;
- count strategy;
- export size.

## General principle

A datatable should expose only the data it needs.

Avoid treating a datatable as a generic entity dump.

Prefer:

- explicit columns;
- explicit joins;
- explicit filters;
- limited page sizes;
- indexed search/sort/filter fields;
- server-side exports with clear limits.

## Page size

Keep default page sizes reasonable.

Recommended defaults:

```yaml
zhortein_datatable:
    default_page_size: 25
    max_page_size: 500
```

Large page sizes increase query time, hydration time, HTML fragment size, network payload size and browser DOM update cost.

If a user needs all rows, prefer server-side export rather than a huge page size.

## Index sortable fields

Any frequently sorted Doctrine field should be indexed.

Common sortable fields:

- email;
- name;
- createdAt;
- status;
- organization name;
- numeric business identifiers.

## Index filtered fields

Permanent filters and user-facing filters should target indexed fields whenever possible.

Examples:

```php
$definition->addPermanentFilter('e.enabled', FilterOperator::Equals, true);
$definition->addFilter('email', 'e.email', type: FilterType::Text);
```

Recommended indexes:

- boolean flags used in permanent filters;
- status fields;
- foreign keys;
- created/updated date fields;
- fields used in frequent text filters.

## Text search limitations

The provider currently uses portable LIKE-style search for string fields.

This is useful and simple, but it is not a full-text search engine.

Limitations:

- `%term%` searches may not use standard B-tree indexes effectively;
- case-insensitive search can be database-dependent;
- large text fields can be expensive;
- multi-field global search can become costly.

For advanced search needs, consider future dedicated providers or application-specific search infrastructure.

## Avoid over-searching

Mark non-useful columns as not searchable:

```php
$definition->addColumn(
    name: 'e.createdAt',
    label: 'Created at',
    searchable: false,
);
```

Good searchable fields:

- email;
- name;
- public identifiers;
- short status labels.

Usually poor searchable fields:

- booleans;
- dates;
- large JSON values;
- large text blobs;
- technical IDs unless explicitly needed.

## Joins

The Doctrine provider supports explicit joins.

Example:

```php
$definition
    ->addJoin('organization', 'e.organization', JoinType::Left)
    ->addColumn('organization.name', label: 'Organization')
;
```

Guidance:

- declare only the joins you need;
- avoid unnecessary joins in large tables;
- index foreign keys;
- avoid joining large collections unless explicitly supported;
- prefer to-one associations for datatable columns.

## Chained joins

Explicit chained joins are supported.

Example:

```php
$definition
    ->addJoin('organization', 'e.organization', JoinType::Left)
    ->addJoin('organizationGroup', 'organization.group', JoinType::Left)
    ->addColumn('organizationGroup.name', label: 'Group')
;
```

Guidance:

- keep chained joins short;
- declare each alias explicitly;
- avoid deep object graphs;
- test generated query performance.

## Custom joins

Custom joins are available for backend-defined relations without Doctrine associations.

Example:

```php
$definition
    ->addCustomJoin(
        alias: 'audit',
        targetEntityClass: AuditLog::class,
        condition: 'audit.objectId = e.id AND audit.className = :audit_class_name',
        type: JoinType::Left,
    )
    ->setOption('customJoinParameters', [
        'audit_class_name' => User::class,
    ])
;
```

Guidance:

- use custom joins sparingly;
- index all fields used in the join condition;
- avoid joining high-cardinality logs unless needed;
- always bind parameters through backend-defined options;
- never expose join conditions to frontend input.

## Count strategy

The provider uses a count strategy based on the datatable query shape.

For simple datatables:

```text
COUNT(e)
```

For datatables that can duplicate root rows, such as custom joins or aggregate columns:

```text
COUNT(DISTINCT e.id)
```

This prevents duplicate joined rows from inflating total counts.

Trade-off:

- `COUNT(DISTINCT ...)` is often more expensive than plain `COUNT(...)`;
- it is required when joins can duplicate root rows;
- databases may need indexes to keep it efficient.

## Aggregate columns

Aggregate columns can require grouping.

Example:

```php
$definition
    ->addColumn('e.email', label: 'Email')
    ->addAggregateColumn(
        name: 'auditCount',
        field: 'audit.id',
        function: AggregateFunction::Count,
        label: 'Audit count',
    )
;
```

Guidance:

- use aggregates for summary values only when useful;
- keep grouped columns minimal;
- test pagination and count behavior;
- avoid many aggregate columns in the same datatable;
- document aggregate limitations for users.

## Pagination with joins and aggregates

Pagination should be used carefully with joins and aggregates.

Supported simple cases are tested, but complex aggregate scenarios may need further review.

Recommendations:

- keep page size moderate;
- use explicit ordering;
- avoid unstable order when paginating;
- test page 1 and later pages;
- test filtered counts.

## CSV full export

Full export mode disables pagination but keeps:

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

Performance guidance:

- avoid very large full exports in synchronous HTTP requests;
- prefer filters before exporting;
- consider future async exports for large datasets;
- keep selected export columns minimal;
- use indexes for active filters and sort fields.

## Memory and hydration

The current provider returns a `DatatableResult` with rows.

This is appropriate for normal paginated requests.

For large exports, a future streaming provider API may be needed.

Current limitation:

```text
no dedicated streaming provider contract yet
```

## Selecting only visible columns

The provider selects visible columns from the datatable definition.

Runtime column visibility affects rendering and exports, but provider-level optimization for runtime visibility may evolve further.

Guidance:

- do not define unnecessary columns;
- hide technical columns by definition when they are only needed for route parameters;
- keep export columns relevant.

## Route parameters and hidden IDs

It is common to keep IDs hidden but available for actions.

Example:

```php
$definition->addColumn(
    name: 'e.id',
    label: 'Id',
    visible: false,
    sortable: false,
    searchable: false,
);
```

This supports action route parameter resolution while avoiding visual noise.

## Database-specific tuning

This bundle aims for portable Doctrine behavior.

Application-specific tuning remains the host application's responsibility.

Examples:

- PostgreSQL trigram indexes;
- full-text indexes;
- partial indexes;
- composite indexes;
- materialized views;
- read replicas;
- denormalized reporting tables.

## Recommended checklist for a Doctrine datatable

Before shipping a Doctrine-backed datatable:

- [ ] Only necessary columns are declared.
- [ ] Non-useful columns are not searchable.
- [ ] Frequently sorted fields are indexed.
- [ ] Frequently filtered fields are indexed.
- [ ] Joins are explicit and minimal.
- [ ] Custom join conditions use indexed fields.
- [ ] Page size is reasonable.
- [ ] Full export is tested with realistic data volume.
- [ ] Permanent filters are tested.
- [ ] User-facing filters are tested.
- [ ] Counts are checked with joins.
- [ ] Later pages are tested.
- [ ] CSV export is tested.

## Current limitations

- No automatic deep association traversal.
- No collection-valued association support.
- No ManyToMany aggregation support.
- No async export.
- No streaming provider contract.
- No full-text search abstraction.
- No database-specific optimization layer.
- No automatic index recommendations.
- Aggregate support remains intentionally limited.

## Related documentation

- [Doctrine-backed datatables](doctrine-provider.md)
- [Filters](filters.md)
- [Server-side exports](exports.md)
- [Architecture](architecture.md)
- [Roadmap](roadmap.md)
