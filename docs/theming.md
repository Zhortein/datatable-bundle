# Theming and rendering customization

This document explains the current theming capabilities and limitations of `zhortein/datatable-bundle`.

The bundle is currently **Bootstrap-first**.

It does not provide multiple maintained themes yet.

## Current status

Implemented:

- Bootstrap template tree;
- Twig template overrides;
- custom column templates;
- typed cell templates;
- Bootstrap table display variants;
- Bootstrap rendering defaults through bundle configuration;
- optional CSS-class based action icons;
- documented template context.

Not implemented yet:

- Tailwind theme;
- custom theme registry;
- theme discovery;
- multi-theme template namespaces;
- CSS asset generation;
- icon provider abstraction;
- Symfony UX Icons integration;
- full design system abstraction.

## Current theme

The only supported theme is:

```text
bootstrap
```

Configuration:

```yaml
zhortein_datatable:
    default_theme: bootstrap
```

The renderer uses this value to resolve templates such as:

```text
@ZhorteinDatatable/bootstrap/datatable.html.twig
@ZhorteinDatatable/bootstrap/_toolbar.html.twig
@ZhorteinDatatable/bootstrap/cell/string.html.twig
```

## Bootstrap templates

Templates live under:

```text
templates/bootstrap/
```

The template tree is documented in [`templates.md`](templates.md).

Important templates:

```text
datatable.html.twig
_toolbar.html.twig
_header.html.twig
_body.html.twig
_row.html.twig
_cell.html.twig
_action.html.twig
_pagination.html.twig
_filter.html.twig
_column_visibility.html.twig
_export.html.twig
cell/*.html.twig
```

## Template override strategy

Host applications can override bundle templates using Symfony's standard bundle override mechanism.

Example:

```text
templates/bundles/ZhorteinDatatableBundle/bootstrap/_toolbar.html.twig
```

This is currently the recommended customization strategy.

Prefer small overrides:

1. custom column template;
2. cell template;
3. action template;
4. toolbar partial;
5. full datatable shell only when necessary.

More details are available in [`templates.md`](templates.md).

## Runtime Bootstrap variants

Bootstrap table variants can be configured at render time.

Example:

```twig
{{ zhortein_datatable('users', {
    tableStriped: true,
    tableHover: true,
    tableBordered: true,
    tableSmall: true,
    tableResponsive: true
}) }}
```

Supported runtime options:

```text
tableStriped
tableHover
tableBordered
tableBorderless
tableSmall
tableResponsive
```

## Global Bootstrap defaults

Bootstrap table variants can also be configured globally.

```yaml
zhortein_datatable:
    bootstrap:
        table:
            striped: true
            hover: true
            bordered: false
            borderless: false
            small: false
            responsive: true
```

Runtime options still take precedence over configuration.

## Cell rendering customization

The renderer supports:

- built-in typed cell templates;
- custom column templates;
- default alignment by cell type.

Example:

```php
$definition->addColumn(
    name: 'e.status',
    label: 'Status',
    template: 'admin/datatable/cell/status.html.twig',
    type: 'string',
);
```

More details are available in [`cell-templates.md`](cell-templates.md).

## Icon strategy

Icons are optional and CSS-class based.

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

The bundle does not require:

- Bootstrap Icons;
- FontAwesome;
- Symfony UX Icons;
- any SVG icon package.

The host application is responsible for loading the icon CSS.

More details are available in [`icons.md`](icons.md).

## Template context

Template context is documented in [`template-context.md`](template-context.md).

This document describes variables available in:

- datatable shell;
- toolbar;
- header;
- rows;
- cells;
- actions;
- filters;
- pagination.

## Current limitations

### Bootstrap only

The bundle currently supports only the Bootstrap template tree.

There is no Tailwind theme and no generic theme registry yet.

### Theme string only

The renderer currently uses a simple theme string.

There is no dedicated `ThemeInterface`, theme registry or theme discovery mechanism yet.

### No CSS asset package

The bundle does not ship a dedicated CSS file yet.

It assumes the host application already loads Bootstrap or Bootstrap-compatible styles.

### No icon provider abstraction

Action icons are rendered as CSS classes only.

There is no icon alias mapping, SVG provider or Symfony UX Icons integration yet.

### Template context may still evolve

The project is not stable yet.

Some template context details may change before 1.0, especially around:

- filters;
- exports;
- preferences;
- actions;
- advanced Doctrine fields.

### No design system abstraction

The bundle is meant to provide business-oriented datatable rendering, not a full design system.

Applications with strict design systems should override templates.

## Recommended customization path

For most applications:

1. Keep the default Bootstrap templates.
2. Use runtime Bootstrap options for small variants.
3. Use `className` for column-level styling.
4. Use custom column templates for special cells.
5. Override partial templates for global layout changes.
6. Avoid overriding `datatable.html.twig` unless truly necessary.

## Future direction

Potential future work:

- formal theme registry;
- documented template stability levels;
- optional icon renderer interface;
- optional Symfony UX Icons integration;
- richer enum badge templates;
- compact table variant;
- custom theme documentation;
- Tailwind evaluation after Bootstrap API stabilizes.

## Related documentation

- [`templates.md`](templates.md)
- [`template-context.md`](template-context.md)
- [`cell-templates.md`](cell-templates.md)
- [`icons.md`](icons.md)
- [`configuration.md`](configuration.md)
- [`architecture.md`](architecture.md)
