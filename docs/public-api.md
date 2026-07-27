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

The builder methods documented in the feature guides are part of the contract, including regular and computed columns, simple and advanced filters, joins, aggregate columns, row/global/bulk actions, child datatable declarations, permanent filters and options.

The definition value objects returned by `DatatableDefinition` are also public:

- `ActionDefinition`;
- `AjaxActionOptions`;
- `AdvancedFilterFieldDefinition`;
- `AggregateColumnDefinition`;
- `BulkActionDefinition`;
- `ChildContextValue`;
- `ChildDatatableDefinition`;
- `ColumnDefinition`;
- `ContextFilterValue`;
- `CustomJoinDefinition`;
- `FilterDefinition`;
- `JoinDefinition`;
- `RouteParameter`;
- `UserFilterDefinition`.

Explicit server-side and browser-safe action context is represented by
`Context\DatatableContext`. Its constructor allowlist and immutable context
methods are public. The signing and request-restoration services remain bundle
implementation details; use the documented definition and render options.

The Ajax action response helper `Response\AjaxActionResponse` and its version
constant are public. Host controllers may return an equivalent JSON response,
but the documented v1 fields and semantics must be preserved.

See [configuration](configuration.md), [providers](providers.md), [filters](filters.md), [advanced filters](advanced-filters.md), [actions](actions.md), [explicit context](context.md), [hierarchical datatables](hierarchical-datatables.md), [cell context and computed values](cell-context.md), [bulk actions](bulk-actions.md) and [exports](exports.md).

## Extension contracts

Applications may implement or decorate these contracts:

- `Contract\CellValueResolverInterface`;
- `Contract\ChildDatatableAuthorizationCheckerInterface`;
- `Contract\DataProviderInterface`;
- `Contract\DatatableExportAuthorizationCheckerInterface`;
- `Contract\ExportRowCountProviderInterface`;
- `Contract\ExportWriterInterface`;
- `Contract\ExportCancellationInterface`;
- `Contract\StreamingDataProviderInterface`;
- `Contract\StreamingExportWriterInterface`;
- `Contract\EnumPresentationResolverInterface`;
- `Contract\IconResolverInterface`;
- `DateTime\DateTimeFormatterInterface`;
- `Action\ActionVisibilityCheckerInterface`;
- `Preference\DatatablePreferenceProviderInterface`;
- `Contract\DatatableViewProviderInterface`;
- `Contract\DatatableViewOwnerResolverInterface`;
- `Contract\DatatableViewAuthorizationCheckerInterface`.

Objects appearing in those signatures are part of the supported API:

- `Cell\CellContext`;
- `Action\ActionVisibilityContext`;
- `Export\DatatableExportAuthorizationContext`;
- `Export\DatatableExportRequest`;
- `Export\ExportRow`;
- `Export\ExportStreamContext`;
- `EnumPresentation\EnumPresentation`;
- `Preference\DatatablePreference`;
- `Request\DatatableRequest`;
- `Result\DatatableResult`;
- `Sorting\SortCriterion`;
- `Hierarchy\ChildDatatableAuthorizationContext`;
- `View\DatatableView`;
- `View\DatatableViewMetadata`;
- `View\DatatableViewState`;
- `View\DatatableViewScope`;
- `View\DatatableViewAuthorizationContext`.

The canonical URL and future saved-view state model is public:

- `State\DatatableState`;
- `State\DatatableStateUrlSerializer`;
- URL state payload version `1`.

Named views reuse this state model. Their provider, opaque ownership,
authorization, scope, revision and JSON behavior are documented in
[named saved views](saved-views.md).

Version 1 state transport keeps `filters` and `advancedFilters` as JSON objects
when empty. Legacy empty arrays remain accepted and normalized by the bundled
frontend; non-empty arrays are invalid for these map-like fields.

Version 1 state transport also includes an ordered `sorts` list. The historical
`sortField` and `sortDirection` fields remain the compatibility representation
of its first criterion. Payloads without `sorts` remain accepted throughout the
1.x series. See [multi-column sorting](sorting.md).

`DatatableResult` source alignment, `CellContext` accessors and
`DatatableDefinition::addComputedColumn()` are documented in [cell context and
computed values](cell-context.md). Resolver services are shared by Twig and
export writers. Export headers follow the definition translation domain and
current Symfony locale, matching rendered column labels.

Synchronous export limits and authorization are documented in
[exports](exports.md). `DatatableDefinition::setExportLimit()`, the two export
[exports](exports.md). `DatatableDefinition::setExportLimit()`, export
authorization/count contracts and their context semantics are additive public
1.x APIs. Custom providers may continue to implement only
`DataProviderInterface`, but synchronous export endpoints reject providers
without the explicit count capability before loading rows.

Bounded-memory exports add `StreamingDataProviderInterface` and
`StreamingExportWriterInterface` without changing either historical interface.
`ExportRow`, `ExportStreamContext` and `ExportCancellationInterface` are public
extension types. Capability negotiation requires both sides; otherwise the
materialized writer path remains supported. The default cancellation service
may be replaced through the interface alias.

Enum presentation metadata declared on columns and filters is resolved at
render/export time through `EnumPresentationResolverInterface`.
`EnumPresentation` and the default fallback order are documented in [enum
presentation](enum-presentation.md).

Doctrine-backed definitions are enriched from ORM metadata before they reach
rendering, requests or exports. Inference covers the main entity and explicitly
declared mapped, chained and custom join aliases. Explicit column types remain
authoritative; computed, unknown and undeclared fields remain untouched. This
runtime behavior is part of the built-in `doctrine` provider contract, while
the concrete metadata and factory services remain internal.

The advanced-filter expression model is public for custom providers:

- `AdvancedFilterExpression`;
- `ComparisonOperator`;
- `Condition`;
- `ExpressionInterface`;
- `Group`;
- `LogicOperator`;
- `OperatorCompatibility`.

Future asynchronous export support will reuse these additive streaming
contracts instead of changing `DataProviderInterface` or
`ExportWriterInterface` incompatibly.

## Enums and exceptions

Enums accepted by documented definition methods and runtime objects are public:

- `ActionDisplayMode`;
- `ActionIconPosition`;
- `AjaxActionSuccessStrategy`;
- `AggregateFunction`;
- `BooleanDisplayMode`;
- `CellType`;
- `ChildContextSource`;
- `ExportFormat`;
- `ExportMode`;
- `FilterLayout`;
- `FilterOperator`;
- `FilterType`;
- `JoinType`;
- `PaginationSize`;
- `RouteParameterSource`;
- `SortDirection`;
- `DatatableViewOperation`.

Exceptions under `Zhortein\DatatableBundle\Exception` may be caught by applications. The base exceptions `DatatableException`, `DataProviderException`, `ExportException` and `CellValueResolverException`, together with their current specialized subclasses, are covered by the 1.x compatibility policy.

## Named integration contracts

The following names are stable in 1.x:

| Contract | Stable names |
|---|---|
| Providers | `array`, `doctrine` |
| Writers | `csv`, `xlsx` |
| Service tags | `zhortein_datatable.datatable`, `zhortein_datatable.data_provider`, `zhortein_datatable.export_writer`, `zhortein_datatable.cell_value_resolver` |
| Routes | `zhortein_datatable_fragments`, `zhortein_datatable_child`, `zhortein_datatable_export` |
| Named-view routes | `zhortein_datatable_views_list`, `zhortein_datatable_views_create`, `zhortein_datatable_views_load`, `zhortein_datatable_views_mutate`, `zhortein_datatable_views_delete` |
| Twig | `zhortein_datatable()`, `zhortein_datatable_translate()`, `zhortein_datatable_enum_choices()` |
| Stimulus | `zhortein--datatable-bundle--datatable` |
| Ajax action events | `zhortein-datatable:action:before`, `zhortein-datatable:action:success`, `zhortein-datatable:action:error`, `zhortein-datatable:action:complete` |
| State events | `zhortein-datatable:state:change`, `zhortein-datatable:state:restore` |
| Ajax action response | version `1` |
| URL state | version `1`, `_zd_state[...]` namespace |
| Named views | JSON version `1`, `zhortein-datatable:view:*` events |
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
