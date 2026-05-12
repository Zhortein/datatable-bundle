# UI/UX rendering customization

This document describes the current UI/UX customization options for `zhortein/datatable-bundle`.

The bundle remains Bootstrap-first and Twig-first.

The goal is to provide a professional business datatable UI while keeping customization predictable for host applications.

## Status

Implemented:

- optional action icons;
- action icon position;
- row action display modes;
- boolean cell display modes;
- polished sortable headers;
- configurable control layout;
- additional CSS classes for root/wrapper/table;
- Bootstrap display variants;
- template overrides;
- custom column templates.

Not implemented yet:

- column header filter dropdowns;
- icon provider abstraction;
- SVG icon rendering;
- icon-only actions;
- modal confirmations;
- Tailwind theme;
- full theme registry.

## Action icons

Actions can render optional CSS-class icons.

Example:

```php
$definition->addRowAction(
    name: 'view',
    route: 'app_user_show',
    label: 'View',
    icon: 'bi bi-eye',
    routeParameters: [
        'id' => 'e.id',
    ],
);
```

The bundle does not require a specific icon library.

The host application must load the relevant CSS, for example:

- Bootstrap Icons;
- FontAwesome;
- custom icon classes.

## Action icon position

Icons render before the label by default.

To render the icon after the label:

```php
use Zhortein\DatatableBundle\Enum\ActionIconPosition;

$definition->addRowAction(
    name: 'view',
    route: 'app_user_show',
    label: 'Open',
    icon: 'bi bi-arrow-right',
    iconPosition: ActionIconPosition::After,
    routeParameters: [
        'id' => 'e.id',
    ],
);
```

Accessibility:

- icon spans are decorative;
- labels remain visible;
- icon-only actions are not part of the current public API.

## Row action display modes

Row actions can be rendered with different display modes.

Supported modes:

```text
inline
dropdown
list
```

### Inline mode

Inline mode is the default.

It renders row actions as a compact Bootstrap button group.

```php
$definition->setOption('rowActionDisplayMode', 'inline');
```

### Dropdown mode

Dropdown mode renders row actions inside a Bootstrap dropdown.

```php
$definition->setOption('rowActionDisplayMode', 'dropdown');
```

This is useful when a row has several actions.

Bootstrap JavaScript must be loaded by the host application.

### List mode

List mode renders row actions vertically.

```php
$definition->setOption('rowActionDisplayMode', 'list');
```

This can be useful for custom layouts or narrow responsive displays.

### Runtime override

The display mode can also be passed at render time:

```twig
{{ zhortein_datatable('users', {
    rowActionDisplayMode: 'dropdown'
}) }}
```

Runtime options take precedence over definition options.

## Boolean cell display modes

Boolean cells support several display modes.

Supported modes:

```text
badge
icon
switch
text
```

### Badge mode

Badge mode is the default.

```twig
{{ zhortein_datatable('users', {
    booleanDisplayMode: 'badge'
}) }}
```

It renders translated `Yes` / `No` labels as Bootstrap badges.

### Icon mode

Icon mode renders dependency-free check/cross characters with visually hidden translated labels.

```twig
{{ zhortein_datatable('users', {
    booleanDisplayMode: 'icon'
}) }}
```

No icon library is required.

### Switch mode

Switch mode renders a disabled Bootstrap switch.

```twig
{{ zhortein_datatable('users', {
    booleanDisplayMode: 'switch'
}) }}
```

This is display-only and does not update data.

### Text mode

Text mode renders translated text only.

```twig
{{ zhortein_datatable('users', {
    booleanDisplayMode: 'text'
}) }}
```

## Sortable header rendering

Sortable headers render:

- column label;
- neutral sort indicator;
- active sort indicator;
- accessible labels;
- `aria-sort` on the active column.

Indicators:

```text
↕ unsorted
↑ ascending
↓ descending
```

These indicators do not require an icon library.

Example rendered behavior:

```text
Email ↕
Email ↑
Email ↓
```

## Control layout

The datatable supports layout options for controls.

Supported modes:

```text
default
split
```

### Default layout

Default layout keeps controls in the top toolbar.

```twig
{{ zhortein_datatable('users', {
    controlsLayout: 'default'
}) }}
```

### Split layout

Split layout keeps search, filters, export and global actions near the top, and moves these below the table:

- column visibility;
- page size selector;
- summary.

```twig
{{ zhortein_datatable('users', {
    controlsLayout: 'split'
}) }}
```

This reduces toolbar clutter for dense business tables.

## Additional CSS classes

Applications can append CSS classes without overriding templates.

```twig
{{ zhortein_datatable('users', {
    rootClass: 'datatable datatable-users',
    tableWrapperClass: 'datatable-wrapper',
    tableClass: 'datatable-table'
}) }}
```

Available options:

| Option | Target |
|---|---|
| `rootClass` | Root datatable container |
| `tableWrapperClass` | Table responsive wrapper |
| `tableClass` | `<table>` element |

Classes are appended to existing Bootstrap classes.

They do not replace the bundle defaults.

## Bootstrap table variants

Bootstrap table variants are available at runtime.

```twig
{{ zhortein_datatable('users', {
    tableStriped: true,
    tableHover: true,
    tableBordered: true,
    tableSmall: true
}) }}
```

Supported options:

```text
tableStriped
tableHover
tableBordered
tableBorderless
tableSmall
tableResponsive
```

Global defaults can be configured in `zhortein_datatable.bootstrap.table`.

See [`configuration.md`](configuration.md).

## Template overrides

For layout customization beyond runtime options, use Twig template overrides.

Example:

```text
templates/bundles/ZhorteinDatatableBundle/bootstrap/_toolbar.html.twig
```

Prefer the smallest override possible:

1. custom column template;
2. cell template;
3. action template;
4. toolbar partial;
5. full datatable shell.

See [`templates.md`](templates.md) and [`template-context.md`](template-context.md).

## Column header filters

Column header filters are not implemented yet.

A design decision exists for the upcoming implementation:

```text
docs/decisions/0006-column-header-filter-dropdowns.md
```

The selected future approach is Bootstrap dropdowns rather than popovers.

See [`filters.md`](filters.md) and [`table-controls.md`](table-controls.md).

## Current limitations

### No icon provider

The bundle does not provide an icon abstraction yet.

Icons are CSS-class based.

### No icon-only actions

Action labels remain visible.

Icon-only actions require a dedicated accessibility design and are not implemented.

### No modal confirmation

Confirmation uses native `window.confirm()` for now.

### Bootstrap only

The only maintained theme is Bootstrap.

### No column header filters yet

Filter controls still render in the toolbar until the column header filter feature is implemented.

### No frontend test suite yet

The Stimulus controller is still tested through smoke tests, not automated JS tests.

## Related documentation

- [`actions-and-cells.md`](actions-and-cells.md)
- [`action-security.md`](action-security.md)
- [`cell-templates.md`](cell-templates.md)
- [`table-controls.md`](table-controls.md)
- [`templates.md`](templates.md)
- [`template-context.md`](template-context.md)
- [`theming.md`](theming.md)
- [`icons.md`](icons.md)
