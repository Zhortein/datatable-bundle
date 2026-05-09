# 0005 - Doctrine ORM provider architecture

## Status

Proposed

## Context

Doctrine ORM will be the first data provider supported by the bundle.

The bundle must make Doctrine-backed datatables easy to declare while keeping the architecture extensible toward other data sources later, such as arrays, APIs, Elasticsearch or custom application services.

The legacy implementation validated several useful Doctrine-related concepts:

- entity-class based datatables;
- automatic field detection from Doctrine metadata;
- association traversal;
- persistent filters;
- custom joins;
- sorting;
- pagination;
- global search;
- enum support.

However, the final bundle must not reproduce a monolithic class that mixes definition, Doctrine querying, rendering, request parsing and export handling.

## Decision

The Doctrine ORM support will be implemented as a dedicated provider layer.

The provider layer will be responsible for turning a `DatatableDefinition` and a datatable request object into a paginated result.

The provider must not render HTML.

Rendering remains the responsibility of the Twig rendering layer.

Frontend behavior remains the responsibility of the Stimulus controller.

## Provider abstraction

The bundle should define a generic provider contract.

Expected direction:

```php
interface DataProviderInterface
{
    public function supports(DatatableDefinition $definition): bool;

    public function getData(
        DatatableDefinition $definition,
        DatatableRequest $request,
    ): DatatableResult;
}
```

The exact names may evolve during implementation.

## Doctrine provider responsibilities

The Doctrine ORM provider is responsible for:

- validating that the datatable has an entity class;
- creating the base `QueryBuilder`;
- applying permanent filters;
- applying search criteria;
- applying sorting;
- applying pagination;
- resolving column metadata;
- returning structured row data;
- returning total and filtered counts.

The Doctrine provider must not:

- render Twig templates;
- generate HTML;
- know about Bootstrap;
- know about Stimulus;
- know about controller internals;
- contain application-specific security logic.

## Data flow

Expected flow:

```text
DatatableDefinition
+ DatatableRequest
→ DataProviderInterface
→ DoctrineOrmDataProvider
→ Doctrine QueryBuilder
→ DatatableResult
→ Twig renderer
→ HTML fragments
→ Stimulus update
```

## Definition requirements

A Doctrine-backed datatable must define an entity class.

Example:

```php
$definition
    ->setEntityClass(User::class)
    ->addColumn('e.id', visible: false)
    ->addColumn('e.email')
    ->addColumn('e.createdAt', searchable: false)
;
```

The main Doctrine alias should initially be:

```text
e
```

This alias may later become configurable if needed.

## Column names

For the first implementation, column names may use Doctrine-style paths:

```text
e.email
customer.name
organization.legalName
```

The provider should treat these names as query-level field references.

Later, a more explicit column path object may be introduced if strings become too ambiguous.

## Doctrine metadata type guessing

Doctrine metadata type guessing should be isolated in a dedicated service.

Expected responsibility:

```text
DoctrineFieldTypeGuesser
```

It should infer:

- Doctrine DBAL type;
- whether the field is an enum;
- enum class when available;
- whether the column is likely sortable;
- whether the column is likely searchable;
- default cell rendering type.

The provider itself should not contain all type-guessing rules.

## Supported initial field types

Initial type guessing should support:

- string;
- text;
- integer;
- smallint;
- bigint;
- decimal;
- float;
- boolean;
- date;
- datetime;
- datetimetz;
- json;
- simple array;
- backed enums.

## Search strategy

The first provider should support simple global search.

Search should apply only to columns marked as searchable.

The provider should avoid database-specific behavior by default.

Initial portable behavior:

- string-like fields use `LIKE`;
- numeric fields are searched only when the search value can be safely converted;
- boolean/date/json search may be postponed.

Case-insensitive search must be explicit later because portable behavior differs by database platform.

The bundle must not silently assume PostgreSQL `ILIKE`.

## Sorting strategy

The first provider should support single-column sorting.

Sorting should apply only to columns marked as sortable.

The request object should expose:

- sort field;
- sort direction.

Allowed sort directions:

```text
asc
desc
```

Invalid sort fields or directions should be rejected or ignored safely.

## Pagination strategy

The first provider should support offset pagination.

The request object should expose:

- page;
- page size.

The provider should return:

- current page;
- page size;
- total items;
- filtered items;
- total pages;
- rows.

A future cursor-based pagination strategy may be considered later for very large datasets.

## Counting strategy

The provider should produce two counts:

- total item count before user-controlled search;
- filtered item count after user-controlled search.

Permanent filters are part of the datatable definition and should usually apply to both total and filtered counts.

This means `totalItems` represents the total visible universe for the datatable context, not necessarily the full database table.

## Permanent filters

Permanent filters are defined by the backend and are never controlled by the frontend.

Example:

```php
$definition->addPermanentFilter('e.deletedAt', FilterOperator::IsNull);
```

The Doctrine provider must translate permanent filters into safe QueryBuilder expressions and parameters.

Supported initial operators:

- equals;
- not equals;
- greater than;
- greater than or equals;
- less than;
- less than or equals;
- in;
- not in;
- is null;
- is not null;
- between;
- like;
- not like.

## Custom joins

Custom joins are useful, but should not be part of the first implementation unless necessary.

When introduced, they should be represented by explicit value objects instead of unstructured arrays.

Expected future direction:

```php
$definition->addJoin(
    alias: 'customer',
    join: 'e.customer',
    type: JoinType::Left,
);
```

Non-mapped joins may be supported later, but they require careful design because they can introduce database-specific or application-specific assumptions.

## Association traversal

Automatic association traversal should not be implemented too early.

The first Doctrine provider should focus on columns explicitly declared by the developer.

If a column references an association alias, the required join should either:

- be declared explicitly; or
- be supported by a simple safe convention later.

This avoids hidden heavy queries and surprising joins.

## Enum support

Backed enum support should be part of type guessing.

The provider should keep enum values as values or enum instances in structured data.

Display decisions belong to the renderer and cell templates.

## Result object

The provider should return a result object rather than a raw array.

Expected direction:

```php
final readonly class DatatableResult
{
    public function __construct(
        public array $rows,
        public int $page,
        public int $pageSize,
        public int $totalItems,
        public int $filteredItems,
        public int $totalPages,
    ) {
    }
}
```

The actual implementation should use private properties and getters if that better matches the project style.

## Request object

A datatable request object should be introduced before provider implementation.

Expected responsibility:

```text
DatatableRequest
```

It should normalize frontend input into a typed object:

- page;
- page size;
- search query;
- sort field;
- sort direction;
- runtime options.

The Doctrine provider should not parse Symfony `Request` directly.

## Doctrine ORM 3 and 4 compatibility

The provider must avoid APIs known to be unstable between Doctrine ORM 3 and 4 when possible.

Compatibility strategy:

- depend on public Doctrine ORM APIs;
- keep query building simple;
- isolate metadata access;
- add tests around metadata/type guessing;
- test against both lowest and highest dependency sets in CI.

## Security considerations

The provider must never trust raw frontend field names blindly.

Sort and search fields must be checked against the columns declared in `DatatableDefinition`.

Permanent filters are backend-defined and trusted, but their values must still be bound as parameters.

No user-controlled value should be concatenated into DQL.

## Consequences

This design keeps Doctrine support powerful but isolated.

It prevents Doctrine-specific logic from leaking into rendering and frontend layers.

It also keeps the bundle open to future non-Doctrine providers.

The trade-off is that more small objects and interfaces are needed before full rendering works.

This is acceptable because the bundle aims for long-term maintainability.

## Follow-up tasks

- Define `DataProviderInterface`.
- Define `DatatableRequest`.
- Define `DatatableResult`.
- Implement `DoctrineFieldTypeGuesser`.
- Implement basic `DoctrineOrmDataProvider`.
- Add provider unit tests.
- Add functional tests with a minimal Doctrine test entity.
- Add documentation for Doctrine-backed datatables.
