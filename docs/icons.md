# Icon System and Visual Consistency

`zhortein/datatable-bundle` provides a unified, flexible, and library-agnostic icon system to ensure visual consistency across your datatables.

## Icon strategy

Icon resolution and icon rendering are deliberately separate:

- `IconResolverInterface` maps a semantic key such as `action_edit` to a
  provider-specific string;
- `IconRendererInterface` turns that string into safe server-rendered HTML;
- the dependency-free CSS renderer is enabled by default;
- the optional Symfony UX Icons adapter renders SVG without coupling the public
  contract to Symfony UX Icons.

Icons are decorative by default and receive `aria-hidden="true"`. A custom
template can pass a label to `zhortein_datatable_icon()` for a meaningful icon;
the renderer then emits `role="img"` and an escaped `aria-label`.

## Configuration

You can configure and override icon mappings globally in your bundle configuration:

```yaml
# config/packages/zhortein_datatable.yaml
zhortein_datatable:
    icon_provider: css
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
| `search_builder` | Search Builder toggle | `bi bi-sliders` |
| `search_builder_add_condition` | Add a condition | `bi bi-plus-lg` |
| `search_builder_add_group` | Add a subgroup | `bi bi-folder-plus` |
| `search_builder_remove` | Remove a condition or subgroup | `bi bi-trash` |
| `column_visibility` | Column visibility menu | `bi bi-layout-three-columns` |
| `hierarchy_expand` | Expand a child datatable | `bi bi-chevron-right` |
| `hierarchy_collapse` | Collapse a child datatable | `bi bi-chevron-down` |
| `pagination_previous` | Previous page | `bi bi-chevron-left` |
| `pagination_next` | Next page | `bi bi-chevron-right` |

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

### Using Bootstrap Icons (default)

Ensure you include the Bootstrap Icons CSS in your layout, if not included via AssetMapper:

```html
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
```

If you want to use AssetMapper (preferred):
```bash
php bin/console importmap:require bootstrap-icons/font/bootstrap-icons.min.css 
```

Then, import it in your app.js (or any scoped js depending on your application architecture):
```js
import 'bootstrap-icons/font/bootstrap-icons.min.css';
```

### Using Font Awesome

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

### Using Symfony UX Icons

Install the optional package:

```bash
composer require symfony/ux-icons
```

Then select the adapter and map the semantic keys to UX Icons names:

```yaml
zhortein_datatable:
    icon_provider: ux_icons
    icons:
        action_view: 'bi:eye'
        action_edit: 'bi:pencil'
        action_delete: 'bi:trash'
        action_create: 'bi:plus-lg'
        sort_neutral: 'bi:arrow-down-up'
        sort_asc: 'bi:arrow-up'
        sort_desc: 'bi:arrow-down'
        filter: 'bi:funnel'
        filter_active: 'bi:funnel-fill'
        export: 'bi:download'
        export_csv: 'bi:filetype-csv'
        export_xlsx: 'bi:filetype-xlsx'
```

The adapter automatically converts the bundle's legacy `bi bi-*` defaults to
their `bi:*` UX Icons equivalents. Therefore `icon_provider: ux_icons` works
without overriding every default; explicit mappings are useful when choosing a
different collection or icon.

The adapter is discovered only when Symfony UX Icons is installed. If it is
unavailable or throws while resolving an icon, rendering falls back to the
dependency-free CSS renderer. This keeps controls and labels usable during a
partial installation or deployment. `bi:*` names are converted back to
`bi bi-*` during that fallback so applications already loading Bootstrap Icons
retain their visuals.

### Custom renderer

Applications can replace the renderer without changing datatable definitions:

```yaml
services:
    App\Datatable\IconRenderer: ~

    Zhortein\DatatableBundle\Contract\IconRendererInterface:
        alias: App\Datatable\IconRenderer
```

Custom Twig templates should render a resolved value with:

```twig
{{ zhortein_datatable_icon(icon, {class: 'me-1'}) }}
```

or resolve a bundle key and render it in one operation:

```twig
{{ zhortein_datatable_icon_key('pagination_previous') }}
```

Both functions are HTML-safe because the configured renderer owns the complete
server-side markup. Custom renderers are trusted services and must escape
dynamic content before returning it. Built-in renderers accept a bounded
attribute allowlist (`class`, identifiers, dimensions, common SVG presentation
attributes, `aria-*` and `data-*`) and discard event-handler attributes.

## Accessibility rules

- **Labels are mandatory**: meaningful information is never conveyed by an
  icon alone. Action labels stay visible; icon-only controls have translated
  `aria-label` values.
- **Decorative by default**: bundle icons include `aria-hidden="true"`.
- **Meaningful custom icons**: pass a non-empty `label` argument to emit
  `role="img"` and `aria-label`.
- **Locale-independent markup**: EN/FR translations belong to surrounding
  labels and controls, so switching locale does not change icon identifiers.

## Compatibility and fallbacks

- Existing icon strings, action definitions, global mappings and template
  overrides remain valid.
- The bundle does not require or ship an icon font.
- Symfony UX Icons is optional and is not referenced by the public contract.
- Missing semantic keys render no icon; sorting retains native arrow fallbacks.
- Icon-only actions remain unsupported to preserve the accessibility baseline.

## Related documentation

- [UI/UX Rendering](ui-ux.md)
- [Actions and Security](actions.md)
- [Theming and Templates](theming.md)
- [Configuration Reference](configuration.md)
