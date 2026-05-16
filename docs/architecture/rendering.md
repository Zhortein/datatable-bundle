# Rendering Architecture

The rendering layer is Twig-first and Bootstrap-first. It follows a server-side fragment rendering model.

## Rendering Layer

The main rendering service is `DatatableRenderer`. It orchestrates the rendering of the datatable shell, rows, pagination, and various table controls.

### Twig Extension

The `zhortein_datatable` Twig function delegates definition building to `DatatableDefinitionFactory` and rendering to `DatatableRenderer`.

## Component Structure

### Datatable Shell

The shell (`datatable.html.twig`) renders the high-level container, toolbar (search, filters, actions, exports), table structure, and pagination.

### Row and Cell Rendering

Rows are normalized against visible columns. Cells are rendered through type-specific Twig templates (string, numeric, boolean, datetime, etc.). Custom templates can be defined per-column in the datatable definition.

### Actions Rendering

- **GET Actions**: Rendered as standard links.
- **Non-GET Actions**: Rendered as POST forms with hidden `_method` and CSRF `_token` fields.
- **Visibility**: Actions are filtered through `ActionVisibilityCheckerInterface`.

### Pagination

Rendered as Bootstrap pagination with Stimulus-compatible attributes for Ajax updates.

## Bootstrap Theme

The bundle is **Bootstrap 5-first**. It supports runtime display variants (striped, hover, bordered, etc.) and global configuration for these defaults.

## Accessibility

The renderer provides:

- `aria-sort` on active headers;
- `aria-busy` and live regions for loading/error states;
- Visually hidden labels for icons and sort state;
- Explicit accessible labels for all form controls.

## Related documentation

- [Theming and Templates](../theming.md)
- [UI/UX Rendering](../ui-ux.md)
