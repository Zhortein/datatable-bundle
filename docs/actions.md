# Actions and Security

This document explains how to declare datatable actions, handle security, and manage visibility.

The bundle supports **Row Actions** (per row), **Global Actions** (rendered in the toolbar) and **Bulk Actions** (on selected rows).

## Status

Currently implemented:
-   GET actions (rendered as links).
-   Non-GET actions (POST, PUT, DELETE - rendered as forms with CSRF protection).
-   Bulk actions (on multiple selected rows).
-   Route parameter resolution from row data.
-   Action visibility checker extension point.
-   Optional Symfony Authorization adapter (voters).
-   Confirmation messages (native `window.confirm` or Bootstrap modal).

Not implemented yet:
-   Action visibility callbacks in the public API.
-   Advanced icon-only action accessibility model.
-   Async confirmations.

## Declaring Actions

Actions are declared in the datatable class.

### Row Actions
Row actions are rendered for each row of the datatable.

```php
$definition->addRowAction(
    name: 'view',
    route: 'app_user_show',
    label: 'View',
    routeParameters: [
        'id' => 'e.id', // Maps 'e.id' from row data to 'id' route parameter
    ],
    className: 'btn btn-sm btn-outline-primary',
);
```

### Global Actions
Global actions are rendered in the datatable toolbar.

```php
$definition->addGlobalAction(
    name: 'create',
    route: 'app_user_create',
    label: 'Create',
    className: 'btn btn-sm btn-primary',
    icon: 'bi bi-plus-lg',
);
```

### Bulk Actions
Bulk actions are used to perform operations on multiple rows. See [Bulk Actions and Selection](bulk-actions.md) for detailed documentation.

```php
$definition->addBulkAction(
    name: 'delete_selected',
    route: 'app_user_bulk_delete',
    label: 'Delete Selected',
    className: 'btn btn-outline-danger',
    confirmationMessage: 'Are you sure you want to delete the selected rows?',
);
```

## Security and CSRF

### Non-GET Actions
Actions using `POST`, `PUT`, `PATCH`, or `DELETE` are rendered as forms to avoid unsafe destructive links.

If `CsrfTokenManagerInterface` is available, these forms include a hidden `_token` field. The token ID follows the pattern `zhortein_datatable_action_{action_name}`.

### Action Visibility
Actions are filtered through an `ActionVisibilityCheckerInterface`. The default implementation is `AllowAllActionVisibilityChecker`.

#### Symfony Authorization Adapter
You can use Symfony's security system by enabling the `AuthorizationActionVisibilityChecker`. Set the action's dedicated `permission` option to the voter attribute:

```php
$definition->addRowAction(
    name: 'delete',
    route: 'app_user_delete',
    label: 'Delete',
    httpMethod: 'DELETE',
    permission: 'USER_DELETE',
);
```

Enable the adapter in your service configuration:

```yaml
services:
    Zhortein\DatatableBundle\Action\ActionVisibilityCheckerInterface:
        alias: Zhortein\DatatableBundle\Action\AuthorizationActionVisibilityChecker
```

`permission` is authorization metadata and is never rendered as an HTML attribute. For compatibility with beta releases, a legacy `attributes: ['permission' => '...']` value is still recognized and removed from the rendered attributes. New code should use the dedicated option.

## Confirmation Messages

You can add a confirmation step to any action:

```php
$definition->addRowAction(
    name: 'delete',
    // ...
    confirmationMessage: 'Are you sure you want to delete this user?',
);
```

By default, this uses `window.confirm()`. If Bootstrap JavaScript and a modal target are present, it will use a Bootstrap modal instead.

## Customization

-   **Icons**: Provide a CSS class via the `icon` option. If no explicit icon is provided, the bundle attempts to resolve a default icon based on the action name (e.g., `view`, `edit`, `delete`). See [Icon System](icons.md) for details.
-   **Position**: Use `ActionIconPosition` enum to place icons `Before` or `After` the label.
-   **Attributes**: Pass arbitrary HTML attributes via the `attributes` array. Do not put authorization metadata in this array.

## Related documentation

- [Icon System](icons.md)
- [Bulk Actions and Selection](bulk-actions.md)
- [UI/UX customization](ui-ux.md)
- [Theming](theming.md)
- [Architecture](architecture/overview.md)
