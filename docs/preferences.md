# Persistent datatable preferences

The bundle can persist user-specific datatable defaults through a PSR-6 cache
pool without depending on an application `User` entity, Symfony Security or a
session.

Persistent preferences and [named saved views](saved-views.md) are deliberately
different:

- preferences are one implicit default per user and scoped datatable;
- named views are explicit, named snapshots that users can create and select;
- a named default view takes precedence over implicit preferences;
- a valid URL state takes precedence over both.

## Stored values

The built-in adapter stores only:

- page size;
- ordered sort criteria;
- visible columns;
- hidden columns;
- simple filters explicitly declared as safe for preference storage.

It never stores:

- global search;
- Search Builder expressions;
- permanent filters;
- server-only context;
- filters that are not explicitly preference-safe;
- the current page.

Permanent filters continue to apply in the provider after preferences are
resolved. A stored preference therefore cannot weaken tenant, ownership,
soft-delete or other business constraints.

## Install the Cache adapter

Enable the adapter and select a PSR-6 pool:

```yaml
# config/packages/zhortein_datatable.yaml
zhortein_datatable:
    preferences:
        enabled: true
        cache_pool: cache.app
        ttl: 31536000
        schema_version: '1'
```

`cache_pool` must reference a service implementing
`Psr\Cache\CacheItemPoolInterface`. Symfony cache pools, including `cache.app`,
implement this contract.

The null provider remains the default when `preferences.enabled` is false.

## Resolve an opaque user identity

The bundle never reads the security token or session implicitly. The host
application must implement:

```php
<?php

declare(strict_types=1);

namespace App\Datatable;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Zhortein\DatatableBundle\Contract\DatatablePreferenceIdentityResolverInterface;

final readonly class DatatablePreferenceIdentityResolver implements DatatablePreferenceIdentityResolverInterface
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function resolvePreferenceOwnerIdentifier(Request $request): ?string
    {
        $user = $this->security->getUser();

        if (null === $user) {
            return null;
        }

        // Return a stable opaque identifier. Do not return an email address.
        return (string) $user->getId();
    }
}
```

Alias the contract:

```yaml
services:
    App\Datatable\DatatablePreferenceIdentityResolver: ~

    Zhortein\DatatableBundle\Contract\DatatablePreferenceIdentityResolverInterface:
        alias: App\Datatable\DatatablePreferenceIdentityResolver
```

Returning `null` disables preference access for that request. Enabling the
preference controls without a resolved owner fails explicitly instead of
falling back to shared or anonymous storage.

## Enable the controls

Enable save/reset controls on a rendered datatable:

```twig
{{ zhortein_datatable('users', {
    instance: 'admin-users',
    preferences: true,
    preferencesScope: 'tenant-a'
}) }}
```

Runtime options:

| Option | Type | Purpose |
|---|---:|---|
| `preferences` | boolean | Renders the save/reset controls and endpoint metadata |
| `preferencesUrl` | string | Overrides the generated save/reset endpoint |
| `preferencesScope` | string | Adds an application namespace, for example a tenant identifier |
| `preferencesLocale` | string | Overrides the current request locale in the storage scope |

The controller emits:

- `zhortein-datatable:preference:save`;
- `zhortein-datatable:preference:reset`;
- `zhortein-datatable:preference:error`.

Reset removes the persisted defaults. It does not mutate the currently
displayed table; bundle/runtime defaults apply on the next page load unless a
URL state or named default view takes precedence.

## Declare preference-safe filters

Filters are excluded unless the definition explicitly opts in:

```php
$definition->addFilter(
    name: 'status',
    field: 'e.status',
    type: FilterType::Choice,
    choices: [
        'Enabled' => 'enabled',
        'Disabled' => 'disabled',
    ],
    preferenceSafe: true,
);
```

Use `preferenceSafe: true` only for values that are appropriate for long-lived
per-user storage. Do not enable it for secrets, personal data, temporary
authorization tokens or filters whose value reveals a protected business
scope.

Advanced filters are not persisted by the built-in adapter.

## Collision-free cache keys

Cache keys are opaque hashes partitioned by:

- owner identifier;
- datatable name;
- rendered instance;
- original route/path scope;
- application namespace;
- locale;
- signed browser-context fingerprint;
- schema version.

The raw user identifier and context values are not embedded in cache keys.
This prevents collisions between users, tenants, routes, locales and multiple
instances of the same datatable.

## Schema invalidation

The schema version includes a deterministic fingerprint of:

- declared column names;
- column visibility and sorting capabilities;
- preference-safe filter names, fields and types;
- `preferences.schema_version`.

Changing a column or safe-filter definition automatically moves reads to a new
cache namespace. Increment `schema_version` when application semantics change
without changing those declarations:

```yaml
zhortein_datatable:
    preferences:
        schema_version: '2'
```

Old entries become unreachable and expire according to `ttl`. Malformed or
stale payloads are ignored and deleted.

## Precedence

The complete precedence is:

```text
URL state
> selected/default named view
> runtime Twig options
> stored preference
> bundle defaults
```

An explicit runtime `sorts: []` clears stored sorting. Runtime page size,
column visibility and filter values similarly override stored values. URL
state is restored by Stimulus after the initial HTML is rendered and therefore
remains authoritative.

## Cache failure behavior

Read failures degrade to an empty preference so a cache outage does not prevent
the datatable from rendering. Save/reset failures return HTTP `503` with the
stable JSON error code `storage_unavailable` and trigger the frontend error
event.

## Existing custom providers

`DatatablePreferenceProviderInterface` remains backward-compatible:

```php
interface DatatablePreferenceProviderInterface
{
    public function getPreference(string $datatableName): DatatablePreference;
}
```

Existing application providers continue to work for read-side defaults.
Scoped persistence extends this contract through:

- `ScopedDatatablePreferenceProviderInterface`;
- `WritableDatatablePreferenceProviderInterface`.

This preserves 1.x implementations while allowing the built-in cache adapter
to support collision-free reads, save and reset operations.

## Column visibility

Runtime column visibility remains available independently of persistence:

```twig
{{ zhortein_datatable('users', {
    visibleColumns: ['e.email', 'e.displayName'],
    hiddenColumns: ['e.createdAt']
}) }}
```

Definition-hidden columns always remain hidden. The frontend transports
visible and hidden column names through the canonical state model and never
serializes definition-hidden columns.

Column ordering and drag-and-drop reordering are not implemented.
