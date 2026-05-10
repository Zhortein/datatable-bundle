# Actions and typed cell rendering

This document explains how to declare datatable actions and customize cell rendering.

## Status

The current implementation supports:

- GET row actions rendered as links;
- non-GET row actions rendered as forms;
- GET global actions rendered as links in the toolbar;
- non-GET global actions rendered as forms;
- CSRF token support when a CSRF token manager is available;
- route parameter resolution from row data;
- built-in typed cell templates;
- custom column Twig templates.

Not implemented yet:

- permission voters;
- action visibility callbacks;
- batch actions on selected rows;
- dropdown action groups;
- JavaScript confirmation modals;
- advanced icon abstraction;
- per-action security expressions.

## Row actions

Row actions are declared on a `DatatableDefinition`.

Example:

```php
$definition
    ->addColumn('e.id', visible: false, sortable: false, searchable: true)
    ->addColumn('e.email', label: 'Email')
    ->addRowAction(
        name: 'view',
        route: 'app_user_show',
        label: 'View',
        routeParameters: [
            'id' => 'e.id',
        ],
        className: 'btn btn-sm btn-outline-primary',
    )
;
```

A row action is rendered for each row.

## Route parameter resolution

Row actions use `RowActionRouteParameterResolver` to resolve route parameters from row data.

Supported row key styles:

```text
id
e_id
e.id
```

For example:

```php
routeParameters: [
    'id' => 'e.id',
]
```

can be resolved from a row containing:

```php
[
    'e_id' => 42,
]
```

or:

```php
[
    'id' => 42,
]
```

If a required value cannot be resolved, a `MissingRouteParameterValueException` is thrown.

## GET row actions

GET row actions are rendered as links.

Example output direction:

```html
<a href="/users/42" class="btn btn-sm btn-outline-primary">
    View
</a>
```

GET actions do not receive CSRF tokens.

## Non-GET row actions

Non-GET row actions are rendered as forms.

Example:

```php
$definition->addRowAction(
    name: 'delete',
    route: 'app_user_delete',
    label: 'Delete',
    httpMethod: 'DELETE',
    routeParameters: [
        'id' => 'e.id',
    ],
    className: 'btn btn-sm btn-danger',
);
```

Example output direction:

```html
<form method="post" action="/users/42/delete" class="d-inline">
    <input type="hidden" name="_method" value="DELETE">
    <input type="hidden" name="_token" value="...">

    <button type="submit" class="btn btn-sm btn-danger">
        Delete
    </button>
</form>
```

This avoids rendering unsafe destructive links.

## CSRF token support

When `CsrfTokenManagerInterface` is available, non-GET actions include a `_token` hidden field.

Token IDs follow this pattern:

```text
zhortein_datatable_action_{action_name}
```

Example:

```text
zhortein_datatable_action_delete
```

If no CSRF token manager is available, the form is still rendered without a token.

Applications that use non-GET actions should install and configure Symfony CSRF support.

## Global actions

Global actions are rendered in the datatable toolbar.

They are useful for operations such as:

- create;
- import;
- export;
- bulk operations.

Example:

```php
$definition->addGlobalAction(
    name: 'create',
    route: 'app_user_create',
    label: 'Create',
    className: 'btn btn-sm btn-primary',
);
```

GET global actions are rendered as links.

Non-GET global actions are rendered as forms with CSRF tokens when available.

## Action attributes

Actions can define HTML attributes:

```php
$definition->addRowAction(
    name: 'view',
    route: 'app_user_show',
    label: 'View',
    routeParameters: [
        'id' => 'e.id',
    ],
    attributes: [
        'data-test' => 'view-user',
    ],
);
```

Attributes are rendered on the link or button element.

## Action icons

Actions can define an optional icon CSS class:

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

The host application must load the icon CSS if it wants icons to appear.

More details are available in [`icons.md`](icons.md).

## Typed cell templates

The renderer supports type-specific cell templates.

Initial supported types:

```text
default
string
numeric
boolean
datetime
array
enum
```

Column type can be declared explicitly:

```php
$definition->addColumn(
    name: 'e.createdAt',
    label: 'Created at',
    type: 'datetime',
);
```

Unknown types fall back to `default`.

## Built-in templates

Built-in templates live under:

```text
templates/bootstrap/cell/
```

Current templates:

```text
default.html.twig
string.html.twig
numeric.html.twig
boolean.html.twig
datetime.html.twig
array.html.twig
enum.html.twig
```

## Boolean cells

Boolean cells render Bootstrap badges:

```html
<span class="badge text-bg-success">Yes</span>
```

or:

```html
<span class="badge text-bg-secondary">No</span>
```

The text is not translated yet.

## Datetime cells

Datetime cells use the format:

```text
Y-m-d H:i
```

This will be improved later with Symfony translation and locale-aware formatting.

## Array cells

Array cells are rendered as JSON inside a `<code>` element.

This is intentionally simple for now.

## Custom column templates

A column can define its own Twig template:

```php
$definition->addColumn(
    name: 'e.status',
    label: 'Status',
    template: 'admin/datatable/cell/status.html.twig',
    type: 'string',
);
```

Custom templates take precedence over built-in type-specific templates.

## Custom template context

Custom templates receive:

```twig
{{ column.name }}
{{ column.label }}
{{ value }}
```

Example:

```twig
<span class="badge text-bg-info">
    {{ value }}
</span>
```

## Cell template reference

Built-in and custom cell templates are documented in [`cell-templates.md`](cell-templates.md).

The reference covers:

- supported cell types;
- built-in templates;
- custom column templates;
- cell template context;
- fallback order;
- default alignment by type;
- Doctrine type enrichment.

## Template resolution order

The renderer resolves cell templates in this order:

1. custom column template;
2. built-in type-specific template;
3. default template.

## Doctrine type enrichment

Doctrine-backed datatables can receive inferred cell types through Doctrine metadata.

For example:

- string fields become `string`;
- boolean fields become `boolean`;
- datetime fields become `datetime`;
- numeric fields become `numeric`.

Explicit column types are preserved.

## Action visibility extension point

Applications can replace `ActionVisibilityCheckerInterface` to control action visibility.

The default implementation allows all actions.

The checker receives an `ActionDefinition` and an `ActionVisibilityContext`.

For row actions, the context contains row data.

For global actions, the context has no row.

The extension point does not require Symfony Security.

## Row action visibility

Row actions can be hidden by replacing `ActionVisibilityCheckerInterface`.

The checker receives:

- the `ActionDefinition`;
- an `ActionVisibilityContext`;
- row data for row actions.

Hidden actions are not rendered, and their URLs are not generated.

This allows applications to hide row actions based on row data or application-specific rules.

## Global action visibility

Global actions can be hidden by replacing `ActionVisibilityCheckerInterface`.

The checker receives:

- the `ActionDefinition`;
- an `ActionVisibilityContext`;
- no row data for global actions.

Hidden global actions are not rendered, and their URLs are not generated.

This allows applications to hide toolbar actions based on application-specific rules.

## Optional Symfony authorization integration

Applications using Symfony Security can use `AuthorizationActionVisibilityChecker`.

It checks an action attribute named `permission`.

```php
$definition->addRowAction(
    name: 'delete',
    route: 'app_user_delete',
    label: 'Delete',
    httpMethod: 'DELETE',
    routeParameters: [
        'id' => 'e.id',
    ],
    attributes: [
        'permission' => 'USER_DELETE',
    ],
);
```

For row actions, row data is used as the authorization subject.

For global actions, the `DatatableDefinition` is used as the subject.

This adapter is optional. Applications can still provide their own `ActionVisibilityCheckerInterface`.

## Action confirmation metadata

Actions can declare a confirmation message:

```php
$definition->addRowAction(
    name: 'delete',
    route: 'app_user_delete',
    label: 'Delete',
    httpMethod: 'DELETE',
    confirmationMessage: 'Delete this user?',
    routeParameters: [
        'id' => 'e.id',
    ],
);
```

The renderer outputs:

```html
data-zhortein-datatable-confirmation-message="Delete this user?"
```

The metadata is passive until JavaScript confirmation behavior is enabled.

## Action confirmation behavior

Actions with `confirmationMessage` trigger native browser confirmation.

Example:

```php
$definition->addRowAction(
    name: 'delete',
    route: 'app_user_delete',
    label: 'Delete',
    httpMethod: 'DELETE',
    confirmationMessage: 'Delete this user?',
    routeParameters: [
        'id' => 'e.id',
    ],
);
```

Rendered markup calls the Stimulus `confirmAction` method.

The first implementation uses `window.confirm()`.

If the user cancels, navigation or form submission is prevented.

## CSRF behavior

GET actions are rendered as links and do not include CSRF tokens.

Non-GET actions are rendered as forms:

```html
<form method="post" action="...">
    <input type="hidden" name="_method" value="DELETE">
    <input type="hidden" name="_token" value="...">
</form>
```

The CSRF token field is rendered only when a `CsrfTokenManagerInterface` is available.

Token ids follow this pattern:

```text
zhortein_datatable_action_{action_name}
```

Example:

```text
zhortein_datatable_action_delete
```

## Action security and visibility

Action visibility, CSRF behavior and confirmation behavior are documented in [`action-security.md`](action-security.md).

Summary:

- row and global actions can be filtered through `ActionVisibilityCheckerInterface`;
- the default checker allows all actions;
- an optional Symfony authorization adapter is available;
- GET actions render as links;
- non-GET actions render as CSRF-aware forms;
- confirmation metadata can trigger vanilla Stimulus confirmation.

Server-side routes must still enforce authorization.


## Current limitations

### No action visibility conditions

Actions are currently always rendered when declared.

Visibility callbacks or security expressions are not implemented yet.

### No permission voters

The renderer does not check Symfony voters or roles.

Applications should avoid declaring actions the current user should not see until action security integration exists.

### No batch actions yet

Global actions exist, but selected-row batch actions are not implemented yet.

### Basic labels

Action labels and built-in cell labels are not translated yet.

### Basic icons

Icons are rendered as CSS classes only.

There is no icon provider abstraction yet.

## Recommended usage

Use the current action system for simple back-office actions:

- view;
- edit;
- delete;
- create.

Use custom cell templates when one column needs specific markup.

Keep complex permission logic outside the bundle until dedicated visibility/security hooks are introduced.
