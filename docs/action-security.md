# Action security and visibility

This document explains how datatable action visibility, authorization, CSRF and confirmation work.

The bundle provides extension points and safe rendering defaults, but it does not impose a specific security model or application user system.

## Status

Implemented:

- action visibility extension point;
- default allow-all visibility checker;
- row action visibility filtering;
- global action visibility filtering;
- optional Symfony authorization adapter;
- CSRF-aware rendering for non-GET actions;
- confirmation metadata rendering;
- vanilla Stimulus confirmation behavior;
- tests covering visibility, authorization, CSRF and confirmation.

Not implemented yet:

- built-in voters;
- security expression language;
- per-action visibility callbacks in the public API;
- icon-only action accessibility model;
- Bootstrap modal confirmation;
- async confirmation;
- controller-side action handling.

## Action visibility model

Actions are filtered through:

```php
ActionVisibilityCheckerInterface
```

The default implementation is:

```php
AllowAllActionVisibilityChecker
```

It preserves the default behavior: every declared action is visible.

Applications can replace the service to apply business or security rules.

## Visibility context

The checker receives:

```php
ActionDefinition
ActionVisibilityContext
```

`ActionVisibilityContext` contains:

- the current `DatatableDefinition`;
- optional row data for row actions;
- runtime options.

For row actions, the context contains the current row.

For global actions, the context has no row.

## Replacing the visibility checker

Applications can replace:

```php
Zhortein\DatatableBundle\Action\ActionVisibilityCheckerInterface
```

Example direction:

```yaml
services:
    App\Datatable\Security\MyActionVisibilityChecker:
        autowire: true

    Zhortein\DatatableBundle\Action\ActionVisibilityCheckerInterface:
        alias: App\Datatable\Security\MyActionVisibilityChecker
```

Example checker:

```php
use Zhortein\DatatableBundle\Action\ActionVisibilityCheckerInterface;
use Zhortein\DatatableBundle\Action\ActionVisibilityContext;
use Zhortein\DatatableBundle\Definition\ActionDefinition;

final readonly class MyActionVisibilityChecker implements ActionVisibilityCheckerInterface
{
    public function isVisible(ActionDefinition $action, ActionVisibilityContext $context): bool
    {
        if ('delete' === $action->getName()) {
            $row = $context->getRow();

            return is_array($row) && true !== ($row['is_locked'] ?? false);
        }

        return true;
    }
}
```

## Row action visibility

Row actions are filtered before URL generation.

If an action is hidden:

- it is not rendered;
- its URL is not generated;
- route parameters are not resolved.

This avoids unnecessary work and prevents hidden actions from leaking URLs.

## Global action visibility

Global actions are also filtered before URL generation.

The visibility context has no row.

This is useful for toolbar actions such as:

- create;
- import;
- export;
- bulk actions.

## Optional Symfony authorization adapter

The bundle provides:

```php
AuthorizationActionVisibilityChecker
```

This adapter uses Symfony's:

```php
AuthorizationCheckerInterface
```

It is optional and does not replace the default allow-all checker automatically.

### Permission attribute

The adapter reads an action attribute named:

```text
permission
```

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
    attributes: [
        'permission' => 'USER_DELETE',
    ],
);
```

### Row action subject

For row actions, the row array is used as the authorization subject.

```php
$authorizationChecker->isGranted('USER_DELETE', $row);
```

### Global action subject

For global actions, the `DatatableDefinition` is used as the authorization subject.

```php
$authorizationChecker->isGranted('USER_CREATE', $definition);
```

### Enabling the adapter

Example service alias:

```yaml
services:
    Zhortein\DatatableBundle\Action\ActionVisibilityCheckerInterface:
        alias: Zhortein\DatatableBundle\Action\AuthorizationActionVisibilityChecker
```

The host application must have Symfony Security configured.

## CSRF-aware action rendering

GET actions are rendered as links.

Non-GET actions are rendered as POST forms.

### GET actions

Example:

```php
$definition->addRowAction(
    name: 'view',
    route: 'app_user_show',
    label: 'View',
    routeParameters: [
        'id' => 'e.id',
    ],
);
```

Rendered direction:

```html
<a href="/users/42">View</a>
```

GET actions do not include CSRF tokens.

### Non-GET actions

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
);
```

Rendered direction:

```html
<form method="post" action="/users/42/delete">
    <input type="hidden" name="_method" value="DELETE">
    <input type="hidden" name="_token" value="...">
    <button type="submit">Delete</button>
</form>
```

This avoids unsafe destructive links.

### CSRF token strategy

When a `CsrfTokenManagerInterface` is available, non-GET actions include a hidden `_token` field.

Token IDs use this pattern:

```text
zhortein_datatable_action_{action_name}
```

Example:

```text
zhortein_datatable_action_delete
```

If no CSRF token manager is available, the form is still rendered without a token.

Applications using non-GET actions should configure Symfony CSRF support.

## Confirmation metadata

Actions can declare a confirmation message.

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

The renderer outputs:

```html
data-zhortein--datatable-bundle--datatable-confirmation-message="Delete this user?"
```

For GET actions, the metadata is placed on the link.

For non-GET actions, the metadata is placed on the form.

## Vanilla confirmation behavior

The Stimulus controller exposes:

```text
zhortein--datatable-bundle--datatable#confirmAction
```

GET action confirmation:

```html
data-action="click->zhortein--datatable-bundle--datatable#confirmAction"
```

Non-GET form confirmation:

```html
data-action="submit->zhortein--datatable-bundle--datatable#confirmAction"
```

The first implementation uses:

```js
window.confirm(message)
```

If the user cancels, navigation or form submission is prevented.

## Bootstrap modal confirmation

Action confirmations use a Bootstrap modal when the modal target and Bootstrap JavaScript are available.

Native `window.confirm()` remains the fallback.

The existing confirmation metadata stays unchanged:

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

The modal can be disabled at render time:

```twig
{{ zhortein_datatable('users', {
    confirmationModal: false
}) }}
```

When disabled, the controller falls back to native browser confirmation.

## Security recommendations

### Do not rely only on hidden actions

Action visibility is a UI-level feature.

The server route handling the action must still enforce authorization.

### Protect action endpoints

Host applications should protect action routes using:

- Symfony voters;
- access control;
- controller checks;
- form CSRF validation;
- business rules.

### Use non-GET methods for mutations

Destructive or state-changing actions should use non-GET methods:

- POST;
- PUT;
- PATCH;
- DELETE.

The bundle renders these as forms.

### Keep labels visible

The current icon strategy keeps labels visible.

Avoid icon-only action buttons unless you provide accessible labels in custom templates.

## Current limitations

### No built-in voters

The bundle does not ship voters.

### No security expressions

There is no expression language integration for actions yet.

### No per-action callback API

Action visibility currently goes through a global checker service.

### No modal confirmation

Only native `window.confirm()` is implemented.

### No controller-side action handling

The bundle renders action links/forms, but does not implement target action controllers.

### No batch action security yet

Batch actions are not implemented yet.

## Related documentation

- [`actions-and-cells.md`](actions-and-cells.md)
- [`icons.md`](icons.md)
- [`templates.md`](templates.md)
- [`architecture.md`](architecture.md)
