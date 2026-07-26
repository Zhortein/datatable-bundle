# Public API and compatibility policy

This document defines the compatibility contract for `zhortein/datatable-bundle` 1.x, starting with `1.0.0`.

## Compatibility promise

Within the 1.x series:

- documented public PHP classes, interfaces, methods, constructor parameters and enum cases remain backward compatible;
- documented configuration keys, service tags, routes, Twig functions and frontend identifiers remain available;
- new optional parameters, enum cases, configuration keys and extension interfaces may be added in minor releases;
- removals or incompatible signature changes require a deprecation path and a new major version, except for security fixes that cannot safely preserve the old behavior.

Code being autoloadable under the bundle namespace does not by itself make it part of this promise. The supported surface is listed below or documented in the linked feature guides.

## Datatable declaration API

The primary application-facing API is stable:

- `Attribute\AsDatatable`;
- `Contract\DatatableInterface`;
- `Definition\DatatableDefinition`;
- the `zhortein_datatable()` Twig function.

The builder methods documented in the feature guides are part of the contract, including columns, simple and advanced filters, joins, aggregate columns, row/global/bulk actions, permanent filters and options.

The definition value objects returned by `DatatableDefinition` are also public:

- `ActionDefinition`;
- `AdvancedFilterFieldDefinition`;
- `AggregateColumnDefinition`;
- `BulkActionDefinition`;
- `ColumnDefinition`;
- `CustomJoinDefinition`;
- `FilterDefinition`;
- `JoinDefinition`;
- `RouteParameter`;
- `UserFilterDefinition`.

Explicit server-side action context is represented by
`Context\DatatableContext`.

See [configuration](configuration.md), [providers](providers.md), [filters](filters.md), [advanced filters](advanced-filters.md), [actions](actions.md), [bulk actions](bulk-actions.md) and [exports](exports.md).

## Extension contracts

Applications may implement or decorate these contracts:

- `Contract\DataProviderInterface`;
- `Contract\ExportWriterInterface`;
- `Contract\IconResolverInterface`;
- `DateTime\DateTimeFormatterInterface`;
- `Action\ActionVisibilityCheckerInterface`;
- `Preference\DatatablePreferenceProviderInterface`.

Objects appearing in those signatures are part of the supported API:

- `Action\ActionVisibilityContext`;
- `Export\DatatableExportRequest`;
- `Preference\DatatablePreference`;
- `Request\DatatableRequest`;
- `Result\DatatableResult`.

The advanced-filter expression model is public for custom providers:

- `AdvancedFilterExpression`;
- `ComparisonOperator`;
- `Condition`;
- `ExpressionInterface`;
- `Group`;
- `LogicOperator`;
- `OperatorCompatibility`.

Future streaming or asynchronous export support will use additive contracts instead of changing `DataProviderInterface` or `ExportWriterInterface` incompatibly.

## Enums and exceptions

Enums accepted by documented definition methods and runtime objects are public:

- `ActionDisplayMode`;
- `ActionIconPosition`;
- `AggregateFunction`;
- `BooleanDisplayMode`;
- `CellType`;
- `ExportFormat`;
- `ExportMode`;
- `FilterLayout`;
- `FilterOperator`;
- `FilterType`;
- `JoinType`;
- `PaginationSize`;
- `RouteParameterSource`;
- `SortDirection`.

Exceptions under `Zhortein\DatatableBundle\Exception` may be caught by applications. The base exceptions `DatatableException`, `DataProviderException` and `ExportException`, together with their current specialized subclasses, are covered by the 1.x compatibility policy.

## Named integration contracts

The following names are stable in 1.x:

| Contract | Stable names |
|---|---|
| Providers | `array`, `doctrine` |
| Writers | `csv`, `xlsx` |
| Service tags | `zhortein_datatable.datatable`, `zhortein_datatable.data_provider`, `zhortein_datatable.export_writer` |
| Routes | `zhortein_datatable_fragments`, `zhortein_datatable_export` |
| Twig | `zhortein_datatable()`, `zhortein_datatable_translate()` |
| Stimulus | `zhortein--datatable-bundle--datatable` |
| AssetMapper | `@zhortein/datatable-bundle` |
| Configuration root | `zhortein_datatable` |

The documented Twig blocks, template override paths and template context are supported customization points. Undocumented local variables or private partials may change in a minor version when that does not break the documented customization surface.

## Implementation boundary

Concrete services used internally by the bundle are not extension points unless another guide explicitly says otherwise. In particular, constructor signatures and implementation details in these areas may evolve during 1.x:

- controllers and dependency-injection compiler passes;
- factories, registries and renderers;
- concrete export writers and provider implementations;
- Doctrine query, metadata, join, count and pagination helpers;
- Twig extension service classes;
- the Stimulus controller's undocumented JavaScript methods.

Applications should use the documented contracts, configuration, tags and Twig API instead of instantiating these services directly. The built-in provider and writer names remain stable even when their internal constructors change.

`ArrayDataProvider::OPTION_ROWS`, the provider-name constants and the writer-name constants remain available because they are useful when building definitions and integrations.

## Decisions completed before 1.0

The pre-1.0 review resolved the earlier compatibility risks:

- action permissions have a dedicated `permission` argument and accessor; legacy `attributes.permission` input remains accepted;
- unused `DatatableExportResult` was removed before the stable contract;
- hidden columns have an explicit `exportable` policy instead of inheriting visibility accidentally;
- boolean negation is represented by the typed column definition;
- the existing provider and export writer contracts are retained, with future large-export work reserved for additive interfaces;
- Doctrine joins remain part of `DatatableDefinition` for 1.x because they are already documented and used by real applications.

The historical prerelease review remains available in the [archive](archive/milestones/public-api-review.md), but this document is authoritative for 1.x.

## Reporting compatibility problems

Open a [GitHub issue](https://github.com/Zhortein/datatable-bundle/issues) with the affected bundle version, PHP/Symfony versions and a minimal reproducer. A regression in the supported surface is treated as a bug.
