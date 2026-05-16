# UI/UX Rendering and Controls

This document describes the UI/UX customization options, table controls, and interaction model of `zhortein/datatable-bundle`.

The bundle is **Bootstrap-first** and uses a **Stimulus-powered** interaction model that updates server-rendered Ajax fragments.

## Status

Currently implemented:
-   **Interactions**: Global search, pagination, sortable headers, page size selector.
-   **UI Features**: Loading and error states, summary updates, Bootstrap table variants.
-   **Customization**: Action icons, display modes (inline, dropdown), boolean rendering modes.
-   **Layouts**: Default toolbar layout and Split layout (moving some controls below the table).

Not implemented yet:
-   Column visibility UI (coming soon).
-   Icon provider abstraction (currently CSS-class based).
-   Icon-only actions (accessibility first).
-   Frontend test suite for the Stimulus controller.

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
Actions can declare an optional icon CSS class. The bundle remains accessible by keeping labels visible alongside icons.

```php
$definition->addRowAction(
    name: 'edit',
    icon: 'bi bi-pencil',
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
- [Theming and Templates](theming.md)
- [Architecture](architecture/overview.md)
