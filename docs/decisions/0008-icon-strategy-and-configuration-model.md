# 0008 - Icon strategy and configuration model

## Status

Accepted

## Context

The `zhortein/datatable-bundle` already supports icons in some areas (like actions), but lacks a unified strategy.
As more interactive features are added (sorting, filtering, exports, bulk actions), the UI needs a consistent way to render and configure icons.

The goals are:
- providing a consistent visual language;
- keeping icon libraries optional;
- avoiding mandatory dependencies on specific icon sets (Bootstrap Icons, FontAwesome, etc.);
- maintaining accessibility;
- allowing easy configuration of icon classes.

## Decision

The bundle will adopt a **CSS-class based icon strategy**.

### Lightweight and Optional

- No mandatory icon library dependency will be introduced.
- Support for icon libraries like **Bootstrap Icons** or **FontAwesome** will be documented but optional.
- **Symfony UX Icons** integration is considered out of scope for the first implementation but may be added later as an optional provider.
- If no icon classes are configured, the bundle should fall back to text-only or native-like indicators where appropriate (especially for sorting).

### CSS-Class Based Implementation

The first implementation will rely on CSS classes. The bundle will provide a way to configure which CSS classes should be applied for specific icon keys.

### Visible Labels are Required

To maintain usability and accessibility, **icon-only actions are out of scope** for now.
All actions must have a visible label. Icons are decorative and must be hidden from screen readers.

### Accessibility Rules

- All rendered icons must include `aria-hidden="true"`.
- Meaningful information must never be conveyed by icons alone.
- Labels remain the primary way to identify actions and states.

## Icon Keys

The following initial icon keys are defined for the bundle:

| Key | Usage | Default (Bootstrap Icons suggestion) |
|-----|-------|---------------------------------------|
| `action_view` | View action icon | `bi bi-eye` |
| `action_edit` | Edit action icon | `bi bi-pencil` |
| `action_delete` | Delete action icon | `bi bi-trash` |
| `action_create` | Create action icon | `bi bi-plus-lg` |
| `bulk_actions` | Bulk actions dropdown/indicator | `bi bi-check2-all` |
| `boolean_true` | Boolean true state (icon mode) | `bi bi-check-lg text-success` |
| `boolean_false` | Boolean false state (icon mode) | `bi bi-x-lg text-danger` |
| `sort_neutral` | Column not sorted | `bi bi-arrow-down-up small text-muted` |
| `sort_asc` | Column sorted ascending | `bi bi-sort-up` |
| `sort_desc` | Column sorted descending | `bi bi-sort-down` |
| `filter` | Filter button/dropdown | `bi bi-filter` |
| `filter_active` | Indicator that a filter is active | `bi bi-funnel-fill` |
| `export` | Export button/dropdown | `bi bi-download` |
| `export_csv` | CSV export action | `bi bi-filetype-csv` |
| `export_xlsx` | XLSX export action | `bi bi-filetype-xlsx` |
| `confirmation_warning` | Warning icon in confirmation modals | `bi bi-exclamation-triangle` |

## Configuration Shape

The icon strategy will be configurable through the bundle configuration.

```yaml
zhortein_datatable:
    icons:
        enabled: true
        # Default icon set prefix (optional convenience)
        # prefix: 'bi bi-'
        mappings:
            action_view: 'bi bi-eye'
            action_edit: 'bi bi-pencil'
            action_delete: 'bi bi-trash'
            action_create: 'bi bi-plus-lg'
            bulk_actions: 'bi bi-check2-all'
            boolean_true: 'bi bi-check-lg text-success'
            boolean_false: 'bi bi-x-lg text-danger'
            sort_neutral: 'bi bi-arrow-down-up small text-muted'
            sort_asc: 'bi bi-sort-up'
            sort_desc: 'bi bi-sort-down'
            filter: 'bi bi-filter'
            filter_active: 'bi bi-funnel-fill'
            export: 'bi bi-download'
            export_csv: 'bi bi-filetype-csv'
            export_xlsx: 'bi bi-filetype-xlsx'
            confirmation_warning: 'bi bi-exclamation-triangle'
```

At the PHP level, these mappings will be available to the renderer to resolve icon classes from keys.

## Implementation Direction

1. Add `icons` section to the bundle configuration.
2. Introduce an `IconResolver` or similar internal service to map keys to CSS classes.
3. Update Twig templates to use the icon resolver.
4. Ensure icons are wrapped in a consistent way (e.g., `<i class="{{ icon_class }}" aria-hidden="true"></i>`).
5. Update `DatatableDefinition` to allow overriding icons per action if needed.

## Consequences

- Applications can easily switch between icon libraries (e.g., from Bootstrap Icons to FontAwesome) by changing the configuration.
- The bundle remains lightweight and does not force a specific asset dependency.
- Accessibility is preserved by requiring labels and hiding icons from screen readers.
- Consistent visual feedback for common datatable interactions.
