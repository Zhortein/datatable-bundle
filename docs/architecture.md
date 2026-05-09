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

## Renderer

The renderer is responsible for Twig rendering and Bootstrap-first templates.

### Rendering strategy

The bundle uses a Twig-first rendering strategy.

The backend renders datatable HTML fragments, and the Stimulus controller updates these fragments through Ajax.

The frontend controller must not duplicate cell rendering logic in JavaScript.

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
