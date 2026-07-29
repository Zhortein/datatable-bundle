# Rendering Architecture

The rendering layer is Twig-first and follows a server-side fragment rendering
model. Bootstrap is the default maintained presentation adapter.

## Rendering Layer

The main rendering service is `DatatableRenderer`. It orchestrates the rendering of the datatable shell, rows, pagination, and various table controls.

`ThemeRegistry` resolves an autoconfigured `ThemeInterface`. Its immutable
metadata supplies the Twig prefix, capabilities and asset requirements.

### Twig Extension

The `zhortein_datatable` Twig function delegates definition building to `DatatableDefinitionFactory` and rendering to `DatatableRenderer`.

## Component Structure

### Datatable Shell

The shell (`datatable.html.twig`) renders the high-level container, toolbar (search, filters, actions, exports), table structure, and pagination.

### Row and Cell Rendering

Rows are normalized against visible columns. `CellContextFactory` resolves
provider values and optional named computed values into one server-side
`CellContext`. Cells are rendered through type-specific Twig templates
(string, numeric, boolean, datetime, etc.). Custom templates can be defined
per-column in the datatable definition.

The final HTML is returned in fragment JSON. Rows, provider sources,
definitions and application context are never serialized as JSON fields or
HTML data attributes.

### Actions Rendering

- **GET Actions**: Rendered as standard links.
- **Non-GET Actions**: Rendered as POST forms with hidden `_method` and CSRF `_token` fields.
- **Visibility**: Actions are filtered through `ActionVisibilityCheckerInterface`.

### Pagination

Rendered by the selected theme with Stimulus-compatible attributes for Ajax updates.

## Bootstrap Theme

The bundle is **Bootstrap 5-first**. It supports runtime display variants (striped, hover, bordered, etc.) and global configuration for these defaults.

Bootstrap-specific classes and nested partials remain inside `BootstrapTheme`
and `templates/bootstrap`. The renderer and Stimulus controller do not select
Bootstrap presentation classes.

## Accessibility

The renderer provides:

- `aria-sort` on active headers;
- `aria-busy` and live regions for loading/error states;
- Visually hidden labels for icons and sort state;
- Explicit accessible labels for all form controls.

## Related documentation

- [Theming and Templates](../theming.md)
- [Theme extension contract](../theme-contract.md)
- [UI/UX Rendering](../ui-ux.md)
- [Cell Context and Computed Values](../cell-context.md)
