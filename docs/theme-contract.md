# Theme extension contract

The 2.0 theme boundary separates datatable state from framework-specific
markup. Bootstrap is the default maintained theme; applications and optional
packages may register additional themes.

## Register a theme

Implement `ThemeInterface` as an autoconfigured Symfony service:

```php
<?php

declare(strict_types=1);

namespace App\Datatable\Theme;

use Zhortein\DatatableBundle\Contract\ThemeInterface;
use Zhortein\DatatableBundle\Enum\CellType;
use Zhortein\DatatableBundle\Enum\ThemeCapability;
use Zhortein\DatatableBundle\Theme\ThemeMetadata;

final readonly class AcmeTheme implements ThemeInterface
{
    public function getMetadata(): ThemeMetadata
    {
        return new ThemeMetadata(
            name: 'acme',
            templatePrefix: '@AcmeDatatable',
            capabilities: ThemeCapability::cases(),
        );
    }

    public function getDefaultCellClassName(CellType $cellType): ?string
    {
        return match ($cellType) {
            CellType::Numeric => 'acme-align-end',
            CellType::Boolean, CellType::Enum => 'acme-align-center',
            default => null,
        };
    }
}
```

Autoconfiguration adds the `zhortein_datatable.theme` tag. If autoconfiguration
is disabled, tag the service explicitly.

```yaml
services:
    App\Datatable\Theme\AcmeTheme:
        tags: ['zhortein_datatable.theme']

zhortein_datatable:
    default_theme: acme
```

Duplicate names are rejected. Selecting a name that is not registered raises a
`ThemeNotFoundException`.

## Metadata

`ThemeMetadata` contains:

| Property | Meaning |
|---|---|
| `name` | Stable configuration and registry name |
| `templatePrefix` | Twig prefix used for every theme template |
| `capabilities` | Supported optional UI behaviors |
| `assetRequirements` | Required packages, asset type and owning layer |

Asset ownership is explicit:

- `host_application`: the host installs and imports the asset;
- `theme_package`: an optional theme package owns the asset;
- `bundle`: the core bundle owns the asset.

The Bootstrap metadata declares Bootstrap 5 CSS and JavaScript as host-owned.
The core Stimulus package itself depends only on Stimulus.

## Required template surface

A complete theme provides the following templates below its prefix:

| Area | Templates |
|---|---|
| Shell | `datatable.html.twig`, `_header.html.twig`, `_body.html.twig`, `_row.html.twig`, `_cell.html.twig`, `_empty.html.twig` |
| Controls | `_toolbar.html.twig`, `_bottom_controls.html.twig`, `_pagination.html.twig`, `_filters.html.twig`, `_filter.html.twig`, `_column_filter.html.twig`, `_column_visibility.html.twig` |
| Search | `_search_builder.html.twig` |
| Actions | `_actions.html.twig`, `_action.html.twig`, `_bulk_actions.html.twig`, `_row_actions_inline.html.twig`, `_row_actions_list.html.twig`, `_row_actions_dropdown.html.twig`, `_list_action.html.twig`, `_dropdown_action.html.twig`, `_export.html.twig` |
| User state | `_saved_views.html.twig`, `_preferences.html.twig` |
| Confirmation | `_confirmation_modal.html.twig` |
| Cells | `cell/default.html.twig`, `cell/string.html.twig`, `cell/numeric.html.twig`, `cell/boolean.html.twig`, `cell/datetime.html.twig`, `cell/array.html.twig`, `cell/enum.html.twig` |

Themes must resolve their nested partials with `theme.template()`:

```twig
{% include theme.template('_pagination.html.twig') %}
```

Do not include a different theme's template. Missing templates fail fast.

## Template-context compatibility matrix

| Template group | Stable context |
|---|---|
| All renderer entry templates | `theme` (`ThemeMetadata`) |
| Shell and header | `definition`, `visibleColumns`, `columnClassNames`, `htmlId`, `options`, `filters` |
| Body and row | normalized `rows`, action mode, hierarchy and selection flags |
| Cell | `cell`, `column`, `column_label`, `value`, `row`, `source`, `row_identifier`, `datatable`, `datatable_context`, typed presentation values |
| Pagination | `result` when loaded, `options`, `htmlId` |
| Actions | normalized action maps documented in `theming.md` |

The detailed cell and action fields remain documented in
[Theming and Templates](theming.md).

## Frontend semantic contract

All themes must preserve:

- the `zhortein--datatable-bundle--datatable` controller identifier;
- documented Stimulus targets, values, params and actions;
- `aria-*`, `role`, `hidden` and form semantics;
- datatable BEM hooks used by the framework-neutral controller;
- the root presentation-class values used for hidden/visible states, statuses
  and dropdown overflow;
- Search Builder presentation-class attributes;
- hierarchy loading/error presentation-class attributes.

The controller never imports a theme framework and does not generate
framework-specific class names.

## Override precedence

An explicit column template wins over the theme's typed cell template. For all
theme templates, normal Twig loader precedence then applies. Built-in Bootstrap
templates can still be overridden under:

```text
templates/bundles/ZhorteinDatatableBundle/bootstrap/
```

Theme packages should publish their own Twig namespace instead of copying files
into the bundle override directory.

## External package validation

The CI installs the independent
`tools/smoke-test/external-theme` Composer package into a fresh Symfony
application. The smoke host enables its bundle, selects `default_theme: acme`
and verifies both the initial shell and Ajax fragments.

This test protects the complete integration boundary:

- Composer autoloading outside the core bundle namespace;
- Symfony bundle registration and service autoconfiguration;
- `ThemeInterface` discovery through the public service tag;
- package-owned Twig namespace and complete template surface;
- theme-specific default cell presentation;
- shell and fragment rendering without cross-theme template fallback.

Run it locally with:

```bash
SMOKE_EXTERNAL_THEME=1 tools/smoke-test/fresh-symfony-app.sh
```
