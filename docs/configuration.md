# Configuration

Bundle configuration is not implemented yet.

This document describes the expected configuration direction.

## Expected root key

```yaml
zhortein_datatable:
    default_provider: doctrine
    default_theme: bootstrap
    default_page_size: 25
```

## Providers

Expected direction:

```yaml
zhortein_datatable:
    default_provider: doctrine
```

The first provider will target Doctrine ORM.

Future providers may support:

- arrays;
- APIs;
- Elasticsearch;
- custom application services.

## Theme

Expected direction:

```yaml
zhortein_datatable:
    default_theme: bootstrap
```

The first supported theme is Bootstrap.

Tailwind support is intentionally out of scope for the first release.

## Pagination

Expected direction:

```yaml
zhortein_datatable:
    default_page_size: 25
    allowed_page_sizes: [10, 25, 50, 100]
```

## Routes

The bundle may expose default generic routes later.

Applications should be able to wrap or customize routes when needed.

## Templates

Host applications should be able to override templates through standard Symfony bundle override mechanisms.

Expected override path:

```text
templates/bundles/ZhorteinDatatableBundle/bootstrap/...
```

## Assets

The bundle will provide a vanilla Stimulus controller later.

No jQuery or DataTables.net dependency is allowed.

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
zhortein_datatable.actions
zhortein_datatable.pagination.label
zhortein_datatable.pagination.previous
zhortein_datatable.pagination.next
zhortein_datatable.boolean.yes
zhortein_datatable.boolean.no
```

Host applications can override these translations through Symfony translation mechanisms.

## Datetime cell formatting

Datetime cells are formatted through `DateTimeFormatterInterface`.

The default implementation:

- uses the current Symfony request locale when available;
- uses `IntlDateFormatter` when the PHP Intl extension is installed;
- falls back to a deterministic PHP date format otherwise.

Applications with user-specific timezones or advanced localization needs can replace the formatter service.

