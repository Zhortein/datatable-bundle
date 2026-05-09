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
