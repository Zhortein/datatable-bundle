# Legacy Datatable Anti-Patterns

This document lists implementation patterns from the legacy application-specific datatable system that must not be reproduced in this bundle.

## Do not copy application-specific code

The legacy implementation belongs to a specific application.

It may contain:
- application-specific entities;
- application-specific services;
- application-specific routes;
- application-specific security rules;
- application-specific templates;
- application-specific naming conventions.

None of this code should be copied into the public bundle.

## Do not depend on DataTables.net

The new bundle must not depend on DataTables.net.

Reasons:
- the goal is to provide a Symfony-native datatable system;
- the frontend should remain lightweight;
- behavior should be controlled by the bundle;
- JavaScript must stay vanilla;
- Bootstrap rendering should be server-friendly and predictable.

## Do not depend on jQuery

The new bundle must not introduce jQuery.

Frontend behavior must use:
- Stimulus;
- native DOM APIs;
- `fetch()`;
- `URLSearchParams`;
- standard browser events.

## Do not create a monolithic datatable class

The legacy implementation concentrated too many responsibilities in one central class.

The final bundle must split responsibilities:

```text
Datatable class
→ DatatableDefinition
→ ColumnDefinition
→ ActionDefinition
→ FilterDefinition
→ Registry
→ DataProvider
→ QueryBuilder adapter
→ Renderer
→ Controller
→ Stimulus controller
```
Each class should have one clear responsibility.

## Do not instantiate datatables manually
The final bundle must not instantiate datatable classes with <code>new</code>.

Datatables must be regular Symfony services.

The registry should resolve them through dependency injection, service tags and a service locator.

## Do not scan all application services manually
The final bundle must not scan all container definitions manually to find datatables.

Discovery should rely on:

- PHP attributes;
- Symfony autoconfiguration;
- service tags;
- compiler passes where appropriate.

## Do not mix rendering and data loading

Rendering cell values and loading data from a data source are separate responsibilities.

The data provider should return structured rows.

The renderer should decide how values are displayed.

## Do not hardcode application routes

The bundle may expose default generic routes, but applications must be able to customize or wrap them.

The route design must not assume a specific application context such as customer portals, internal back offices or external user spaces.

## Do not hardcode application services

The bundle must not depend on services such as:

- application historizers;
- application cache managers;
- application locale providers;
- application-specific security helpers.

Equivalent concepts must be represented through bundle-level abstractions or optional extension points.

## Do not make PostgreSQL-only behavior implicit
If a feature depends on a database-specific capability, it must be explicit.

For example:

- case-insensitive search;
- custom SQL functions;
- aggregation functions;
- JSON querying.

Doctrine ORM compatibility must remain a priority.