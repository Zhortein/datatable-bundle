# Optional icon rendering strategy

This document describes the current icon rendering strategy.

The bundle must not require a specific icon library.

Applications may use Bootstrap Icons, FontAwesome, Symfony UX Icons, custom CSS classes or no icons at all.

## Status

Implemented:

- action definitions can declare an optional icon CSS class;
- row actions can render an icon span;
- global actions can render an icon span;
- icon markup is hidden from assistive technologies with `aria-hidden="true"`;
- buttons and links remain usable without icons.

Not implemented yet:

- icon provider abstraction;
- Symfony UX Icons integration;
- SVG icon rendering;
- icon set configuration;
- built-in Bootstrap Icons dependency;
- built-in FontAwesome dependency.

## Current action icon API

Actions can define an icon:

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

The renderer outputs:

```html
<span class="bi bi-eye" aria-hidden="true"></span>
```

The action label remains visible:

```html
View
```

This means actions stay accessible even if the icon library is not loaded.

## Global action icons

Global actions use the same icon behavior:

```php
$definition->addGlobalAction(
    name: 'create',
    route: 'app_user_create',
    label: 'Create',
    icon: 'bi bi-plus-lg',
);
```

## No mandatory dependency

The bundle does not require:

- Bootstrap Icons;
- FontAwesome;
- Symfony UX Icons;
- any SVG icon package.

The application decides which icon system to load.

## Recommended usage with Bootstrap Icons

If the host application uses Bootstrap Icons:

```php
$definition->addRowAction(
    name: 'edit',
    route: 'app_user_edit',
    label: 'Edit',
    icon: 'bi bi-pencil',
    routeParameters: [
        'id' => 'e.id',
    ],
);
```

The host application must ensure Bootstrap Icons CSS is loaded.

## Recommended usage with FontAwesome

If the host application uses FontAwesome:

```php
$definition->addRowAction(
    name: 'delete',
    route: 'app_user_delete',
    label: 'Delete',
    icon: 'fa-solid fa-trash',
    httpMethod: 'DELETE',
    routeParameters: [
        'id' => 'e.id',
    ],
);
```

The host application must ensure FontAwesome CSS is loaded.

## Accessibility

Icon spans are rendered with:

```html
aria-hidden="true"
```

The visible action label remains the accessible text.

The current strategy intentionally avoids icon-only buttons.

If icon-only actions are added later, they must provide an accessible label.

## Template override

Applications can override action templates to implement a different icon strategy.

Relevant templates:

```text
templates/bootstrap/_action.html.twig
templates/bootstrap/_actions.html.twig
```

Override path:

```text
templates/bundles/ZhorteinDatatableBundle/bootstrap/_action.html.twig
```

## Future direction

A future icon provider abstraction may support:

- Symfony UX Icons;
- SVG icon rendering;
- icon aliases;
- per-theme icon mapping;
- fallback icons.

Possible direction:

```php
interface IconRendererInterface
{
    public function render(string $icon, array $attributes = []): string;
}
```

This is intentionally postponed until the rest of the public action API stabilizes.

## Current limitations

### CSS classes only

The current icon value is rendered directly as a CSS class.

### No icon validation

The bundle does not validate whether the icon class exists.

### No icon aliases

There is no built-in alias system such as `view`, `edit`, `delete`.

### No icon-only action support

Action labels are currently expected to remain visible.

### No SVG support

SVG icons are not rendered by the core bundle yet.
