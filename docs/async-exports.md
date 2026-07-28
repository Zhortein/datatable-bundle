# Asynchronous export jobs

Asynchronous exports extend the bounded streaming pipeline for datasets that
should not keep an HTTP request open. The feature is opt-in and does not replace
the synchronous CSV/XLSX endpoints.

## Design boundaries

The bundle defines the job protocol but does not impose:

- a Doctrine entity;
- a database schema;
- a local filesystem;
- an object-storage provider;
- a user class.

Applications replace the repository, result storage and owner resolver
contracts. The bundled in-memory implementations exist for deterministic tests
and same-process examples only. They must not be used by a Messenger worker
because another PHP process cannot see their state.

Jobs use five states:

| State | Meaning |
|---|---|
| `pending` | Stored and waiting for a worker |
| `running` | Claimed by a worker |
| `completed` | Result metadata is available |
| `failed` | The configured attempt budget was exhausted |
| `expired` | The result passed its retention deadline and was removed |

## Enable the endpoints

```yaml
# config/packages/zhortein_datatable.yaml
zhortein_datatable:
    export:
        batch_size: 500
        async:
            enabled: true
            max_rows: 250000
            ttl: 86400
            max_attempts: 3
            format_limits:
                csv: 250000
                xlsx: 100000
```

`max_rows` and `format_limits` follow the same trusted precedence as synchronous
limits. A definition-level `setExportLimit()` remains authoritative. The
submission endpoint counts filtered rows before creating a job, and the worker
also stops a provider that yields more rows than the stored limit.

## Required host services

Provide production implementations and replace these aliases:

```yaml
# config/services.yaml
services:
    App\Datatable\Export\DatabaseExportJobRepository: ~
    App\Datatable\Export\ObjectStorageExportJobResultStorage: ~
    App\Datatable\Export\CurrentUserExportJobOwnerResolver: ~

    Zhortein\DatatableBundle\Contract\ExportJobRepositoryInterface:
        alias: App\Datatable\Export\DatabaseExportJobRepository

    Zhortein\DatatableBundle\Contract\ExportJobResultStorageInterface:
        alias: App\Datatable\Export\ObjectStorageExportJobResultStorage

    Zhortein\DatatableBundle\Contract\ExportJobOwnerResolverInterface:
        alias: App\Datatable\Export\CurrentUserExportJobOwnerResolver
```

The owner resolver returns a stable opaque string. It may represent a user, a
tenant plus user, or another application scope:

```php
<?php

declare(strict_types=1);

namespace App\Datatable\Export;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Zhortein\DatatableBundle\Contract\ExportJobOwnerResolverInterface;

final readonly class CurrentUserExportJobOwnerResolver implements ExportJobOwnerResolverInterface
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function resolve(Request $request): ?string
    {
        $user = $this->security->getUser();

        return $user instanceof \App\Entity\User
            ? hash('sha256', 'datatable-export:'.$user->getId())
            : null;
    }
}
```

Do not return an email address, display name, sequential database identifier or
other value that should appear in logs or URLs. The job identifier remains a
separate random opaque value.

## Symfony Messenger

Install Messenger in the host application:

```bash
composer require symfony/messenger
```

When Messenger is available, the bundle automatically dispatches
`RunExportJobMessage` through `messenger.default_bus` and registers
`RunExportJobHandler`. Route that message to an asynchronous transport:

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        transports:
            datatable_exports: '%env(MESSENGER_TRANSPORT_DSN)%'
        routing:
            Zhortein\DatatableBundle\Export\Job\RunExportJobMessage: datatable_exports
```

For a named bus, replace `ExportJobDispatcherInterface` with a
`MessengerExportJobDispatcher` configured with that bus.

The message contains only the opaque job identifier. The worker reloads the
canonical request from the repository, rebuilds the datatable definition,
restores only allowlisted context values, applies the stored locale and streams
rows into a CSV or XLSX artifact.

### Retry semantics

The job attempt counter is independent of Messenger transport metadata:

1. a worker changes `pending` to `running`;
2. a failure below `max_attempts` returns the job to `pending` and throws
   `RetryableExportJobException`;
3. Messenger may redeliver the same message;
4. the last failed attempt stores `failed` with a non-sensitive public code;
5. delivery of a terminal job is a no-op.

Align Messenger's retry limit with or above `max_attempts`. Repository
implementations must implement `claim()` as an atomic `pending` → `running`
compare-and-swap (or an equivalent lock) and return `null` to concurrent
consumers. `create()` must atomically enforce the owner/idempotency-key
uniqueness and return the already stored job when two submissions race.

## HTTP lifecycle

Submit a canonical export request:

```http
POST /_zhortein/datatable/users/export-jobs/csv?mode=full&search=alice
Idempotency-Key: users-alice-2026-07-28
```

The response is private JSON with HTTP `202`:

```json
{
  "identifier": "opaque_job_identifier",
  "status": "pending",
  "attempts": 0,
  "createdAt": "2026-07-28T08:00:00+00:00",
  "updatedAt": "2026-07-28T08:00:00+00:00",
  "expiresAt": "2026-07-29T08:00:00+00:00",
  "failureCode": null,
  "result": null
}
```

Read status and download the result:

```text
GET /_zhortein/datatable/export-jobs/{identifier}
GET /_zhortein/datatable/export-jobs/{identifier}/download
```

A pending/running/failed download returns `409`; an expired result returns
`410`. Unknown identifiers and jobs owned by another scope both return `404`.

An `Idempotency-Key` may contain 1 to 255 printable characters. Repeating the
same owner, key and canonical request returns the existing job. Reusing the key
for different filters, sorting, columns, format, mode or context returns `409`.
A repeated submission may dispatch another copy of the small identifier-only
message when the existing job is still pending. Atomic `claim()` makes these
at-least-once deliveries safe and also lets a client recover from an earlier
transport outage.

## Security

The bundle performs two independent checks:

1. `DatatableExportAuthorizationCheckerInterface` at submission, before count
   or provider row access;
2. the same checker immediately before download.

Every status and download lookup also resolves the current opaque owner and
uses constant-time ownership comparison. A valid identifier never grants access
by itself.

The saved job contains the normalized `DatatableRequest`, format, mode, locale,
instance and browser-safe context values. It does not serialize the Symfony
request, security token, datatable definition or server-only context.

Protect the routes with the host firewall as well. Replace the backward-
compatible allow-all export checker whenever permissions depend on roles,
tenant, format, mode or business context.

## Expiration and cleanup

Submission and completed results receive a deadline from
`ExportJobExpiryPolicyInterface`. `FixedExportJobExpiryPolicy` uses the
configured `ttl`; applications may replace it.

Status/download lazily expires a job and deletes its result. A scheduled host
task should also call:

```php
$cleaned = $exportJobCleanup->cleanup(limit: 100);
```

`ExportJobCleanup` loads expired jobs in bounded batches, removes stored result
content and persists the `expired` state. Run it regularly through Scheduler,
Messenger, cron or the application's existing maintenance command.
Repositories should not return actively `running` jobs from `findExpired()`;
recovery of a worker process that died while holding a claim is a separate
host-specific lease or watchdog policy.

## Storage contract

Workers produce an `ExportArtifact` backed by a temporary local file. The
result storage copies or uploads it and returns immutable
`ExportJobResultMetadata`. The temporary file is then deleted.

Downloads consume `ExportJobResultStorageInterface::read()` as chunks. A
filesystem adapter can yield fixed-size reads; an object-store adapter can yield
the provider's response stream. Neither path requires the bundle to materialize
the complete result.

See [server-side exports](exports.md), [export architecture](architecture/exports.md)
and [routes](routes.md).
