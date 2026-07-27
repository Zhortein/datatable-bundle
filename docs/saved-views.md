# Named saved views

Named views let an application store and restore a datatable state without
coupling the bundle to Doctrine, an application `User` entity, Symfony Security
or a particular persistence model.

The feature is opt-in. The bundle supplies:

- immutable view, metadata, state and scope objects;
- replaceable ownership, authorization and provider contracts;
- CSRF-protected JSON endpoints;
- an optional Bootstrap control rendered by the existing Twig function;
- Stimulus loading, creation, update, rename, default and delete behavior;
- optimistic concurrency through opaque revisions.

The host application remains responsible for durable storage and for deciding
who may perform each operation.

## Enable the controls

```twig
{{ zhortein_datatable('users', {
    instance: 'admin-users',
    savedViews: true,
    savedViewsScope: app.request.attributes.get('_route'),
    savedViewsLocale: app.request.locale
}) }}
```

`savedViewsScope` is an opaque application namespace. A route name is a useful
default when the same datatable appears in screens with different meanings.
`savedViewsLocale` keeps localized view names and filter choices isolated when
that is appropriate for the application.

Available options:

| Option | Default | Purpose |
|---|---:|---|
| `savedViews` | `false` | Enables the Bootstrap controls and JSON integration |
| `savedViewsUrl` | bundle view route | Replaces the built-in endpoint base URL |
| `savedViewsScope` | `default` | Adds an application-defined scope |
| `savedViewsLocale` | `und` | Adds a locale scope |
| `savedViewsIncludePage` | `false` | Explicitly stores the current page |

The signed browser-safe datatable context is fingerprinted automatically. Its
values are not stored in view metadata. The resulting scope distinguishes:

- datatable name;
- rendered instance;
- application namespace, such as a route;
- locale;
- signed contextual scope.

## State and precedence

A view reuses the public version 1 `DatatableState` model:

- global search;
- simple filters;
- advanced filter expression;
- sort field and direction;
- page size;
- visible and hidden columns.

The current page is reset to `1` unless `savedViewsIncludePage` is explicitly
enabled. Selections, server-only context, provider options and layout settings
are never persisted.

Initial state precedence is:

```text
URL state > named default view > Twig options > preferences > bundle defaults
```

A valid URL is therefore deterministic and never silently replaced by a user's
default view. Selecting another named view performs a normal successful
datatable refresh and writes the resulting canonical state to the page URL.

## Application integration

Three contracts separate identity, authorization and persistence:

```php
use Zhortein\DatatableBundle\Contract\DatatableViewAuthorizationCheckerInterface;
use Zhortein\DatatableBundle\Contract\DatatableViewOwnerResolverInterface;
use Zhortein\DatatableBundle\Contract\DatatableViewProviderInterface;
```

The defaults are deliberately safe:

- `NullDatatableViewOwnerResolver` returns no implicit owner;
- `DenyDatatableViewAuthorizationChecker` refuses every operation;
- `NullDatatableViewProvider` does not persist views.

Enabling the Twig controls without replacing these services cannot expose or
mutate data.

### Resolve an opaque owner

The resolver may use Symfony Security inside the host application. It returns
only an opaque string to the bundle:

```php
namespace App\Datatable\View;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Zhortein\DatatableBundle\Contract\DatatableViewOwnerResolverInterface;

final readonly class CurrentUserViewOwnerResolver implements DatatableViewOwnerResolverInterface
{
    public function __construct(private Security $security)
    {
    }

    public function resolveOwnerIdentifier(Request $request): ?string
    {
        $identifier = $this->security->getUser()?->getUserIdentifier();

        return null === $identifier
            ? null
            : hash('sha256', 'datatable-view-owner-v1'."\0".$identifier);
    }
}
```

The owner identifier is never returned by the JSON API and must not be embedded
in frontend attributes.

### Delegate authorization

The checker receives the operation, opaque owner, complete scope and, for
object operations, the loaded view:

```php
namespace App\Datatable\View;

use Zhortein\DatatableBundle\Contract\DatatableViewAuthorizationCheckerInterface;
use Zhortein\DatatableBundle\Enum\DatatableViewOperation;
use Zhortein\DatatableBundle\View\DatatableViewAuthorizationContext;

final readonly class DatatableViewAuthorizationChecker implements DatatableViewAuthorizationCheckerInterface
{
    public function isGranted(
        DatatableViewOperation $operation,
        DatatableViewAuthorizationContext $context,
    ): bool {
        return null !== $context->getOwnerIdentifier()
            && 'admin-users' === $context->getScope()->getInstance();
    }
}
```

An application may delegate this decision to its voters, roles or domain
authorization service. The bundle does not inject Symfony Security into its
contracts.

### Implement persistent storage

`DatatableViewProviderInterface` defines:

```text
list, load, create, rename, update, setDefault, delete
```

Every method receives `DatatableViewScope` and the opaque owner identifier.
Use `DatatableViewScope::getStorageKey()` as a stable partition key or map its
individual getters into application columns.

The provider returns immutable `DatatableView`, `DatatableViewMetadata` and
`DatatableViewState` objects. A provider may use Doctrine, an API or any other
storage without changing the bundle.

For rename, update, default and delete operations, compare
`expectedRevision` with the stored opaque revision atomically. Throw:

- `DatatableViewConflictException` for a stale revision or duplicate name;
- `DatatableViewNotFoundException` when the object no longer exists.

`InMemoryDatatableViewProvider` demonstrates the complete contract and is used
by the tests. It is process-local, is not the default and must not be used as
durable production storage.

Register host implementations:

```yaml
services:
    App\Datatable\View\CurrentUserViewOwnerResolver: ~
    App\Datatable\View\DatatableViewAuthorizationChecker: ~
    App\Datatable\View\PersistentDatatableViewProvider: ~

    Zhortein\DatatableBundle\Contract\DatatableViewOwnerResolverInterface:
        alias: App\Datatable\View\CurrentUserViewOwnerResolver

    Zhortein\DatatableBundle\Contract\DatatableViewAuthorizationCheckerInterface:
        alias: App\Datatable\View\DatatableViewAuthorizationChecker

    Zhortein\DatatableBundle\Contract\DatatableViewProviderInterface:
        alias: App\Datatable\View\PersistentDatatableViewProvider
```

## JSON API

The imported bundle routes expose:

| Operation | Method | Path |
|---|---|---|
| List | `GET` | `/_zhortein/datatable/{name}/views` |
| Load | `GET` | `/_zhortein/datatable/{name}/views/{viewIdentifier}` |
| Create | `POST` | `/_zhortein/datatable/{name}/views` |
| Rename/update/default | `PATCH` | `/_zhortein/datatable/{name}/views/{viewIdentifier}` |
| Delete | `DELETE` | `/_zhortein/datatable/{name}/views/{viewIdentifier}` |

Responses use JSON contract version `1`. Mutations require the CSRF token
rendered by the bundle. The Stimulus controller sends it through the
`X-CSRF-Token` header.

Map-like state fields such as `filters` and `advancedFilters` are emitted as
JSON objects, including when empty (`{}`). For backward compatibility, the
frontend also accepts an empty JSON array (`[]`) previously emitted for these
fields and normalizes it to an empty object. Non-empty arrays remain invalid.
List fields such as `visibleColumns` and `hiddenColumns` continue to use JSON
arrays.

A mutation conflict returns HTTP `409` and error code `conflict`. Missing views
return `404`; denied operations return `403`; invalid state or input returns
`400`. The UI keeps the current table state when a mutation fails.

## Frontend events

The datatable root dispatches bubbling events:

| Event | Detail |
|---|---|
| `zhortein-datatable:view:load` | `view`, `source` |
| `zhortein-datatable:view:create` | `view` |
| `zhortein-datatable:view:update` | `view` |
| `zhortein-datatable:view:rename` | `view` |
| `zhortein-datatable:view:default` | `view` |
| `zhortein-datatable:view:delete` | `view` |
| `zhortein-datatable:view:error` | `code`, `message` |

These are observation hooks. The bundle remains responsible for validating and
applying the versioned state.

## Security boundaries

- A context signature proves integrity; it does not replace authorization.
- The owner resolver must not trust an identifier sent by the browser.
- Provider queries must always partition by scope and owner.
- Mutation routes are CSRF-protected but still require authorization.
- View state is user-controlled input and remains subject to declared columns,
  filters, provider constraints and domain access rules.
- Do not store secrets in search or filter values.
