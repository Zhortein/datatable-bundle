# Actions and Security

This document explains how to declare datatable actions, handle security, and manage visibility.

The bundle supports **Row Actions** (per row), **Global Actions** (rendered in the toolbar) and **Bulk Actions** (on selected rows).

## Status

Currently implemented:
-   GET actions (rendered as links).
-   Non-GET actions (POST, PUT, DELETE - rendered as forms with CSRF protection).
-   Bulk actions (on multiple selected rows).
-   Typed route parameter sources for row data, literals and explicit datatable context.
-   Action visibility checker extension point.
-   Optional Symfony Authorization adapter (voters).
-   Confirmation messages (native `window.confirm` or Bootstrap modal).
-   Opt-in Ajax execution with a versioned response contract and progressive enhancement.

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

## Route parameters

An action route parameter can use an explicit `RouteParameter` source:

```php
use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Definition\RouteParameter;

$definition
    ->setContext(new DatatableContext([
        'locale' => $locale,
    ]))
    ->addRowAction(
        name: 'preview',
        route: 'app_article_preview',
        label: 'Preview',
        routeParameters: [
            'id' => RouteParameter::row('e.id'),
            '_locale' => RouteParameter::context('locale'),
            'format' => RouteParameter::literal('html'),
        ],
    )
;
```

The available sources are:

| Declaration | Resolution |
|---|---|
| `RouteParameter::row('e.id')` | Required value from the normalized row |
| `RouteParameter::literal('html')` | Explicit literal value |
| `RouteParameter::context('locale')` | Required value from the definition's allowlisted context |
| `RouteParameter::optionalRow('slug')` | Row value, omitted when absent or `null` |
| `RouteParameter::optionalContext('tenant')` | Context value, omitted when absent or `null` |
| `RouteParameter::rowOr('slug', 'preview')` | Row value with a fallback |
| `RouteParameter::contextOr('locale', 'en')` | Context value with a fallback |

A `null` fallback omits the parameter. Required row and context sources reject
both missing and `null` values with an exception that identifies the action,
route parameter and source. Literals are passed deliberately, including
`null`, so Symfony's URL generator remains authoritative for the target route.

Resolved values may be scalar, `Stringable` or backed enums. Backed enums are
reduced to their backing value and `Stringable` objects to strings before URL
generation. Arrays and arbitrary objects are rejected.

### Row lookup rules

Row sources work with both built-in providers. Resolution checks, in order:

1. the exact normalized array key, such as `e.id`;
2. the Doctrine scalar alias, such as `e_id`;
3. a nested array or readable object path, such as `translation.locale`;
4. the final segment fallback, such as `id`.

This allows a selected Doctrine projection and an Array provider row to use the
same declaration without requiring a visible or hidden technical column for a
literal or contextual value.

### Context allowlist and request locales

`DatatableContext` is an explicit, server-side allowlist owned by one
`DatatableDefinition`. It is recreated when the definition is built for an
Ajax fragment, so a request-aware datatable may select the current locale
without exposing the full request:

```php
use Symfony\Component\HttpFoundation\RequestStack;
use Zhortein\DatatableBundle\Context\DatatableContext;

final class ArticleDatatable implements DatatableInterface
{
    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    public function buildDatatable(DatatableDefinition $definition): void
    {
        $locale = $this->requestStack->getCurrentRequest()?->getLocale() ?? 'en';

        $definition->setContext(new DatatableContext([
            'locale' => $locale,
        ]));

        // Columns and actions...
    }
}
```

Do not put the request, session, token, user or another broad application
object in this context. Store only the minimal validated value needed by the
definition. A context value used as a route parameter is visible in the
generated URL and must never contain a secret. Authorization and tenant
validation remain the responsibility of the target route.

The context is not accepted from fragment query parameters and is not
serialized automatically. Cross-request propagation of explicitly
browser-safe context is a separate contract; applications must rebuild the
current server context for now.

### Compatibility and migration

Existing 1.x declarations remain valid:

- a string in a row action still means a normalized row key;
- a string in a global or bulk action still means a literal value.

Consequently, this declaration does not need to change:

```php
routeParameters: ['id' => 'e.id']
```

Use the typed form for new code and when the value does not come from a row.
The former hidden-column workaround:

```php
routeParameters: [
    'locale' => 'frTranslation.locale',
]
```

can become either an explicit literal:

```php
routeParameters: [
    'locale' => RouteParameter::literal('fr'),
]
```

or an allowlisted, request-aware context value:

```php
routeParameters: [
    'locale' => RouteParameter::context('locale'),
]
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

Action labels and confirmation messages are resolved in the definition's
translation domain at render time. This applies consistently to row, global
and bulk actions, including row-action fragments loaded through Ajax:

```php
$definition
    ->setTranslationDomain('admin')
    ->addRowAction(
        name: 'delete',
        route: 'app_user_delete',
        label: 'users.actions.delete',
        confirmationMessage: 'users.confirmations.delete',
        httpMethod: 'DELETE',
        routeParameters: ['id' => 'e.id'],
    )
;
```

Without a definition domain, both values are treated as final literal text.
See [declarative translations](configuration.md#translating-declarative-labels).

## Opt-in Ajax execution

Classic links and forms remain the default. Add `AjaxActionOptions` only when
an action should be intercepted by the bundled Stimulus controller:

```php
use Zhortein\DatatableBundle\Definition\AjaxActionOptions;
use Zhortein\DatatableBundle\Enum\AjaxActionSuccessStrategy;

$definition->addRowAction(
    name: 'archive',
    route: 'app_user_archive',
    label: 'Archive',
    httpMethod: 'PATCH',
    routeParameters: [
        'id' => RouteParameter::row('e.id'),
    ],
    confirmationMessage: 'Archive this user?',
    ajax: new AjaxActionOptions(
        AjaxActionSuccessStrategy::RefreshRow,
    ),
);
```

The option is available on row, global and bulk actions. Omitting it preserves
the complete 1.x behavior.

### Success strategies

| Strategy | Behavior |
|---|---|
| `RefreshTable` | Reload all fragments with the controller's current search, filters, advanced filters, sort, page, page size and column visibility |
| `RefreshRow` | Fetch the current body fragment and replace only the affected row or selected rows; falls back to a table refresh when an identifier cannot be matched |
| `RemoveRow` | Remove the affected row or selected rows from the current DOM |
| `None` | Keep the current table DOM unchanged |
| `Redirect` | Navigate to the `redirect` URL returned by the action response |

`RefreshRow` relies on the definition's `identifier` option, or the existing
`id`/`e_id` normalized-row fallback. It does not require the business
controller to render a custom row fragment.

`RemoveRow` intentionally changes only the current DOM. If deleting the last
row should recalculate pagination or totals, prefer `RefreshTable`.

### Versioned JSON response

Ajax action routes return JSON using contract version `1`:

```json
{
  "version": 1,
  "ok": true,
  "message": "User archived.",
  "errors": [],
  "redirect": null
}
```

The bundle provides `AjaxActionResponse` so host applications do not need to
assemble that payload manually:

```php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Zhortein\DatatableBundle\Response\AjaxActionResponse;

#[Route('/users/{id}/archive', name: 'app_user_archive', methods: ['PATCH'])]
public function archive(Request $request, User $user): Response
{
    if (!$this->isCsrfTokenValid(
        'zhortein_datatable_action_archive',
        $request->request->getString('_token'),
    )) {
        return AjaxActionResponse::failure(
            message: 'The security token is invalid.',
            status: 403,
        );
    }

    if ($user->isProtected()) {
        return AjaxActionResponse::failure(
            errors: [[
                'message' => 'The protected account cannot be archived.',
                'code' => 'protected_account',
            ]],
            status: 409,
        );
    }

    // Authorize and perform the business operation.

    if (!$request->isXmlHttpRequest()) {
        return $this->redirectToRoute('app_user_index');
    }

    return AjaxActionResponse::success('User archived.');
}
```

For the redirect strategy:

```php
return AjaxActionResponse::redirect(
    $this->generateUrl('app_user_show', ['id' => $user->getId()]),
    'User created.',
);
```

The response fields are:

| Field | Type | Contract |
|---|---|---|
| `version` | integer | Must be `1` |
| `ok` | boolean | `true` for success, `false` for validation/business failure |
| `message` | string or `null` | Optional neutral user feedback |
| `errors` | list | Optional errors with `message` and optional `code`/`field` |
| `redirect` | string or `null` | Required by the `Redirect` strategy |

Use a 2xx status for success and a 4xx/5xx status for failure. Invalid,
unversioned or non-JSON responses are rejected and shown through the
datatable's accessible error area.

The rendered fallback remains a real link or form. For non-GET forms, the
browser request is `POST` with the configured method in `_method`, matching
Symfony's standard method override, and the CSRF token remains in `_token`.
Without JavaScript, the same route can detect the absence of
`X-Requested-With: XMLHttpRequest` and return its normal redirect/HTML
response, as in the example above. Apply the same split to validation and
business failures when progressive enhancement is required.

### Loading, confirmation and duplicate prevention

During execution, the action receives `aria-busy` and `is-loading`; submit
controls are disabled and restored afterward. A second activation of the same
action is ignored while its request is pending.

The existing native or Bootstrap-modal confirmation is applied before the
Ajax request. Ajax actions render `data-turbo="false"` so Turbo does not race
the controller, while remaining valid progressive-enhancement links/forms.

### Lifecycle events

The datatable root dispatches bubbling custom events:

| Event | Detail |
|---|---|
| `zhortein-datatable:action:before` | Action metadata; cancellable with `preventDefault()` |
| `zhortein-datatable:action:success` | Metadata, parsed `payload` and `response` |
| `zhortein-datatable:action:error` | Metadata, `error`, and the available `payload`/`response` |
| `zhortein-datatable:action:complete` | Action metadata after either outcome |

Common metadata contains `action`, `strategy`, `target`, `selectedIds` and
`rowIdentifiers`. Applications may use these events for toasts, telemetry or
additional UI behavior without replacing the controller:

```js
document.addEventListener('zhortein-datatable:action:success', (event) => {
    showToast(event.detail.payload.message);
});
```

The built-in success/error areas remain the dependency-free fallback when no
application notification system listens to the events.

### Security boundary

Ajax changes transport and presentation only. The target controller must
still:

- authorize the action and every selected row;
- validate identifiers and business invariants;
- validate the generated CSRF token for state-changing methods;
- avoid putting secrets or sensitive exception details in the response.

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
