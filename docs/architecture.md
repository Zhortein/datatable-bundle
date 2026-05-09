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

## Data provider

A data provider loads rows from a source.

The first provider will support Doctrine ORM.

Later providers may support arrays, APIs, Elasticsearch or custom application services.

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

## Ajax controller

The Ajax controller exposes generic endpoints used by the frontend controller.

Expected endpoints:

- columns;
- data;
- export.

## Stimulus controller

The Stimulus controller is responsible for frontend interactions.

It must use vanilla JavaScript only.

It must not depend on jQuery or DataTables.net.
