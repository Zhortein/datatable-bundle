# Theming and Templates

`zhortein/datatable-bundle` uses a Twig-first rendering strategy, making it easy to customize and override the UI. The bundle is currently **Bootstrap-first**.

## Status

Currently implemented:
-   **Theme**: Bootstrap 5.
-   **Overrides**: Standard Symfony bundle template overrides.
-   **Cells**: Typed cell templates (string, numeric, boolean, datetime, array, enum).
-   **Context**: Stable `CellContext` with normalized rows, optional provider sources, identifiers, definitions and explicit application context.
-   **Computed values**: Named PHP resolvers shared by custom cells and exports.
-   **Variants**: Runtime Bootstrap table options (striped, hover, bordered, etc.).
-   **Boolean Display Modes**: Configurable rendering for boolean columns (`badge`, `icon`, `switch`, `text`).
-   **Boolean Negation**: Per-column inversion of rendered boolean values.
-   **Enum Presentation**: Localized labels with optional Bootstrap badges, custom colors and icons.
-   **Icons**: Extensible `IconResolver` for common UI elements (sort, filter, export, actions).

Not implemented yet:
-   Tailwind or other built-in themes.

## Template Override Strategy

To customize the look and feel, use Symfony's bundle override mechanism. Place your custom templates in:

`templates/bundles/ZhorteinDatatableBundle/bootstrap/...`

### Recommended Path for Customization
1.  **Custom Column Template**: Use `template: '...'` in `addColumn()` for specific column needs.
2.  **Cell Templates**: Override `cell/*.html.twig` to change how specific types are rendered globally.
3.  **Partial Templates**: Override `_toolbar.html.twig`, `_header.html.twig`, etc., for layout changes.
4.  **Full Shell**: Override `datatable.html.twig` only if you need to restructure the entire component.

## Typed Cell Rendering

The bundle automatically selects a template based on the column type.

| Type | Template | Default Alignment |
|---|---|---|
| `string` | `cell/string.html.twig` | `text-start` |
| `numeric` | `cell/numeric.html.twig` | `text-end` |
| `boolean` | `cell/boolean.html.twig` | `text-center` |
| `datetime` | `cell/datetime.html.twig` | `text-start` |
| `array` | `cell/array.html.twig` | `text-start` |
| `enum` | `cell/enum.html.twig` | `text-center` |
| `default` | `cell/default.html.twig` | `text-start` |

### Custom Column Template Example
```php
$definition->addColumn(
    name: 'e.status',
    label: 'Status',
    template: 'admin/datatable/cell/status.html.twig'
);
```

In your Twig template:
```twig
{# templates/admin/datatable/cell/status.html.twig #}
<span class="badge text-bg-info">{{ value }}</span>
```

## Template Context Reference

### Global Context (`datatable.html.twig`)
Available variables: `definition`, `visibleColumns`, `rowActions`, `globalActions`, `htmlId`, `options`.

### Cell Context (`_cell.html.twig` and `cell/*.html.twig`)
-   `cell`: Canonical server-side `CellContext` DTO.
-   `column`: The `ColumnDefinition` object.
-   `column_label`: Final column label, translated for the current locale when the definition has a translation domain.
-   `translation_domain`: Definition translation domain, or `null` when declarative strings are literal.
-   `value`: The provider or computed value, inverted when the boolean column enables `negate`.
-   `row`: Normalized provider row.
-   `source`: Optional provider source array/object, or `null`.
-   `row_identifier`: Normalized row identifier, or `null`.
-   `datatable`: Current `DatatableDefinition`.
-   `datatable_context`: Explicit server-side `DatatableContext`.
-   `boolean_display_mode`, `boolean_true_icon`, `boolean_false_icon`: Resolved typed-cell rendering options.
-   `enum_presentation`: Resolved `EnumPresentation` metadata for enum cells, or `null`.

The bundle never serializes these objects into browser attributes or JSON.
See [Cell Context and Computed Values](cell-context.md) for provider
capabilities, computed columns, export behavior and security guidance.

### Action Context (`_action.html.twig`)
-   `action`: Array containing `name`, `label`, `confirmationMessage`, `translationDomain`, `url`, `httpMethod`, `csrfToken`, `className`, `attributes` and `selectedRowsParameterName`.

`label` and `confirmationMessage` remain their declared values so an override
can inspect them. Resolve either value with the same semantics as the built-in
templates:

```twig
{% set label = zhortein_datatable_translate(
    action.label,
    action.translationDomain,
    action.name
) %}
```

The helper translates only when its domain argument is not `null`. Its optional
fourth argument accepts translation parameters. Custom cell templates should
prefer the already resolved `column_label`.

## Bootstrap Configuration

You can set global Bootstrap defaults in your configuration:

```yaml
zhortein_datatable:
    bootstrap:
        table:
            striped: true
            hover: true
            responsive: true
```

Or override them at runtime:

```twig
{{ zhortein_datatable('users', {
    tableSmall: true,
    tableBordered: true
}) }}
```

## Related documentation

- [Icon System](icons.md)
- [UI/UX Rendering](ui-ux.md)
- [Actions and Security](actions.md)
- [Cell Context and Computed Values](cell-context.md)
- [Enum Presentation](enum-presentation.md)
- [Architecture](architecture/overview.md)
