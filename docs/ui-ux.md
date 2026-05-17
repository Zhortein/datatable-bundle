# UI/UX Rendering and Controls

This document describes the UI/UX customization options, table controls, and interaction model of `zhortein/datatable-bundle`.

The bundle is **Bootstrap-first** and uses a **Stimulus-powered** interaction model that updates server-rendered Ajax fragments.

## Status

Currently implemented:
-   **Interactions**: Global search, pagination, sortable headers, page size selector.
-   **Row Selection**: Checkbox-based selection and bulk action toolbar.
-   **UI Features**: Loading and error states, summary updates, Bootstrap table variants.
-   **Column Visibility**: User-controlled column visibility with persistent state.
-   **Icons**: Consistent icon system for actions, filters, and exports via `IconResolver`.
-   **Customization**: Action icons, display modes (inline, dropdown), boolean rendering modes.
-   **Layouts**: Default toolbar layout and Split layout (moving some controls below the table).
-   **Testing**: Automated frontend test suite for the Stimulus controller.

Not implemented yet:
-   Icon-only actions (accessibility first).
-   Persisted filter presets.

## Table Controls

### Global Search
Search can be enabled at runtime. It features a 300ms debounce and resets the current page to 1.

```twig
{{ zhortein_datatable('users', { search: true }) }}
```

### Pagination and Page Size
Pagination is rendered server-side. The page size selector is enabled by default.

```twig
{{ zhortein_datatable('users', {
    pageSize: 25,
    allowedPageSizes: [10, 25, 50, 100],
    pageSizeSelector: true
}) }}
```

### Sortable Headers
Clicking a header toggles sorting between `asc`, `desc`, and back to `asc`. Neutral and active sort indicators (↕, ↑, ↓) are rendered without requiring an icon library.

## Rendering Customization

### Action Icons
Actions can declare an optional icon CSS class. If no explicit icon is provided, the bundle resolves a default icon from the `IconResolver` based on the action name.

Common action names like `view`, `edit`, `delete`, and `create` have built-in defaults. Bulk actions also have a default icon fallback.

The bundle remains accessible by keeping labels visible alongside icons.

```php
$definition->addRowAction(
    name: 'edit',
    // icon: 'bi bi-pencil', // Optional, resolved automatically for 'edit'
    label: 'Edit',
    // ...
);
```

### Action Display Modes
Row actions can be grouped differently:
-   `inline` (Default): Button group.
-   `dropdown`: Bootstrap dropdown menu (requires Bootstrap JS).
-   `list`: Vertical list.

### Boolean Display Modes
Boolean cells support several styles:
-   `badge` (Default): Yes/No badges.
-   `icon`: Check/Cross characters.
-   `switch`: Disabled Bootstrap switch.
-   `text`: Plain translated text.

### Layout Options
Use `controlsLayout: 'split'` to move column visibility, page size selector, and summary below the table, reducing toolbar clutter.

## CSS Customization

Append CSS classes to the main elements without overriding templates:

```twig
{{ zhortein_datatable('users', {
    rootClass: 'my-datatable',
    tableWrapperClass: 'my-wrapper',
    tableClass: 'table-striped table-hover'
}) }}
```

## Accessibility

The bundle follows a strong accessibility baseline:
-   `aria-sort` on active headers.
-   `aria-busy` and live regions for loading/error states.
-   Visually hidden labels for decorative icons and sort state.
-   Explicit accessible labels for all form controls.

## Related documentation

- [Actions and Security](actions.md)
- [Icon System](icons.md)
- [Bulk Actions and Selection](bulk-actions.md)
- [Theming and Templates](theming.md)
- [Architecture](architecture/overview.md)
