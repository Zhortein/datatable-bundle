# Filters

This document explains how to declare and use filters in `zhortein/datatable-bundle`.

The bundle distinguishes between **permanent filters** (backend-defined, non-removable) and **user-facing filters** (rendered in the UI, controlled by users).

## Status

Currently implemented:
-   **Types**: Text, Choice, Boolean, Date, Date Range, Number, Number Range.
-   **Layouts**: Toolbar (default) and Column Header Dropdowns.
-   **Features**: Active filter summary, clear filters action, Stimulus-powered refresh with debouncing.

Not implemented yet:
-   Advanced SearchBuilder-style expressions (only `AND` is supported).
-   Nested filter groups.
-   Persisted filter presets.
-   Custom filter widgets (Select2, datepickers).
-   Collection-valued association filters.

## Declaring filters

Filters are declared in your datatable class using the `addFilter()` method.

```php
use Zhortein\DatatableBundle\Enum\FilterType;

$definition->addFilter(
    name: 'email',
    field: 'e.email',
    label: 'Email',
    type: FilterType::Text,
    placeholder: 'Search an email',
);
```

### Options

| Option | Description |
|---|---|
| `name` | Public filter name used in request parameters (`filters[email]`). |
| `field` | Provider field targeted (e.g., `e.email` or `organization.name`). |
| `label` | Human-readable label rendered in the UI. |
| `type` | `FilterType` enum value. |
| `choices` | Array of choices for `Choice` filters. |
| `placeholder` | Placeholder text for input or empty option. |
| `required` | Adds HTML `required` attribute. |

## Filter Types

### Text
Applies `LOWER(field) LIKE :value` (case-insensitive) in Doctrine.

### Choice / Boolean
Choice renders a `<select>`. Boolean renders a `<select>` with Yes/No. 
Doctrine applies equality for scalars or `IN` for arrays.

### Date / Date Range
Date interprets a single date as a full-day range. Date range provides `from` and `to` inputs.
Expected format: `Y-m-d`.

### Number / Number Range
Applies equality or range comparisons. Non-numeric values are ignored.

## Filter Layouts

You can control where filters are rendered using the `filterLayout` option:

```twig
{{ zhortein_datatable('users', {
    filterLayout: 'toolbar'
}) }}
```

| Value | Behavior |
|---|---|
| `toolbar` | (Default) Render filters in a dedicated toolbar section. |
| `header` | Render filters as Bootstrap dropdowns in column headers. |
| `none` | Hide filter controls entirely. |

### Column Header Filters
When `filterLayout: 'header'` is used, filters are matched to columns by their `field` name. The column header will display a small button to open the filter dropdown.

## Stimulus and Interaction

The frontend controller (`datatable_controller.js`) handles filter changes:
1.  Resets page to 1.
2.  Debounces the refresh (300ms).
3.  Appends filter values to the Ajax fragments URL.

A **Clear Filters** button is automatically displayed when at least one filter is active.

## Security

The bundle only applies filters explicitly declared in the `DatatableDefinition`. This prevents users from injecting arbitrary DQL fields or expressions via the frontend.

## Related documentation

- [Doctrine provider](doctrine-provider.md)
- [UI/UX customization](ui-ux.md)
- [Architecture](architecture.md)
