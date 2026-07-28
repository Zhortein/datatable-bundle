# Security

The bundle treats every fragment, filter, sort and export request as untrusted
input. Datatable definitions remain the server-side authority.

## Built-in request boundaries

Requests created through `DatatableRequestFactory` enforce these boundaries:

- pagination cannot be disabled by an HTTP `options` payload;
- pages are capped at `10,000` and page sizes keep the configured
  `max_page_size` limit;
- global search strings are capped at 512 characters;
- simple filters are capped at 50 entries, 100 scalar values per entry and
  2,048 characters per string value;
- advanced-filter transport is bounded by depth and node count before state
  normalization;
- Search Builder expressions accept only `AND` or `OR`, at most 50 conditions,
  three group levels, 100 values per condition and 2,048 characters per string;
- submitted filters, sorts and column state are reduced to fields declared by
  the resolved `DatatableDefinition`;
- sort directions and filter operators are backed by enums;
- only the bounded `http_cursor` transport value can become a client-provided
  request option.

Custom controllers should call:

```php
$datatableRequest = $requestFactory->createFromRequest($request, $definition);
```

Passing the definition is important: it applies the declaration allowlists
before a custom provider receives the request.

Server-owned provider options can still be supplied explicitly through
`createFromState(..., options: [...])`. They must never be copied wholesale
from query parameters, JSON or form payloads.

## Doctrine query safety

The built-in Doctrine provider:

- resolves filter and sort fields from the definition;
- checks mapped metadata before adding user-controlled criteria;
- binds every filter and search value as a Doctrine parameter;
- never accepts raw DQL, SQL, field expressions or logical operators from the
  browser.

Permanent filters are application-owned authorization boundaries. When a user
has no tenant, customer or other business scope, application code must deny the
request or apply a condition that returns no rows. An empty scope must never
mean "do not add a filter".

## Endpoint authorization

The bundle routes are generic infrastructure. Protect them with the host
application firewall and `access_control` rules. Apply the same business voter
or scope rules to the page route, fragment route and export routes.

Exports additionally support
`DatatableExportAuthorizationCheckerInterface`. Replace the permissive default
when export permission differs by datatable, user or business scope.

Action visibility is not authorization. The target action controller must
enforce its own voter and domain rules even when the button is hidden.

## HTML and custom templates

Built-in cells render through Twig with auto-escaping. Stored strings are not
returned as raw JSON for client-side HTML insertion.

Custom cell templates are trusted application code. Keep auto-escaping enabled
and use `|raw` only for HTML that was produced by a trusted server-side
sanitizer.

## Export safety

Synchronous and asynchronous exports enforce server-side row limits and
authorization hooks. CSV string cells beginning with spreadsheet formula
markers (`=`, `+`, `-` or `@`, including after leading control/space
characters) are prefixed with an apostrophe. Numeric negative values remain
numeric.

Keep export limits aligned with the deployment timeout and database capacity.

## Reporting a vulnerability

Do not publish exploitable details in a public issue before a fix is available.
Contact the maintainer through the private security-reporting channel exposed
by the GitHub repository when available.
