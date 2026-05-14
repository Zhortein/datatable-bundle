# Public API review

This document reviews the current public API of `zhortein/datatable-bundle` before the first pre-release.

The goal is to identify public names, extension points and potential breaking-change risks before tagging an alpha release.

## Status

The bundle is still under active development.

The API should be considered unstable until a stable 1.0 release.

A first pre-release may still accept breaking changes, but public API decisions should now become more intentional.

## Public package namespace

Runtime namespace:

```text
Zhortein\DatatableBundle
```

Test namespace:

```text
Zhortein\DatatableBundle\Tests
```

The runtime namespace is appropriate and should be kept.

## Bundle class

Public class:

```php
Zhortein\DatatableBundle\ZhorteinDatatableBundle
```

Status:

```text
Keep
```

The name is explicit and follows Symfony bundle conventions.

## Public attributes

### `AsDatatable`

```php
Zhortein\DatatableBundle\Attribute\AsDatatable
```

Current usage:

```php
#[AsDatatable(name: 'users', provider: 'doctrine')]
final class UserDatatable implements DatatableInterface
{
}
```

Constructor options:

- `name`;
- `label`;
- `provider`.

Status:

```text
Keep
```

Notes:

- `name` is the main public datatable identifier.
- `provider` is useful but provider resolution still needs to remain consistent.
- `label` is currently light-use metadata; it should either gain usage later or remain harmless.

## Public contracts

### DatatableInterface

```php
Zhortein\DatatableBundle\Contract\DatatableInterface
```

Current method:

```php
public function buildDatatable(DatatableDefinition $definition): void;
```

Status:

```text
Keep
```

Notes:

- Simple and readable.
- Does not expose request/user context yet.
- If context-aware datatables become necessary, prefer adding a new optional interface rather than breaking this one.

### DataProviderInterface

```php
Zhortein\DatatableBundle\Contract\DataProviderInterface
```

Current methods:

```php
public function supports(DatatableDefinition $definition): bool;

public function getData(DatatableDefinition $definition, DatatableRequest $request): DatatableResult;
```

Status:

```text
Keep for now
```

Potential future concern:

- large exports may require iterable/streaming data access.
- avoid breaking this interface too soon; add a separate export/streaming provider interface if needed.

### ExportWriterInterface

```php
Zhortein\DatatableBundle\Contract\ExportWriterInterface
```

Current methods:

```php
public function supports(ExportFormat $format): bool;

public function write(
    DatatableExportRequest $request,
    DatatableDefinition $definition,
    DatatableResult $result,
): Response;
```

Status:

```text
Keep for CSV foundation
```

Potential future concern:

- very large exports may need streaming provider support.
- XLSX may need a different output strategy.
- acceptable for alpha.

## Definition objects

### DatatableDefinition

```php
Zhortein\DatatableBundle\Definition\DatatableDefinition
```

Current responsibilities:

- entity class;
- translation domain;
- columns;
- joins;
- filters;
- permanent filters;
- row actions;
- global actions;
- options.

Status:

```text
Keep, but monitor size
```

Potential concern:

- it is accumulating responsibilities.
- acceptable for now because it is a builder/definition object.
- if it grows much more, consider extracting sub-builders later.

### ColumnDefinition

```php
Zhortein\DatatableBundle\Definition\ColumnDefinition
```

Current responsibilities:

- name;
- label;
- visibility;
- sortable/searchable flags;
- CSS class;
- custom template;
- type.

Status:

```text
Keep
```

Potential future improvements:

- add column options;
- migrate `type` to `CellType|string|null` or `CellType|null`;
- keep current string type for alpha compatibility.

### ActionDefinition

```php
Zhortein\DatatableBundle\Definition\ActionDefinition
```

Current responsibilities:

- name;
- route;
- label;
- icon;
- HTTP method;
- confirmation message;
- class name;
- route parameters;
- attributes.

Status:

```text
Keep
```

Potential concern:

- `attributes` is currently also used for metadata such as `permission`.
- consider separating HTML attributes from action metadata before stable 1.0 if this becomes confusing.

### FilterDefinition

```php
Zhortein\DatatableBundle\Definition\FilterDefinition
```

Backend-only permanent filters.

Status:

```text
Keep
```

### UserFilterDefinition

```php
Zhortein\DatatableBundle\Definition\UserFilterDefinition
```

User-facing filters.

Status:

```text
Keep
```

Naming is clear enough because it separates frontend/user filters from permanent backend filters.

### JoinDefinition

```php
Zhortein\DatatableBundle\Definition\JoinDefinition
```

Doctrine explicit joins.

Status:

```text
Keep
```

Potential future concern:

- currently oriented toward Doctrine association paths.
- if non-Doctrine providers introduce joins, this may need to stay Doctrine-specific or move under a Doctrine namespace.
- acceptable for alpha because current join behavior is Doctrine-oriented.

## Enums

Current public enums:

```text
CellType
ExportFormat
ExportMode
FilterOperator
FilterType
JoinType
SortDirection
```

### CellType

Status:

```text
Keep
```

Potential future:

- add `Money`, `Percent`, `Html`, `Url`, `Email` only when templates exist.

### ExportFormat

Status:

```text
Keep CSV only for now
```

Do not add `Xlsx` until an XLSX writer exists.

### ExportMode

Status:

```text
Keep
```

Current values:

- current;
- full.

### FilterOperator

Status:

```text
Keep
```

Used for permanent filters.

### FilterType

Status:

```text
Keep
```

Used for user-facing filters.

### JoinType

Status:

```text
Keep
```

Current values:

- inner;
- left.

### SortDirection

Status:

```text
Keep
```

Current values:

- asc;
- desc.

## Request/result objects

### DatatableRequest

```php
Zhortein\DatatableBundle\Request\DatatableRequest
```

Status:

```text
Keep
```

It carries:

- pagination;
- search;
- sorting;
- filters;
- column visibility;
- options;
- pagination enabled/disabled state.

Potential concern:

- may become too broad later.
- acceptable because it represents normalized datatable runtime state.

### DatatableResult

```php
Zhortein\DatatableBundle\Result\DatatableResult
```

Status:

```text
Keep
```

Potential future:

- add metadata bag if needed;
- keep simple for now.

### DatatableExportRequest

```php
Zhortein\DatatableBundle\Export\DatatableExportRequest
```

Status:

```text
Keep
```

### DatatableExportResult

```php
Zhortein\DatatableBundle\Export\DatatableExportResult
```

Status:

```text
Review later
```

Currently not central to the writer contract because writers return `Response`.

Potential options:

- keep for future non-Response export pipelines;
- remove before stable if unused.

For alpha, it can remain.

## Registries and factories

### DatatableRegistry

Status:

```text
Keep
```

### DataProviderRegistry

Status:

```text
Keep
```

### ExportWriterRegistry

Status:

```text
Keep
```

### DatatableDefinitionFactory

Status:

```text
Keep
```

### DatatableRequestFactory

Status:

```text
Keep
```

Naming is clear and service responsibilities are well scoped.

## Providers

### ArrayDataProvider

```php
Zhortein\DatatableBundle\Provider\ArrayDataProvider
```

Status:

```text
Keep as demo/test provider
```

Should remain documented as not intended for large production datasets.

### DoctrineOrmDataProvider

```php
Zhortein\DatatableBundle\Provider\DoctrineOrmDataProvider
```

Status:

```text
Keep
```

Potential concern:

- class is growing large.
- future refactor may extract query builders, filter appliers, search appliers, sorting appliers.
- do not do this before alpha unless it becomes painful.

## Doctrine services

### DoctrineFieldTypeGuesser

Status:

```text
Keep
```

### DoctrineDatatableDefinitionEnricher

Status:

```text
Keep
```

Potential future:

- make enrichment explicit in data pipeline if automatic behavior grows.

## Advanced Doctrine public API note

After milestone 0.19, advanced Doctrine features remain intentionally backend-defined.

Before stable 1.0, revisit:

- whether `JoinDefinition` and `CustomJoinDefinition` should stay in the generic `Definition` namespace;
- whether aggregate columns need a dedicated builder API;
- whether custom join parameters should remain an option bag or become a typed definition field;
- whether count strategy should become externally configurable.

## Rendering services

### DatatableRenderer

Status:

```text
Keep, but monitor size
```

Potential concern:

- class has accumulated many normalization responsibilities.
- future refactor could extract:
  - action normalizer;
  - row normalizer;
  - cell template resolver;
  - table option resolver.
- acceptable for alpha.

### DateTimeFormatterInterface

Status:

```text
Keep
```

Good extension point for applications with locale/timezone needs.

### DefaultDateTimeFormatter

Status:

```text
Keep
```

## Action/security services

### RowActionRouteParameterResolver

Status:

```text
Keep
```

### ActionVisibilityCheckerInterface

Status:

```text
Keep
```

### AllowAllActionVisibilityChecker

Status:

```text
Keep
```

### AuthorizationActionVisibilityChecker

Status:

```text
Keep optional
```

Potential concern:

- `permission` is currently read from action attributes.
- before 1.0, consider a separate metadata field if this becomes too confusing.

## Preference services

### DatatablePreference

Status:

```text
Keep
```

### DatatablePreferenceProviderInterface

Status:

```text
Keep
```

### NullDatatablePreferenceProvider

Status:

```text
Keep
```

Good extension point without user-model coupling.

## Export services

### CsvExportWriter

Status:

```text
Keep
```

Potential future:

- add delimiter/enclosure configuration;
- add UTF-8 BOM option;
- streaming optimization.

## Twig API

### Function `zhortein_datatable`

Status:

```text
Keep
```

Current usage:

```twig
{{ zhortein_datatable('users') }}
```

This is a good public API.

### Function `zhortein_datatable_datetime`

Status:

```text
Internal-ish, review before stable
```

This function is used by internal templates.

It is public in Twig once registered.

Potential future:

- document as internal rendering function;
- avoid encouraging applications to call it directly.

## Routes

Current routes:

```text
zhortein_datatable_fragments
zhortein_datatable_export
```

Status:

```text
Keep
```

Route names are namespaced and clear.

Current paths:

```text
/_zhortein/datatable/{name}/fragments
/_zhortein/datatable/{name}/export/{format}
```

Potential future:

- route prefix configuration;
- route import recipe;
- separate route sets for admin/customer contexts.

## Service tags

Current tags:

```text
zhortein_datatable.datatable
zhortein_datatable.data_provider
zhortein_datatable.export_writer
```

Status:

```text
Keep
```

Names are explicit and namespaced.

## Configuration keys

Root key:

```text
zhortein_datatable
```

Status:

```text
Keep
```

Current keys:

```yaml
default_provider
default_theme
default_page_size
max_page_size
search_enabled
bootstrap.table.*
```

Status:

```text
Keep
```

Potential future:

- route prefix;
- export defaults;
- CSV delimiter;
- translation domain override;
- action defaults.

## Current API risks before stable 1.0

### DatatableRenderer size

Risk:

```text
Medium
```

The renderer has grown. Consider extracting collaborators before 1.0 if it continues growing.

### ActionDefinition attributes used as metadata

Risk:

```text
Medium
```

`attributes` are rendered as HTML attributes but also used for `permission`.

This may be confusing. Consider a separate metadata/options bag before stable 1.0.

### DatatableExportResult usage

Risk:

```text
Low
```

Currently not central. Review before stable.

### JoinDefinition namespace

Risk:

```text
Low to medium
```

It is Doctrine-oriented but lives in the generic `Definition` namespace.

If joins remain Doctrine-specific only, consider `DoctrineJoinDefinition` before stable.

### Template context stability

Risk:

```text
Medium
```

Templates are documented, but some areas still evolve.

Keep `docs/template-context.md` updated.

## Recommendation before first alpha

The API is coherent enough for an alpha release after:

- release checklist is prepared;
- examples are reviewed;
- documentation navigation is final;
- changelog is ready;
- no known debug CI remains.

## Follow-up issues to consider

Potential future issues:

- Extract action normalization from `DatatableRenderer`.
- Extract cell template resolution from `DatatableRenderer`.
- Review `ActionDefinition` metadata vs HTML attributes.
- Review `JoinDefinition` naming before stable.
- Decide whether `DatatableExportResult` remains public.
- Add `@internal` documentation where needed.
