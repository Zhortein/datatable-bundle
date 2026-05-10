# Architecture

This document describes the current architecture of `zhortein/datatable-bundle`.

The bundle is designed as a Symfony 8+ reusable datatable system with:

- PHP-first datatable declarations;
- Symfony service discovery;
- provider-based data loading;
- Twig-first rendering;
- Bootstrap-first templates;
- Ajax fragment updates;
- vanilla Stimulus interactions;
- extensibility toward multiple data sources.

The current implementation already supports a first end-to-end flow with `ArrayDataProvider` and a Doctrine ORM provider foundation.

---

## 1. Configuration

These values are currently exposed as container parameters. Their application to renderer and request defaults is handled in the next configuration step.

### Bundle configuration

The bundle exposes a first configuration surface under the `zhortein_datatable` root key.

Current options:

```yaml
zhortein_datatable:
    default_provider: doctrine
    default_theme: bootstrap
    default_page_size: 25
    max_page_size: 500
    search_enabled: false
```

This configuration is intentionally small and focused on defaults that can be applied by services.

Invalid values are rejected during Symfony container configuration.

### Applying configuration defaults

Runtime services consume bundle configuration values.

Current usage:

- `DatatableRenderer` receives the configured default theme, default page size and search enabled flag.
- `DatatableRequestFactory` receives the configured default page size and maximum page size.

Runtime options still override configured defaults when explicitly provided.

### Built-in translation catalog

The bundle provides built-in translation catalogs under the `zhortein_datatable` domain.

Initial locales:

- English;
- French.

Twig templates use stable translation keys for built-in labels such as search, loading, empty state, pagination, actions and boolean cells.

The bundle does not depend on BazingaJsTranslationBundle.

### Installation and configuration documentation

Installation and configuration documentation is maintained in:

- `docs/installation.md`;
- `docs/configuration.md`.

These documents describe bundle registration, route loading, translations, Stimulus/AssetMapper integration, Doctrine provider setup and current configuration options.

---

## 2. High-level flow

The first usable server-side datatable flow is:

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

This flow is documented in detail in [`end-to-end-flow.md`](end-to-end-flow.md).

---

## 3. Datatable declaration layer

### Datatable class

A datatable is declared as a PHP class in the host application.

The class:

- uses the `#[AsDatatable]` attribute;
- implements `DatatableInterface`;
- configures a `DatatableDefinition`.

Example direction:

```php
#[AsDatatable(name: 'users', provider: 'doctrine')]
final class UserDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setEntityClass(User::class)
            ->addColumn('e.email', label: 'Email')
        ;
    }
}
```

Datatable classes are regular Symfony services discovered through autoconfiguration and service tags.

### Datatable registry

`DatatableRegistry` resolves registered datatable services by public name.

It does not instantiate datatables manually. Services are resolved through Symfony dependency injection.

### Datatable definition factory

`DatatableDefinitionFactory` centralizes definition building.

It:

1. resolves the datatable through `DatatableRegistry`;
2. creates a new `DatatableDefinition`;
3. calls `buildDatatable()`;
4. returns the completed definition.

This avoids duplicating definition-building logic in Twig extensions, controllers and future services.

---

## 4. Definition model

### DatatableDefinition

`DatatableDefinition` stores high-level datatable configuration:

- name;
- entity class;
- translation domain;
- columns;
- row actions;
- global actions;
- permanent filters;
- provider/options metadata.

### ColumnDefinition

`ColumnDefinition` stores column metadata:

- name;
- label;
- visibility;
- sortable flag;
- searchable flag;
- CSS class;
- custom Twig template;
- cell type.

Columns can be replaced immutably through `withType()` when an enrichment step needs to infer metadata, for example from Doctrine.

### ActionDefinition

`ActionDefinition` describes row and global actions:

- name;
- route;
- label;
- icon;
- HTTP method;
- confirmation message;
- CSS class;
- route parameters;
- HTML attributes.

Actions are declarative. Rendering is handled by the renderer layer.

### FilterDefinition

`FilterDefinition` represents backend-defined permanent filters.

Permanent filters are never controlled by the frontend and are applied by providers.

---

## 5. Request and result objects

### DatatableRequest

Providers receive a typed `DatatableRequest` instead of parsing Symfony HTTP requests directly.

It stores:

- current page;
- page size;
- search query;
- sort field;
- sort direction;
- runtime options.

### DatatableRequestFactory

`DatatableRequestFactory` converts Symfony HTTP requests into typed `DatatableRequest` objects.

It reads query and request payload parameters, applies safe defaults and normalizes invalid values.

This keeps controllers thin and prevents data providers from depending on the HTTP layer directly.

### DatatableResult

Providers return a typed `DatatableResult` instead of raw arrays.

It stores:

- rows;
- current page;
- page size;
- total items;
- filtered items;
- total pages.

This keeps provider outputs explicit, testable and independent from Twig rendering or HTTP responses.

---

## 6. Data provider layer

A data provider loads rows from a source.

Data loading is abstracted behind `DataProviderInterface`.

A provider receives:

```text
DatatableDefinition
+ DatatableRequest
```

and returns:

```text
DatatableResult
```

### DataProviderRegistry

`DataProviderRegistry` resolves providers:

- explicitly by provider name;
- automatically by asking providers whether they support a definition.

Providers are regular Symfony services tagged with:

```text
zhortein_datatable.data_provider
```

The provider tag defines a `name` attribute.

Example:

```php
$services
    ->set(ArrayDataProvider::class)
    ->tag('zhortein_datatable.data_provider', [
        'name' => ArrayDataProvider::PROVIDER_NAME,
    ])
;
```

The registry receives tagged providers as an indexed iterable.

### ArrayDataProvider

`ArrayDataProvider` is a simple provider intended for tests, demos and early integration.

It reads rows from datatable definition options and supports:

- pagination;
- simple scalar search;
- single-column sorting.

It is not intended to replace the Doctrine ORM provider, but it allows the data pipeline to be tested without a database.

---

## 7. Doctrine ORM provider layer

Doctrine ORM is the first production-oriented provider.

The full design decision is documented in [`decisions/0005-doctrine-orm-provider-architecture.md`](decisions/0005-doctrine-orm-provider-architecture.md), and user-facing documentation lives in [`doctrine-provider.md`](doctrine-provider.md).

### Doctrine provider strategy

Doctrine support is implemented in a dedicated provider layer.

The expected flow is:

```text
DatatableDefinition
+ DatatableRequest
→ DoctrineOrmDataProvider
→ DatatableResult
→ DatatableRenderer
→ HTML fragments
→ Stimulus update
```

Doctrine-specific responsibilities remain isolated:

- metadata type guessing;
- QueryBuilder construction;
- pagination;
- permanent filters;
- global search;
- sorting;
- future joins/associations.

The provider must not render HTML and must not parse Symfony HTTP requests directly.

### Doctrine provider container wiring

`DoctrineOrmDataProvider` is registered as a tagged data provider when Doctrine is available.

It uses provider name:

```text
doctrine
```

Doctrine-specific services such as `DoctrineFieldTypeGuesser`, `DoctrineDatatableDefinitionEnricher` and `DoctrineOrmDataProvider` are registered conditionally so the bundle can remain installable in applications that do not use Doctrine.

### DoctrineFieldTypeGuesser

`DoctrineFieldTypeGuesser` isolates Doctrine metadata inspection from the provider.

It reads Doctrine ORM metadata and returns a `DoctrineFieldType` value object containing:

- field name;
- Doctrine DBAL type;
- datatable cell type;
- searchable flag;
- sortable flag;
- optional backed enum class.

This avoids embedding metadata rules directly in `DoctrineOrmDataProvider`.

### DoctrineDatatableDefinitionEnricher

`DoctrineDatatableDefinitionEnricher` enriches Doctrine-backed datatable definitions with inferred column types.

It uses `DoctrineFieldTypeGuesser` for columns without explicit type metadata.

Rules:

- explicit column types are preserved;
- definitions without entity class are ignored;
- unsupported aliases/fields are ignored safely;
- Doctrine-specific inference remains isolated from the generic renderer.

### DoctrineOrmDataProvider

`DoctrineOrmDataProvider` supports:

- definitions with an entity class;
- visible column selection on the main alias `e`;
- offset pagination;
- permanent filters;
- simple global search;
- single-column sorting;
- `DatatableResult` output.

Current limitations:

- only the main Doctrine alias `e` is supported;
- association traversal is not implemented yet;
- custom joins are not implemented yet;
- advanced user-controlled filters are not implemented yet;
- multi-column sorting is not implemented yet.

### Permanent filters

The Doctrine provider applies backend-defined permanent filters from `DatatableDefinition`.

Permanent filters are translated into QueryBuilder expressions and all values are bound as parameters.

They apply to both loaded rows and counts, so `totalItems` represents the visible universe for the datatable context.

### Global search

The Doctrine provider supports simple global search on declared searchable columns.

The initial implementation supports:

- portable `LIKE` search on string-like fields;
- numeric equality search when the search query is numeric;
- safe parameter binding;
- permanent filters combined with search filters.

Database-specific behavior such as PostgreSQL `ILIKE`, JSON search and advanced search builders is intentionally out of scope for now.

### Single-column sorting

The Doctrine provider supports single-column sorting from `DatatableRequest`.

Sorting is applied only when:

- a sort field is present;
- the field matches a declared datatable column;
- the column is sortable;
- the field exists in Doctrine metadata.

Unknown or non-sortable fields are ignored safely.

### Doctrine join definitions

Datatable definitions can declare explicit Doctrine joins.

The first join API uses:

- `JoinType`;
- `JoinDefinition`;
- `DatatableDefinition::addJoin()`;
- `DatatableDefinition::getJoins()`.

Example:

```php
$definition
    ->addJoin('organization', 'e.organization', JoinType::Left)
    ->addColumn('organization.name', label: 'Organization')
;
```

Join application in Doctrine queries is implemented in later steps of the milestone.

### Explicit Doctrine joins in provider

`DoctrineOrmDataProvider` can apply explicit joins declared on `DatatableDefinition`.

Supported join types:

- inner join;
- left join.

Joins are applied to row queries and count queries.

Only declared join aliases can be referenced by non-main fields. The main Doctrine alias remains `e`.

Joined column selection, sorting, search and filters are implemented in later issues of the milestone.

### Joined entity columns

`DoctrineOrmDataProvider` can select fields from explicitly declared join aliases.

Example:

```php
$definition
    ->addJoin('organization', 'e.organization', JoinType::Left)
    ->addColumn('e.email', label: 'Email')
    ->addColumn('organization.name', label: 'Organization')
;
```

The provider returns joined values with stable aliases such as:

```text
organization_name
```

The renderer can display those values because it already normalizes dot notation column names to result aliases.

### Sorting on joined Doctrine fields

`DoctrineOrmDataProvider` supports single-column sorting on fields from explicitly declared joins.

Example:

```php
$definition
    ->addJoin('organization', 'e.organization', JoinType::Left)
    ->addColumn('organization.name', label: 'Organization')
;
```

The provider resolves metadata for the joined alias and validates the target field before applying `ORDER BY`.

Only declared sortable columns can be used for sorting.

### Search on joined Doctrine fields

`DoctrineOrmDataProvider` supports global search on fields from explicitly declared joins.

Example:

```php
$definition
    ->addJoin('organization', 'e.organization', JoinType::Left)
    ->addColumn('organization.name', label: 'Organization')
;
```

The provider resolves Doctrine metadata for the joined alias before applying search expressions.

Only declared searchable columns participate in global search.

### Doctrine joins documentation

Doctrine joins and association fields are documented in `docs/doctrine-provider.md`.

The documentation covers:

- explicit join declaration;
- left and inner joins;
- joined columns;
- sorting on joined fields;
- search on joined fields;
- permanent filters on joined fields;
- current limitations.

### Permanent filters on joined Doctrine fields

`DoctrineOrmDataProvider` supports permanent filters on fields from explicitly declared joins.

Example:

```php
$definition
    ->addJoin('organization', 'e.organization', JoinType::Left)
    ->addPermanentFilter('organization.enabled', FilterOperator::Equals, true)
;
```

Joined permanent filters are applied to both rows and counts.

Only explicit join aliases can be used.

### Doctrine association test fixtures

The Doctrine functional test foundation includes associated entities for join-related tests.

Current fixtures:

- `DoctrineUser`;
- `DoctrineOrganization`.

`DoctrineUser` has a nullable ManyToOne association to `DoctrineOrganization`.

A shared `DoctrineSchemaMetadataTrait` provides metadata for both entities when creating in-memory SQLite schemas in functional tests.

This prepares the next steps of the Doctrine joins milestone without changing provider behavior yet.

### Applying user filters in Doctrine provider

`DoctrineOrmDataProvider` applies user-facing filters declared on `DatatableDefinition`.

Only declared filters are read from `DatatableRequest`.

Supported initial filter types:

- text;
- choice;
- boolean;
- date;
- date range;
- number;
- number range.

All values are bound as Doctrine parameters.

Unknown frontend filter input is ignored safely.

### User filters on joined Doctrine fields

Declared user-facing filters can target fields from explicitly declared Doctrine joins.

Example:

```php
$definition
    ->addJoin('organization', 'e.organization', JoinType::Left)
    ->addFilter(
        name: 'organization_name',
        field: 'organization.name',
        type: FilterType::Text,
    )
;
```

Only explicit join aliases can be used.

The provider resolves Doctrine metadata for the joined alias before applying filter expressions.

### Active filter summary and clear filters

The filter toolbar renders an active filter summary area and a clear filters button.

Stimulus keeps the active state synchronized by counting non-empty filter controls.

The clear filters action:

- clears all filter controls;
- resets the current page to 1;
- refreshes datatable fragments.

This gives users visible feedback when filters are active and a simple way to reset the table.

### User-facing filter definitions

`DatatableDefinition` can declare user-facing filters separately from backend-only permanent filters.

The first filter API uses:

- `FilterType`;
- `UserFilterDefinition`;
- `DatatableDefinition::addFilter()`;
- `DatatableDefinition::getFilters()`.

Example:

```php
$definition->addFilter(
    name: 'email',
    field: 'e.email',
    label: 'Email',
    type: FilterType::Text,
    placeholder: 'Search an email',
);
```

These filters are rendered and applied by later steps of the advanced filtering milestone.

### User-facing filters documentation

User-facing filters are documented in [`filters.md`](filters.md).

The documentation covers:

- filter declaration;
- request parameter format;
- text filters;
- choice filters;
- boolean filters;
- date and range filters;
- filters on joined Doctrine fields;
- toolbar rendering;
- Stimulus refresh;
- active filter summary;
- security model;
- current limitations.

### Filter request normalization

`DatatableRequest` can carry normalized user-facing filter values.

Filter values are read from the `filters` request parameter:

```text
filters[email]=alice@example.test
filters[status]=enabled
```

`DatatableRequestFactory` reads filters from query parameters or request payloads.

Empty values are normalized away, and providers only consume filters that are explicitly declared by the datatable definition.

---

## 8. Rendering layer

The rendering layer is Twig-first and Bootstrap-first.

The backend renders datatable HTML fragments, and the Stimulus controller updates those fragments through Ajax.

The frontend controller must not duplicate cell rendering logic in JavaScript.

The main rendering service is `DatatableRenderer`.

### Twig datatable function

The first public rendering API is the `zhortein_datatable` Twig function.

Expected usage:

```twig
{{ zhortein_datatable('users') }}
```

The Twig extension is intentionally thin:

- it delegates definition building to `DatatableDefinitionFactory`;
- it delegates rendering to `DatatableRenderer`.

Business rendering logic must stay in the renderer and Twig templates.

### Datatable shell

The datatable shell renders:

- toolbar;
- optional search input;
- loading target;
- error target;
- summary target;
- table;
- body target;
- pagination target;
- Stimulus values.

### Row and cell rendering

The renderer renders table body rows from a `DatatableResult`.

Rows are normalized against visible columns from `DatatableDefinition`.

Cell values are rendered through Twig templates and escaped by default.

### Typed cell templates

The renderer supports type-specific cell templates.

Initial supported types:

- `default`;
- `string`;
- `numeric`;
- `boolean`;
- `datetime`;
- `array`;
- `enum`.

The renderer uses the column `type` option when available and falls back to the default cell template for unknown types.

### Datetime cell formatting

Datetime cells are formatted through `DateTimeFormatterInterface`.

The default implementation uses Symfony request locale when available and can use `IntlDateFormatter` when the PHP Intl extension is installed.

If Intl is unavailable, it falls back to a deterministic PHP date format.

Applications can replace the formatter service to apply project-specific locale, timezone and formatting rules.

### Custom column template rendering

A column can define a custom Twig template through `ColumnDefinition::getTemplate()`.

Custom templates take precedence over built-in type-specific templates.

Custom cell templates receive:

- `column`: the `ColumnDefinition`;
- `value`: the cell value.

This allows applications to customize one column without replacing the whole datatable rendering layer.

### Pagination rendering

The renderer renders Bootstrap pagination from a `DatatableResult`.

Pagination controls include Stimulus-compatible attributes:

- `data-action="zhortein-datatable#goToPage"`;
- `data-zhortein-datatable-page-param`.

Pagination markup remains accessible with disabled states and `aria-current` on the active page.

### Sortable header rendering

Sortable columns are rendered as button controls in the table header.

The generated markup uses Stimulus parameters:

```html
data-action="zhortein-datatable#sort"
data-zhortein-datatable-field-param="e.email"
```

Columns marked as non-sortable remain static header text.

Current sort state rendering is handled separately.

### Current sorting state rendering

The datatable shell exposes the current sort state through Stimulus values:

```html
data-zhortein-datatable-sort-field-value="e.email"
data-zhortein-datatable-sort-direction-value="asc"
```

Sortable headers render active state metadata when they match the current sort field:

- `aria-sort="ascending"` or `aria-sort="descending"`;
- `data-zhortein-datatable-current-sort-param`;
- `data-zhortein-datatable-sort-direction-param`;
- a visually hidden sorted-state label.

This keeps sorting state accessible and synchronized with the Stimulus controller.

### Page size selector

The datatable toolbar can render a page size selector.

The selector is enabled by default and can be disabled through runtime options.

Rendered markup uses:

```html
data-zhortein-datatable-target="pageSizeInput"
data-action="change->zhortein-datatable#changePageSize"
```

Changing the page size resets the current page to 1 and refreshes Ajax fragments.

### Default column alignment by cell type

The renderer applies Bootstrap alignment classes when no explicit column class is provided.

Current defaults:

- `numeric` cells: `text-end`;
- `boolean` cells: `text-center`;
- `enum` cells: `text-center`;
- all other cell types: no automatic class.

Explicit `ColumnDefinition::getClassName()` values always take precedence over defaults.

### Accessibility markup

The datatable renderer provides accessibility-friendly markup for core controls.

Current improvements:

- search input has an explicit accessible label;
- page size selector has an accessible label;
- sortable headers expose accessible sort labels;
- active sort state uses `aria-sort`;
- pagination buttons expose page-specific labels;
- summary, loading and error areas use polite live regions;
- the datatable root exposes `aria-busy`.

This is not a full WCAG audit, but it provides a stronger baseline for professional back-office usage.

### Filter toolbar rendering

Declared user-facing filters are rendered in the datatable toolbar.

The first rendering layer supports:

- text filters;
- choice filters;
- boolean filters;
- date filters;
- date range filters;
- number filters;
- number range filters.

Rendered controls use stable request names:

```text
filters[filterName]
filters[filterName][from]
filters[filterName][to]
```

Stimulus integration and provider application are handled in later steps.

### Filter controls and Stimulus refresh

Filter controls rendered in the toolbar now expose:

```html
data-zhortein-datatable-filter-control="true"
```

and call the Stimulus `changeFilter` action.

The controller serializes filter controls by their names:

```text
filters[email]
filters[createdAt][from]
filters[createdAt][to]
```

Changing a filter resets the current page to 1 and refreshes Ajax fragments.

### Column visibility controls

The toolbar can render a column visibility control.

The control lists declared columns and exposes stable metadata:

- `data-zhortein-datatable-column-visibility-control="true"`;
- `data-zhortein-datatable-column-name`.

Definition-hidden columns are represented but marked with:

```html
data-zhortein-datatable-definition-hidden="true"
```

Stimulus wiring and backend request normalization are implemented in later steps.

### Runtime column visibility state

The renderer supports runtime column visibility through rendering options.

Supported options:

```php
[
    'visibleColumns' => ['e.email', 'e.displayName'],
    'hiddenColumns' => ['e.createdAt'],
]
```

Rules:

- definition-level hidden columns remain hidden;
- `visibleColumns` restricts rendering to a whitelist;
- `hiddenColumns` excludes matching columns;
- explicit `visibleColumns` and `hiddenColumns` use column names as declared in `DatatableDefinition`.

This is the first step toward user-controlled column visibility and preferences. HTTP request normalization, Stimulus controls and persistence are implemented separately.

### Column visibility controls and Stimulus refresh

Column visibility controls now call the Stimulus `changeColumnVisibility` action.

The controller serializes checked and unchecked columns as:

```text
visibleColumns[]=e.email
hiddenColumns[]=e.createdAt
```

Definition-hidden columns are not serialized.

Changing column visibility resets the current page to 1 and refreshes datatable fragments.

### Column visibility request normalization

`DatatableRequest` can carry runtime column visibility state.

The request factory reads:

```text
visibleColumns[]=e.email
hiddenColumns[]=e.createdAt
```

Normalized state is exposed through:

- `DatatableRequest::getVisibleColumns()`;
- `DatatableRequest::getHiddenColumns()`;
- `DatatableRequest::getColumnVisibilityOptions()`.

Providers and renderers still rely on declared columns. Frontend-provided column state only affects declared columns.

### Datatable preference extension point

The bundle exposes `DatatablePreferenceProviderInterface`.

The default implementation is `NullDatatablePreferenceProvider`, which returns an empty preference.

Host applications can replace this service to provide user-specific preferences without the bundle depending on a User entity or storage model.

The preference object can currently carry:

- page size;
- sort field;
- sort direction;
- visible columns;
- hidden columns.

### Preferences applied to rendering defaults

`DatatableTwigExtension` loads preferences from `DatatablePreferenceProviderInterface`.

Preference values are converted to render options and merged before calling `DatatableRenderer`.

Precedence rule:

```text
runtime Twig options > preference options > bundle defaults
```

This allows applications to provide user-specific defaults while keeping explicit render options authoritative.

### Column visibility and preferences documentation

Column visibility and preference extension points are documented in [`preferences.md`](preferences.md).

The documentation covers:

- runtime column visibility;
- toolbar column visibility controls;
- Stimulus visibility refresh;
- request normalization;
- `DatatablePreference`;
- `DatatablePreferenceProviderInterface`;
- the default null provider;
- rendering precedence;
- current limitations.

### Twig template override strategy

Twig template override strategy is documented in [`templates.md`](templates.md).

The documentation covers:

- current Bootstrap template tree;
- public override templates;
- cell template context;
- action template context;
- row/header/toolbar/pagination contexts;
- recommended override strategy;
- current limitations.

### Template context reference

The current Twig template context is documented in [`template-context.md`](template-context.md).

The documentation covers:

- main datatable shell context;
- toolbar context;
- header context;
- row and cell contexts;
- action context;
- filter context;
- pagination context;
- stable and evolving context keys.

This document acts as the current public rendering contract for template overrides.

### Bootstrap table display variants

The Bootstrap datatable shell supports runtime display variants.

Current options:

```twig
{{ zhortein_datatable('users', {
    tableStriped: true,
    tableHover: true,
    tableBordered: false,
    tableBorderless: false,
    tableSmall: false,
    tableResponsive: true
}) }}
```

Defaults preserve the current rendering:

```text
table table-striped table-hover align-middle mb-0
```

The responsive wrapper is enabled by default through `table-responsive`.

### Bootstrap rendering defaults

Bootstrap table display variants can be configured globally.

Configuration:

```yaml
zhortein_datatable:
    bootstrap:
        table:
            striped: true
            hover: true
            bordered: false
            borderless: false
            small: false
            responsive: true
```

These values are injected into `DatatableRenderer` as default render options.

Runtime Twig options still take precedence over configuration.

### Optional icon rendering strategy

Icons are optional and CSS-class based.

The bundle does not require Bootstrap Icons, FontAwesome, Symfony UX Icons or any SVG package.

Action icons render as decorative spans with `aria-hidden="true"`, while the action label remains visible and accessible.

The strategy is documented in `docs/icons.md`.

### Cell template reference

Cell templates are documented in `docs/cell-templates.md`.

The documentation covers:

- built-in cell templates;
- custom column templates;
- cell template context;
- fallback order;
- default alignment by cell type;
- Doctrine type enrichment;
- current limitations.

### Bootstrap template cleanup

Bootstrap templates have been reviewed for readability and consistency.

The cleanup focuses on:

- indentation;
- multiline attributes;
- Twig condition formatting;
- preserving existing behavior;
- keeping TwigCS compliance.

No functional behavior is intentionally changed by this cleanup.

### Theming and rendering limitations

The current rendering layer is Bootstrap-first.

The only supported theme is `bootstrap`.

Customization is currently handled through:

- runtime Bootstrap options;
- bundle configuration defaults;
- column `className`;
- custom column templates;
- Symfony template overrides.

There is no theme registry, Tailwind theme, CSS asset package or icon provider abstraction yet.

The current strategy and limitations are documented in `docs/theming.md`.

---

## 9. Action rendering layer

Actions are declared on `DatatableDefinition` and rendered by the Twig/renderer layer.

User-facing documentation is available in [`actions-and-cells.md`](actions-and-cells.md).

### Row action route parameter resolver

`RowActionRouteParameterResolver` resolves route parameters for row actions from row data.

It supports:

- direct row keys such as `id`;
- aliased row keys such as `e_id`;
- Doctrine-style dot notation such as `e.id`.

The resolver does not generate URLs. It only resolves route parameter values.

URL generation remains the responsibility of the renderer.

### Row actions

The renderer can render row actions declared on `DatatableDefinition`.

Row action rendering uses:

- `ActionDefinition`;
- `RowActionRouteParameterResolver`;
- Symfony `UrlGeneratorInterface`;
- Bootstrap-compatible markup.

### Global actions

Global actions are rendered in the datatable toolbar.

They are useful for operations such as:

- create;
- import;
- export;
- future batch actions.

### CSRF-aware action rendering

GET actions are rendered as links.

Non-GET actions are rendered as POST forms with a hidden `_method` field.

When a `CsrfTokenManagerInterface` is available, non-GET action forms include a `_token` hidden field.

This avoids rendering unsafe destructive links while keeping action rendering compatible with Symfony conventions.

### Action visibility extension point

The bundle exposes `ActionVisibilityCheckerInterface`.

The default implementation is `AllowAllActionVisibilityChecker`, which keeps current behavior and allows all actions.

The visibility checker receives:

- `ActionDefinition`;
- `ActionVisibilityContext`.

The context can contain:

- the current `DatatableDefinition`;
- optional row data for row actions;
- runtime options.

This extension point is independent from Symfony Security. Optional Symfony authorization integration is handled separately.

### Row action visibility

Row actions are filtered through `ActionVisibilityCheckerInterface` before URL generation.

The renderer builds an `ActionVisibilityContext` with:

- the current `DatatableDefinition`;
- the current provider row;
- runtime options.

Hidden row actions are not rendered and their URLs are not generated.

The default checker still allows all actions, preserving previous behavior unless the application replaces the service.

---

## 10. Ajax controller layer

The Ajax controller exposes bundle-owned generic endpoints used by the frontend controller.

The current implemented endpoint returns server-rendered fragments:

- body;
- pagination;
- summary;
- pagination metadata.

### Ajax fragments endpoint

The fragments endpoint connects the current server-side data pipeline:

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

The controller remains thin and delegates:

- request parsing;
- definition building;
- provider resolution;
- data loading;
- rendering.

The endpoint returns rendered `body` and `pagination` fragments plus metadata.

Current response shape:

```json
{
  "body": "<tr>...</tr>",
  "pagination": "<div>...</div>",
  "summary": "Showing 1 to 25 of 83 entries",
  "page": 1,
  "pageSize": 25,
  "totalItems": 83,
  "filteredItems": 83,
  "totalPages": 4
}
```

### Route loading strategy

The bundle exposes generic routes through `config/routes.php`.

Current route:

```text
zhortein_datatable_fragments
/_zhortein/datatable/{name}/fragments
```

Host applications should import these routes explicitly from the bundle.

Route names are prefixed with `zhortein_datatable_` to avoid collisions with application routes.

---

## 11. Stimulus controller layer

The frontend controller is a vanilla Stimulus controller located at:

```text
assets/controllers/datatable_controller.js
```

It must not depend on jQuery or DataTables.net.

### Stimulus and AssetMapper integration

The frontend controller is designed for Symfony UX Stimulus and AssetMapper.

The bundle ships a vanilla JavaScript controller under:

```text
assets/controllers/datatable_controller.js
```

Host applications can expose it through a local wrapper controller until an automatic registration or Flex recipe strategy is implemented.

The controller does not depend on jQuery, DataTables.net or a Node build pipeline.

### Responsibilities

The controller is responsible for:

- refreshing server-rendered HTML fragments through `fetch()`;
- updating body and pagination targets;
- updating summary target;
- managing loading state;
- managing safe error display;
- handling search debounce;
- handling pagination;
- preparing sort interactions.

It does not render business cells manually.

### Values exposed by the datatable shell

The datatable shell exposes:

- datatable name;
- fragments URL;
- current page;
- page size.

The controller sends these values as query parameters:

- `page`;
- `pageSize`;
- `search`;
- `sortField`;
- `sortDirection`.

### Loading and error states

The datatable shell renders accessible loading and error targets.

Loading state:

- uses `role="status"`;
- uses `aria-live="polite"`;
- includes a Bootstrap spinner;
- is toggled by the Stimulus controller.

Error state:

- uses a Bootstrap danger alert;
- uses `role="alert"`;
- uses `aria-live="polite"`;
- is cleared before refresh and after successful fragment application.

The Stimulus controller toggles `aria-busy` on the datatable root element during refresh.

---

## 12. Test and quality architecture

### Quality gates

The project must pass:

- PHPUnit;
- PHPStan at maximum level;
- PHP-CS-Fixer with Symfony-oriented rules;
- twigcs;
- GitHub Actions CI.

### Unit tests

Unit tests are preferred for isolated behavior:

- value objects;
- registries;
- request/result objects;
- renderers;
- resolvers;
- providers where possible.

### Functional tests

Functional tests are used when Symfony integration itself must be verified.

The suite includes a minimal Symfony kernel under:

```text
tests/Functional/Kernel
```

This kernel registers:

- FrameworkBundle;
- TwigBundle;
- DoctrineBundle;
- ZhorteinDatatableBundle;
- functional test fixtures.

### Doctrine functional test foundation

The functional test suite includes a minimal Doctrine ORM setup backed by in-memory SQLite.

It registers a test entity under:

```text
tests/Functional/Fixtures/Entity
```

Doctrine `SchemaTool` is used to create and drop schema during tests.

This allows Doctrine provider features to be tested without requiring an external database service.

---

## 13. Current limitations

### Doctrine limitations

The Doctrine provider currently supports only simple fields on the main alias `e`.

Not implemented yet:

- association traversal;
- custom joins;
- advanced filters;
- search builder;
- multi-column sorting;
- export support.

### Rendering limitations

The renderer supports typed cells and custom column templates, but not yet:

- full i18n message catalog integration;
- advanced icon abstraction;
- action visibility rules;
- permission voters;
- dropdown action groups;
- batch selected-row actions.

### Frontend limitations

The Stimulus controller is intentionally lightweight.

Not implemented yet:

- advanced sorting UI;
- column visibility;
- persisted user preferences;
- frontend test suite.

---

## Exports

### Export request and format objects

Server-side exports start with typed request and format objects.

Current export model:

- `ExportFormat`;
- `ExportMode`;
- `DatatableExportRequest`;
- `DatatableExportResult`.

Supported format:

```text
csv
```

Supported modes:

```text
current
full
```

`current` keeps pagination while `full` is intended to export the whole filtered dataset.

### Export writer contract

Server-side exports are abstracted behind `ExportWriterInterface`.

A writer declares whether it supports an `ExportFormat` and writes a `Response`.

`ExportWriterRegistry` resolves writers by name or by supported format.

Writers are registered as Symfony services tagged with:

```text
zhortein_datatable.export_writer
```

The tag must define a `name` attribute.

CSV is implemented in the next export step.

### CSV export writer

`CsvExportWriter` implements server-side CSV export without adding external dependencies.

It writes visible datatable columns to CSV:

- column labels are used as headers;
- hidden columns are not exported;
- row values are normalized safely;
- CSV escaping uses PHP built-ins.

The writer is registered as an export writer named `csv`.

### Datatable export endpoint

The bundle exposes a server-side export endpoint:

```text
zhortein_datatable_export
/_zhortein/datatable/{name}/export/{format}
```

Current supported format:

```text
csv
```

The endpoint resolves:

1. datatable definition;
2. datatable request;
3. data provider;
4. export writer.

It returns a downloadable response generated by the resolved export writer.

### Export modes

Exports support two modes:

- `current`;
- `full`.

Current mode keeps pagination and exports only the current view.

Full mode disables pagination while keeping filters, global search and sorting.

The export controller uses `DatatableRequest::withoutPagination()` to build the effective provider request for full exports.

### CSV export control

The toolbar can render a CSV export control.

The control exposes two export modes:

- current view;
- full dataset.

Generated links target the datatable export endpoint with:

```text
mode=current
mode=full
```

The control can be disabled at render time with:

```twig
{{ zhortein_datatable('users', {
    export: false
}) }}
```

### Export documentation

Server-side exports are documented in [`exports.md`](exports.md).

The documentation covers:

- export route;
- CSV format;
- current and full export modes;
- export request flow;
- CSV writer behavior;
- toolbar export controls;
- value normalization;
- current limitations.

---

## 15. Documentation map

Related documentation:

- [`end-to-end-flow.md`](end-to-end-flow.md)
- [`doctrine-provider.md`](doctrine-provider.md)
- [`actions-and-cells.md`](actions-and-cells.md)
- [`features.md`](features.md)
- [`roadmap.md`](roadmap.md)

Architecture decisions:

- [`decisions/0001-legacy-code-as-functional-reference-only.md`](decisions/0001-legacy-code-as-functional-reference-only.md)
- [`decisions/0002-initial-public-api.md`](decisions/0002-initial-public-api.md)
- [`decisions/0003-bootstrap-rendering-strategy.md`](decisions/0003-bootstrap-rendering-strategy.md)
- [`decisions/0004-vanilla-stimulus-interaction-model.md`](decisions/0004-vanilla-stimulus-interaction-model.md)
- [`decisions/0005-doctrine-orm-provider-architecture.md`](decisions/0005-doctrine-orm-provider-architecture.md)

### Table controls documentation

Table controls and frontend interactions are documented in [`table-controls.md`](table-controls.md).

The documentation covers:

- search behavior;
- page size selector;
- sortable headers;
- current sorting state;
- pagination;
- loading state;
- error state;
- accessibility notes;
- current limitations.
