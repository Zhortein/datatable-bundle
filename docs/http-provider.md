# HTTP/API Provider

The built-in `http` provider is a protocol-neutral foundation for remote data
sources. It does not assume that every API uses the same parameter names,
pagination model or response envelope.

Symfony HttpClient is optional:

```bash
composer require symfony/http-client
```

When it is installed, the bundle wires `SymfonyHttpClientTransport`. An
application may replace `HttpTransportInterface` with another adapter.

## Definition

Declare the provider explicitly and attach a server-side configuration:

```php
<?php

declare(strict_types=1);

namespace App\Datatable;

use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\HttpPaginationStrategy;
use Zhortein\DatatableBundle\Provider\Http\HttpProviderCapabilities;
use Zhortein\DatatableBundle\Provider\Http\HttpProviderConfiguration;
use Zhortein\DatatableBundle\Provider\Http\HttpResponseMapping;
use Zhortein\DatatableBundle\Provider\HttpDataProvider;

#[AsDatatable(name: 'remote-users', provider: 'http')]
final class RemoteUserDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->addColumn('id', visible: false)
            ->addColumn('displayName', label: 'Name')
            ->addColumn('email', label: 'Email')
            ->setOption(HttpDataProvider::OPTION_CONFIGURATION, new HttpProviderConfiguration(
                endpoint: 'https://api.example.test/users',
                capabilities: new HttpProviderCapabilities(
                    paginationStrategies: [HttpPaginationStrategy::Page],
                    search: true,
                    sorting: true,
                    simpleFilters: true,
                ),
                fieldMap: [
                    'displayName' => 'profile.name',
                ],
                parameterNames: [
                    'page_size' => 'per_page',
                ],
                responseMapping: new HttpResponseMapping(
                    rowsPath: 'data.items',
                    identifierPath: 'uuid',
                    totalItemsPath: 'meta.total',
                ),
            ))
        ;
    }
}
```

Configuration objects stay on the server. Endpoint headers, context values and
transport details are never added to the datatable frontend state.

## Capability contract

`HttpProviderCapabilities` is authoritative. The default mapper rejects a
search, sort, filter, advanced-filter or export request if the API did not
declare that operation. This prevents a saved view or URL state from implying
that the remote server applied an operation it actually ignored.

Supported pagination strategies are:

- `Page`: sends page and page-size parameters;
- `Offset`: sends offset and limit parameters;
- `Cursor`: sends an opaque cursor and limit.

Page and offset strategies integrate with the built-in numbered pagination.
Cursor metadata is exposed in `DatatableResult::getMetadata()` for custom
clients. A cursor-aware client must pass the selected token as the trusted
`http_cursor` request option; the built-in numbered pagination does not invent
or persist cursor chains.

## Request mapping

The default mapper supports `GET` query parameters and `POST` JSON bodies. It
maps:

- pagination;
- ordered sorts;
- global search;
- permanent and user filters;
- nested advanced filters;
- explicitly allowlisted datatable context.

`fieldMap` maps local datatable fields to remote field paths.
`operatorMap` maps bundle operator values such as `eq`, `gt` or `contains` to
API-specific names. `parameterNames` changes envelope keys without replacing
the complete mapper.

Only keys listed in `contextKeys` leave the application. This allowlist is
separate from browser-safe context because server-to-server APIs may need a
tenant or business scope that must never reach JavaScript.

For a protocol that cannot be represented by these options, inject a service
implementing `HttpRequestMapperInterface` into the datatable and set it through
`HttpDataProvider::OPTION_REQUEST_MAPPER`.

## Response mapping

`HttpResponseMapping` uses dot-separated paths for:

- rows;
- row identifiers;
- total and filtered counts;
- next and previous cursors;
- the `has next page` flag.

Columns are projected from the mapped remote rows. If no declared column path
is present, the complete associative row is retained for custom integrations.

Replace `HttpResponseMapperInterface` through
`HttpDataProvider::OPTION_RESPONSE_MAPPER` when an API needs transformations
beyond path mapping.

Malformed JSON, invalid shapes and non-2xx statuses produce normalized
provider exceptions. Raw response bodies, credentials and endpoint URLs are
not copied into public error messages.

## Timeout, retries and cancellation

`HttpProviderConfiguration` defines:

- a per-request timeout;
- a bounded maximum attempt count;
- the status codes eligible for retry;
- an optional cooperative `HttpRequestCancellationInterface`.

The Symfony adapter retries only transport failures and explicitly configured
statuses. It does not retry every application error. A custom transport must
honor the same immutable `HttpTransportRequest` constraints.

## Exports

Remote exports are disabled by default. Enable them only when the API:

- declares `exports: true`;
- declares exact counts;
- can return stable pages under the active filters and sorts.

The provider then implements guarded preflight counting and streams pages into
CSV/XLSX writers. Page, offset and cursor strategies are supported. If exports
or exact counts are not declared, synchronous and asynchronous export
submission fails before row materialization.

Credentials must not be placed in browser state or mapper error messages.
Prefer a scoped Symfony HttpClient service or a custom transport for bearer
tokens and signed requests.

## Custom transport

Alias the public contract:

```yaml
services:
    App\Datatable\Transport\SignedApiTransport: ~

    Zhortein\DatatableBundle\Contract\HttpTransportInterface:
        alias: App\Datatable\Transport\SignedApiTransport
```

The transport receives an immutable `HttpTransportRequest` and returns an
`HttpTransportResponse`. It owns authentication, observability and any
infrastructure-specific cancellation mechanism.
