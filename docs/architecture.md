# Architecture

The bundle is organized around explicit responsibilities.

## Datatable class

A datatable class is defined in the host application.

It describes the datatable by implementing `DatatableInterface` and using the `#[AsDatatable]` attribute.

## Datatable definition

`DatatableDefinition` stores the high-level datatable configuration:

- name;
- entity class;
- translation domain;
- columns;
- actions;
- filters;
- options.

### Datatable definition factory

`DatatableDefinitionFactory` centralizes the process of building a `DatatableDefinition` from a registered datatable name.

It resolves the datatable through `DatatableRegistry`, creates a new `DatatableDefinition`, calls `buildDatatable()` and returns the completed definition.

This avoids duplicating definition-building logic in Twig extensions, controllers and future services.

## Data provider

A data provider loads rows from a source.

The first provider will support Doctrine ORM.

Later providers may support arrays, APIs, Elasticsearch or custom application services.

### Data provider contract and registry

Data loading is abstracted behind `DataProviderInterface`.

A data provider is responsible for converting a `DatatableDefinition` and a typed `DatatableRequest` into a typed `DatatableResult`.

The provider registry can resolve a provider explicitly by name or automatically by asking providers whether they support a given definition.

This keeps Doctrine ORM support isolated and allows future providers for arrays, APIs, Elasticsearch or custom application services.

### Array data provider

`ArrayDataProvider` is a simple provider intended for tests, demos and early rendering integration.

It reads rows from datatable definition options, supports basic pagination, simple scalar search and single-column sorting.

It is not intended to replace the future Doctrine ORM provider, but it allows the data pipeline to be tested without a database.

### Data provider container wiring

Data providers are regular Symfony services tagged with `zhortein_datatable.data_provider`.

The provider tag must define a `name` attribute.

Example:

```php
$services
    ->set(ArrayDataProvider::class)
    ->tag('zhortein_datatable.data_provider', [
        'name' => ArrayDataProvider::PROVIDER_NAME,
    ])
;
```

`DataProviderRegistry` receives the tagged providers as an indexed iterable.

This keeps provider discovery extensible and avoids hardcoding Doctrine as the only supported source.

### Doctrine provider strategy

The first data provider will target Doctrine ORM.

Doctrine support must be implemented in a dedicated provider layer.

The provider is responsible for loading structured data from Doctrine ORM based on a `DatatableDefinition` and a typed datatable request object.

It must not render HTML and must not parse Symfony HTTP requests directly.

The expected flow is:

```text
DatatableDefinition
+ DatatableRequest
→ DoctrineOrmDataProvider
→ DatatableResult
→ Twig renderer
→ HTML fragments
→ Stimulus update
```

Doctrine-specific responsibilities such as metadata type guessing, QueryBuilder construction, search, sorting, pagination and permanent filters must remain isolated from Twig rendering and frontend behavior.

The full architecture decision is documented in [docs/decisions/0005-doctrine-orm-provider-architecture.md](`docs/decisions/0005-doctrine-orm-provider-architecture.md`).

### Doctrine field type guesser

`DoctrineFieldTypeGuesser` isolates Doctrine metadata inspection from the Doctrine ORM provider.

It reads Doctrine ORM metadata and returns a `DoctrineFieldType` value object containing:

- field name;
- Doctrine DBAL type;
- datatable cell type;
- searchable flag;
- sortable flag;
- optional backed enum class.

This keeps type inference testable and avoids embedding metadata rules directly in the provider.

### Doctrine ORM data provider skeleton

`DoctrineOrmDataProvider` is the first production-oriented provider implementation.

The initial skeleton supports:

- datatable definitions with an entity class;
- simple visible-column selection on the main Doctrine alias `e`;
- offset pagination;
- total and filtered counts without search/filter distinction yet;
- `DatatableResult` output.

Search, sorting, permanent filters, association traversal and custom joins are implemented in later steps.

### Doctrine provider container wiring

`DoctrineOrmDataProvider` is registered as a tagged data provider when Doctrine is available.

The provider is tagged with:

```text
zhortein_datatable.data_provider
```

using the provider name:

```text
doctrine
```

Doctrine-specific services such as `DoctrineFieldTypeGuesser` and `DoctrineOrmDataProvider` are registered conditionally so the bundle can remain installable in applications that do not use Doctrine.

### Doctrine permanent filters

The Doctrine ORM provider applies backend-defined permanent filters from `DatatableDefinition`.

Permanent filters are translated into Doctrine QueryBuilder expressions and all values are bound as parameters.

They apply to both loaded rows and counts, so `totalItems` represents the visible universe for the datatable context.

### Doctrine global search

The Doctrine ORM provider supports simple global search on declared searchable columns.

The initial implementation supports:

- portable `LIKE` search on string-like fields;
- numeric equality search when the search query is numeric;
- safe parameter binding;
- permanent filters combined with search filters.

Database-specific behavior such as PostgreSQL `ILIKE`, JSON search and advanced search builders are intentionally out of scope for this step.

### Doctrine single-column sorting

The Doctrine ORM provider supports single-column sorting from `DatatableRequest`.

Sorting is applied only when:

- a sort field is present;
- the field matches a declared datatable column;
- the column is sortable;
- the field exists in Doctrine metadata.

Unknown or non-sortable fields are ignored safely.

### Doctrine provider documentation

Doctrine-backed datatables are documented in `docs/doctrine-provider.md`.

The documentation covers:

- requirements;
- entity-class based declarations;
- columns;
- pagination;
- global search;
- single-column sorting;
- permanent filters;
- Ajax response shape;
- current limitations.

### Datatable request object

Providers must receive a typed request object instead of parsing Symfony HTTP requests directly.

The request object stores pagination, search, sorting and runtime options in a normalized form.

This keeps providers independent from the HTTP layer and easier to test.

### Datatable HTTP request factory

`DatatableRequestFactory` converts Symfony HTTP requests into typed `DatatableRequest` objects.

It reads query and request payload parameters, applies safe defaults and normalizes invalid values.

This keeps controllers thin and prevents data providers from depending on the HTTP layer directly.

### Datatable result object

Data providers must return a typed result object instead of raw arrays.

The result object stores rows and pagination metadata:

- rows;
- current page;
- page size;
- total items;
- filtered items;
- total pages.

This keeps provider outputs explicit, testable and independent from Twig rendering or HTTP responses.

## Renderer

The renderer is responsible for Twig rendering and Bootstrap-first templates.

### Rendering strategy

The bundle uses a Twig-first rendering strategy.

The backend renders datatable HTML fragments, and the Stimulus controller updates these fragments through Ajax.

The frontend controller must not duplicate cell rendering logic in JavaScript.

### Stimulus interaction model

The bundle uses a vanilla Stimulus controller to orchestrate datatable interactions.

The controller is responsible for Ajax requests, loading state, error state, pagination, sorting, search and page size changes.

It must not render business cells manually in JavaScript. Cell and row rendering remains a Twig/server-side responsibility.

The controller receives server-rendered HTML fragments and updates the relevant DOM targets.

### Twig renderer skeleton

The rendering layer starts with a dedicated `DatatableRenderer` service.

The renderer receives a `DatatableDefinition` and returns a server-rendered Bootstrap datatable shell.

At this stage, the renderer does not load data and does not depend on Doctrine. It only renders the structural HTML and an empty state.

Future steps will connect this renderer to the provider layer and Ajax fragments.

### Twig datatable function

The first public rendering API is the `zhortein_datatable` Twig function.

Expected usage:

```twig
{{ zhortein_datatable('users') }}
```

The Twig extension is intentionally thin:

- it resolves the datatable by name through the registry;
- it creates a `DatatableDefinition`;
- it lets the datatable class build the definition;
- it delegates HTML rendering to `DatatableRenderer`.

Business rendering logic must remain in the renderer and Twig templates, not in the Twig extension.

### Row and cell rendering

The renderer can render table body rows from a `DatatableResult`.

Rows are normalized against visible columns from the `DatatableDefinition`.

Cell values are rendered through Twig templates and escaped by default.

The initial implementation uses a generic cell template only. Type-specific cell templates will be introduced later.

### Pagination rendering

The renderer can render Bootstrap pagination from a `DatatableResult`.

Pagination controls are server-rendered and include Stimulus-compatible attributes:

- `data-action="zhortein-datatable#goToPage"`;
- `data-zhortein-datatable-page-param`.

Pagination markup remains accessible with disabled states and `aria-current` on the active page.

### Row action route parameter resolver

`RowActionRouteParameterResolver` resolves route parameters for row actions from rendered row data.

It supports:

- direct row keys such as `id`;
- aliased row keys such as `e_id`;
- Doctrine-style dot notation such as `e.id`.

The resolver does not generate URLs. It only transforms an `ActionDefinition` route parameter mapping into resolved route parameter values.

URL generation remains the responsibility of the action rendering layer.

## Ajax controller

The Ajax controller exposes generic endpoints used by the frontend controller.

Expected endpoints:

- columns;
- data;
- export.

### Ajax controller skeleton

The bundle exposes a generic Ajax controller skeleton for future datatable refreshes.

The first endpoint returns server-rendered HTML fragments:

- body;
- pagination;
- summary;
- pagination metadata.

The controller resolves the datatable through the registry, lets the datatable build its definition, then delegates fragment rendering to `DatatableRenderer`.

It does not parse advanced request parameters yet and does not depend on Doctrine.

### Ajax fragments connected to providers

The Ajax fragments endpoint now connects the first complete server-side data pipeline:

```text
HTTP Request
→ DatatableRequestFactory
→ DatatableDefinitionFactory
→ DataProviderRegistry
→ DataProviderInterface
→ DatatableResult
→ DatatableRenderer
→ JSON HTML fragments
```

The controller remains thin and delegates request parsing, definition building, data loading and rendering to dedicated services.

The endpoint returns rendered `body` and `pagination` fragments plus pagination metadata.


## Stimulus controller

The Stimulus controller is responsible for frontend interactions.

It must use vanilla JavaScript only.

It must not depend on jQuery or DataTables.net.

### Stimulus controller skeleton

The bundle provides an initial vanilla Stimulus controller skeleton in `assets/controllers/datatable_controller.js`.

The controller is responsible for:

- refreshing server-rendered HTML fragments through `fetch()`;
- updating body and pagination targets;
- managing loading state;
- managing safe error display;
- handling search debounce;
- preparing future sort and pagination interactions.

It does not render cells manually and does not depend on jQuery or DataTables.net.

### Stimulus search and pagination loop

The datatable shell now exposes the values required by the Stimulus controller:

- datatable name;
- fragments URL;
- current page;
- page size.

The controller sends these values as query parameters to the fragments endpoint:

- `page`;
- `pageSize`;
- `search`;
- `sortField`;
- `sortDirection`.

Search uses a debounced refresh and pagination controls call `goToPage`.

The controller updates server-rendered `body`, `pagination` and `summary` fragments from the JSON payload.

## First end-to-end datatable flow

The first usable server-side datatable flow is documented in `docs/end-to-end-flow.md`.

Current flow:

```text
Datatable class
→ DatatableDefinitionFactory
→ DatatableDefinition
→ DatatableRequestFactory
→ DataProviderRegistry
→ DataProviderInterface
→ DatatableResult
→ DatatableRenderer
→ Twig fragments
→ Ajax JSON response
→ Stimulus DOM update
```

This flow currently uses `ArrayDataProvider` for tests and demos. Doctrine ORM support will be introduced later as a dedicated provider.

## Test / Quality

### Symfony test kernel

The test suite includes a minimal Symfony kernel under `tests/Functional/Kernel`.

This kernel registers:

- FrameworkBundle;
- TwigBundle;
- ZhorteinDatatableBundle;
- functional test datatable fixtures.

It allows the bundle to be tested in a real Symfony container, including service autoconfiguration, compiler passes, Twig function registration and bundle routes.

Unit tests remain preferred for isolated behavior. Functional tests should be used when Symfony integration itself must be verified.

### Doctrine functional test foundation

The functional test suite includes a minimal Doctrine ORM setup backed by in-memory SQLite.

It registers a test entity under `tests/Functional/Fixtures/Entity` and uses Doctrine `SchemaTool` to create and drop the schema during tests.

This foundation allows Doctrine provider features to be tested without requiring an external database service.
