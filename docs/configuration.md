# Configuration

This document describes bundle configuration.

The bundle configuration root key is:

```yaml
zhortein_datatable:
```

## Full default configuration

```yaml
zhortein_datatable:
    default_provider: doctrine
    default_theme: bootstrap
    default_page_size: 25
    max_page_size: 500
    search_enabled: false
    search_builder_enabled: false
    icons: { }
    bootstrap:
        table:
            striped: true
            hover: true
            bordered: false
            borderless: false
            small: false
            responsive: true
    export:
        csv:
            delimiter: ','
            enclosure: '"'
            escape: "\\"
            bom: false
```

This configuration is intentionally small.

It focuses on global defaults that can be overridden at runtime.

## `default_provider`

Type:

```text
string
```

Allowed values:

```text
array
doctrine
```

Default:

```yaml
default_provider: doctrine
```

The default provider is preferred when no provider is declared explicitly and it supports the datatable definition. If it does not support the definition, the registry falls back to another compatible provider.

An explicit provider declared with `#[AsDatatable(provider: '...')]` always takes precedence.

Current providers:

- `array`;
- `doctrine`.

The array provider is mostly intended for tests and demos.

Doctrine is the first production-oriented provider.

## `default_theme`

Type:

```text
string
```

Allowed values:

```text
bootstrap
```

Default:

```yaml
default_theme: bootstrap
```

The bundle currently supports Bootstrap-first rendering only.

Tailwind support is intentionally out of scope for the first releases.

## `default_page_size`

Type:

```text
integer
```

Default:

```yaml
default_page_size: 25
```

Minimum:

```text
1
```

This value is injected into:

- `DatatableRenderer`;
- `DatatableRequestFactory`.

It is used when no runtime page size is provided.

Runtime options can override it:

```twig
{{ zhortein_datatable('users', {
    pageSize: 50
}) }}
```

Ajax requests can also pass:

```text
pageSize=50
```

## `max_page_size`

Type:

```text
integer
```

Default:

```yaml
max_page_size: 500
```

Minimum:

```text
1
```

This value protects request parsing from excessive page sizes.

If the frontend requests a page size larger than the configured maximum, `DatatableRequestFactory` caps it.

Example:

```yaml
zhortein_datatable:
    max_page_size: 100
```

A request with:

```text
pageSize=1000
```

will be normalized to:

```text
100
```

## `search_enabled`

Type:

```text
boolean
```

Default:

```yaml
search_enabled: false
```

When enabled, datatables render the search input by default.

Example:

```yaml
zhortein_datatable:
    search_enabled: true
```

A runtime option can still override it:

```twig
{{ zhortein_datatable('users', {
    search: false
}) }}
```

## `search_builder_enabled`

Type:

```text
boolean
```

Default:

```yaml
search_builder_enabled: false
```

When enabled, datatables render the Search Builder controls by default. A runtime option can still override it:

```twig
{{ zhortein_datatable('users', {
    searchBuilder: false
}) }}
```

## `icons`

Type: `array<string, string>`

The bundle uses a lightweight icon resolver to map internal icon keys to CSS classes. By default, it uses [Bootstrap Icons](https://icons.getbootstrap.com/) class names.

See [Icon System documentation](icons.md) for the full list of available keys and detailed strategy.

Example:

```yaml
zhortein_datatable:
    icons:
        action_view: "fas fa-eye"
        action_edit: "fas fa-edit"
```

## `bootstrap.table`

These booleans configure the default Bootstrap table variants:

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

Each value can still be overridden through the corresponding runtime rendering option.

## `export.csv`

The CSV writer uses the following defaults:

```yaml
zhortein_datatable:
    export:
        csv:
            delimiter: ','
            enclosure: '"'
            escape: "\\"
            bom: false
```

For French/European spreadsheet workflows, a semicolon delimiter is often convenient.

## Runtime options

The Twig function accepts runtime options:

```twig
{{ zhortein_datatable('users', {
    search: true,
    pageSize: 25,
    fragmentsUrl: path('custom_fragments_route')
}) }}
```

Current options:

| Option | Type | Description |
|---|---:|---|
| `search` | boolean | Displays or hides the search input |
| `pageSize` | integer | Defines the initial page size |
| `fragmentsUrl` | string | Overrides the default Ajax fragments URL |
| `booleanDisplayMode` | string | Defines how boolean values are rendered (`badge`, `icon`, `switch`, `text`) |

## `booleanDisplayMode`

Type: `string`

Default: `badge`

Available modes:
- `badge`: Renders a Bootstrap badge (Success/Secondary).
- `icon`: Renders an icon from the `IconResolver` (defaults to `bi bi-check-lg` and `bi bi-x-lg`).
- `switch`: Renders a Bootstrap switch (checkbox). **Note**: This mode is display-only; inline editing is not supported.
- `text`: Renders translated "Yes" or "No" text.

Example:

```twig
{{ zhortein_datatable('users', {
    booleanDisplayMode: 'switch'
}) }}
```

Runtime options take precedence over global configuration.

## Boolean column negation

Boolean columns can invert their rendered value with the `negate` argument:

```php
$definition->addColumn(
    name: 'e.disabled',
    label: 'Enabled',
    type: 'boolean',
    negate: true,
);
```

This is useful when the stored field expresses the opposite of the value that should be presented. With the example above, `disabled = false` is rendered as `Yes`.

Negation is disabled by default and applies only to columns whose resolved type is `boolean`. Doctrine type detection is supported, so the explicit `type` argument can be omitted for mapped boolean fields.

The rendered values follow these rules:

- `true` becomes `false`;
- `false` becomes `true`;
- `null` remains `null`.

The transformed value is also passed to a custom column template. Negation is a rendering concern: it does not mutate provider data or change filtering, sorting, action parameters, or exported values.

## Routes

Bundle routes are defined in:

```text
config/routes.php
```

Current route:

```text
zhortein_datatable_fragments
/_zhortein/datatable/{name}/fragments
```

Import the routes in the host application.

PHP example:

```php
<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->import('@ZhorteinDatatableBundle/config/routes.php');
};
```

YAML example:

```yaml
zhortein_datatable:
    resource: '@ZhorteinDatatableBundle/config/routes.php'
```

More details are available in [`routes.md`](routes.md).

## Translations

Built-in messages use the `zhortein_datatable` translation domain.

Translation files are provided for:

- English;
- French.

Current keys include:

```text
zhortein_datatable.search.label
zhortein_datatable.search.placeholder
zhortein_datatable.loading
zhortein_datatable.empty
zhortein_datatable.actions.row
zhortein_datatable.actions.more
zhortein_datatable.pagination.label
zhortein_datatable.pagination.previous
zhortein_datatable.pagination.next
zhortein_datatable.boolean.yes
zhortein_datatable.boolean.no
```

Host applications can override these translations through Symfony translation mechanisms.

### Translating declarative labels

Call `setTranslationDomain()` when column, filter, Search Builder and action
texts are translation keys:

```php
$definition
    ->setTranslationDomain('admin')
    ->addColumn('e.email', label: 'users.columns.email')
    ->addFilter(
        name: 'status',
        field: 'e.status',
        label: 'users.filters.status',
        type: FilterType::Choice,
        choices: [
            'users.status.enabled' => 'enabled',
            'users.status.disabled' => 'disabled',
        ],
        placeholder: 'users.filters.all_statuses',
    )
    ->addAdvancedFilterField(
        name: 'status',
        field: 'e.status',
        label: 'users.filters.status',
        type: FilterType::Choice,
        choices: [
            'users.status.enabled' => 'enabled',
            'users.status.disabled' => 'disabled',
        ],
    )
    ->addRowAction(
        name: 'view',
        route: 'app_user_show',
        label: 'users.actions.view',
        confirmationMessage: 'users.confirmations.open',
        routeParameters: ['id' => 'e.id'],
    )
;
```

The definition domain is applied at render time to:

- column labels and the sort/filter accessibility labels derived from them;
- simple-filter labels, placeholders and choice labels;
- Search Builder field and choice labels;
- row, global and bulk action labels;
- action confirmation messages;
- column visibility labels.

The current Symfony translator locale is used for both the initial table shell
and Ajax fragments. Built-in controls remain in the separate
`zhortein_datatable` domain.

When `translationDomain` is `null` (the default), all application-provided
strings are treated as final literal text. Fallback names used when a label is
omitted are also literal and are never looked up as translation keys. This
allows an application to opt out cleanly and avoids translating final text a
second time.

Applications that translated labels inside their datatable service can migrate
by:

1. keeping or adding `setTranslationDomain('your_domain')`;
2. replacing injected-translator calls with their original translation keys;
3. removing `TranslatorInterface` from the datatable service;
4. checking both the initial page and an Ajax refresh in each supported locale.

Do not mix pre-translated strings and translation keys in a definition that has
a domain. Keep the domain `null` until that definition has been migrated.

Template overrides can resolve the same contract with
`zhortein_datatable_translate(message, translation_domain, fallback,
parameters)`. See the [template context reference](theming.md#template-context-reference).

## Datetime cell formatting

Datetime cells are formatted through `DateTimeFormatterInterface`.

The default implementation:

- uses the current Symfony request locale when available;
- uses `IntlDateFormatter` when the PHP Intl extension is installed;
- falls back to a deterministic PHP date format otherwise.

Applications with user-specific timezones or advanced localization needs can replace the formatter service.

Example service override direction:

```yaml
services:
    App\Datatable\UserAwareDateTimeFormatter:
        decorates: Zhortein\DatatableBundle\DateTime\DateTimeFormatterInterface
```

Exact override strategy may evolve as the formatter extension point stabilizes.

## Providers

Providers are Symfony services tagged with:

```text
zhortein_datatable.data_provider
```

The tag requires a `name` attribute.

Example:

```php
$services
    ->set(ArrayDataProvider::class)
    ->tag('zhortein_datatable.data_provider', [
        'name' => ArrayDataProvider::PROVIDER_NAME,
    ])
;
```

The provider registry receives these tagged services and resolves providers by name or support check.

## Doctrine provider

Doctrine-specific services are registered conditionally when Doctrine is available.

Current Doctrine services include:

- `DoctrineFieldTypeGuesser`;
- `DoctrineDatatableDefinitionEnricher`;
- `DoctrineOrmDataProvider`.

Doctrine documentation is available in [`doctrine-provider.md`](doctrine-provider.md).

## Theme templates

The default theme is:

```text
bootstrap
```

Templates live under:

```text
templates/bootstrap
```

Host applications can override bundle templates through Symfony bundle template override mechanisms.

Expected override path:

```text
templates/bundles/ZhorteinDatatableBundle/bootstrap/...
```

Custom column templates can also be defined per column:

```php
$definition->addColumn(
    name: 'e.status',
    template: 'admin/datatable/cell/status.html.twig',
);
```
## Bootstrap rendering defaults

Bootstrap table display variants can be configured globally:

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

Runtime options override configuration:

```twig
{{ zhortein_datatable('users', {
    tableBordered: true,
    tableSmall: true
}) }}
```

Current defaults preserve the standard rendering:

```text
table table-striped table-hover align-middle mb-0
```

with a responsive wrapper enabled by default.

## Theming

The bundle currently supports one maintained theme:

```yaml
zhortein_datatable:
    default_theme: bootstrap
```

Bootstrap table variants can be configured globally:

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

Runtime Twig options can override these defaults.

More details are available in [`theming.md`](theming.md).

## Configuration validation

Invalid configuration values are rejected during Symfony container configuration.

Examples of invalid values:

```yaml
zhortein_datatable:
    default_provider: invalid
```

```yaml
zhortein_datatable:
    default_theme: tailwind
```

```yaml
zhortein_datatable:
    default_page_size: 0
```

```yaml
zhortein_datatable:
    max_page_size: 0
```

## XLSX export configuration

XLSX export is enabled at rendering level through `exportFormats`.

```twig
{{ zhortein_datatable('users', {
    exportFormats: ['csv', 'xlsx']
}) }}
```

Custom export URLs can be provided per format:

```twig
{{ zhortein_datatable('users', {
    exportUrls: {
        csv: path('custom_users_csv_export'),
        xlsx: path('custom_users_xlsx_export')
    }
}) }}
```

The XLSX writer requires the optional OpenSpout dependency.

## Current limitations

The configuration surface is intentionally small.

Not configurable yet:

- route prefix;
- route names;
- per-provider defaults;
- per-theme template paths;
- date/time styles;
- timezone strategy;
- action defaults;
- user preferences;
- column visibility persistence;
- export options.
