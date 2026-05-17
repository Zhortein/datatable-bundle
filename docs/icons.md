# Icon System and Visual Consistency

`zhortein/datatable-bundle` provides a unified, flexible, and library-agnostic icon system to ensure visual consistency across your datatables.

## Icon Strategy

The bundle adopts a **CSS-class based icon strategy**:

-   **Library Agnostic**: No specific icon library is required. You can use Bootstrap Icons, FontAwesome, Material Icons, or any other class-based library.
-   **Optional**: Icons are decorative and optional. If no icons are configured, the bundle falls back to text or native-like indicators.
-   **Accessible**: To ensure usability for everyone, icons are hidden from screen readers (`aria-hidden="true"`), and all actions MUST have a visible text label.

## Configuration

You can configure and override icon mappings globally in your bundle configuration:

```yaml
# config/packages/zhortein_datatable.yaml
zhortein_datatable:
    icons:
        # Override specific keys
        action_view: 'bi bi-search'
        action_edit: 'bi bi-pencil-square'
        
        # Add custom keys for your own actions
        action_approve: 'bi bi-check-circle'
```

## Default Icon Keys

The bundle uses the following default keys within its internal components. The default values are based on **Bootstrap Icons**.

| Key | Usage | Default Value |
|---|---|---|
| `action_view` | Default for "view" or "show" actions | `bi bi-eye` |
| `action_edit` | Default for "edit" actions | `bi bi-pencil` |
| `action_delete` | Default for "delete" or "remove" actions | `bi bi-trash` |
| `action_create` | Default for "create" actions | `bi bi-plus-lg` |
| `bulk_actions` | Icon for the bulk actions dropdown | `bi bi-collection` |
| `boolean_true` | Icon for boolean "true" state | `bi bi-check-lg` |
| `boolean_false` | Icon for boolean "false" state | `bi bi-x-lg` |
| `sort_neutral` | Column not sorted | `bi bi-arrow-down-up` |
| `sort_asc` | Column sorted ascending | `bi bi-arrow-up` |
| `sort_desc` | Column sorted descending | `bi bi-arrow-down` |
| `filter` | Filter button/dropdown | `bi bi-funnel` |
| `filter_active` | Indicator for active filters | `bi bi-funnel-fill` |
| `export` | Export button/dropdown | `bi bi-download` |
| `export_csv` | CSV export action | `bi bi-filetype-csv` |
| `export_xlsx` | XLSX export action | `bi bi-filetype-xlsx` |

## Overriding Icons

### Global Overrides

As shown in the [Configuration](#configuration) section, use the `icons` key in your YAML configuration to override any default key or define new ones.

### Explicit Action Icons

You can explicitly set an icon for a specific action in your datatable definition. This takes precedence over any global mapping.

```php
$definition->addRowAction(
    name: 'custom',
    label: 'Custom Action',
    route: 'app_custom',
    icon: 'bi bi-star-fill' // Explicit icon
);
```

### Automatic Action Resolution

If no `icon` is provided for an action, the bundle attempts to resolve it automatically:
1.  It checks for an exact match for the action name in the icon mappings (prefixed with `action_`).
2.  For common names like `view`, `edit`, `delete`, it uses built-in fallbacks.
3.  If no mapping is found, it falls back to no icon.

## Examples

### Using Bootstrap Icons (Default)

Ensure you include the Bootstrap Icons CSS in your layout:

```html
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
```

### Using FontAwesome

If you prefer FontAwesome, update your configuration:

```yaml
zhortein_datatable:
    icons:
        action_view: 'fas fa-eye'
        action_edit: 'fas fa-edit'
        action_delete: 'fas fa-trash'
        action_create: 'fas fa-plus'
        sort_neutral: 'fas fa-sort'
        sort_asc: 'fas fa-sort-up'
        sort_desc: 'fas fa-sort-down'
        # ... and so on
```

## Accessibility Rules

-   **Labels are Mandatory**: Meaningful information must never be conveyed by icons alone.
-   **Hidden from AT**: All icons rendered by the bundle include `aria-hidden="true"`.
-   **Decorative Nature**: Icons should be considered purely decorative; the user experience should be complete even if icons fail to load.

## Limitations

-   **Icon-only actions**: Not supported by design to maintain a high accessibility baseline.
-   **SVG / Symfony UX Icons**: Currently, the system is optimized for CSS-class based libraries. Native SVG or Symfony UX Icons integration is not yet implemented as a core provider.
-   **Icon Libraries**: The bundle does not ship with any icon fonts or CSS. You must include your preferred icon library in your application's assets.

## Related documentation

- [UI/UX Rendering](ui-ux.md)
- [Actions and Security](actions.md)
- [Theming and Templates](theming.md)
- [Configuration Reference](configuration.md)
