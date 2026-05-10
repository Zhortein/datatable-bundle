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

## 14. Documentation map

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
