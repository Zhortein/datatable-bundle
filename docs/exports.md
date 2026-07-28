# Server-side Exports

`zhortein/datatable-bundle` provides server-side exports in CSV and XLSX formats. These exports are generated server-side and do not rely on client-side plugins.

## Status

Currently implemented:
-   **Formats**: CSV (native), XLSX (via optional OpenSpout dependency).
-   **Modes**: 
    -   `current`: Exports only the currently visible page.
    -   `full`: Exports the entire filtered dataset (pagination disabled).
-   **Features**: Toolbar export dropdown, custom export URLs, and per-column export policies.
-   **Safeguards**: Server-side row limits, provider preflight counting and a replaceable authorization checker.
-   **Streaming**: Bounded Doctrine batches, direct CSV output and incremental OpenSpout XLSX generation.
-   **Background jobs**: Optional storage-agnostic CSV/XLSX jobs with Messenger integration.

Not implemented yet:
-   Export progress UI.

## Export Formats

### CSV (Default)
CSV is the default dependency-free format. It uses PHP built-ins for escaping and UTF-8 encoding.

### XLSX (Optional)
XLSX support requires the `openspout/openspout` library.

```bash
composer require openspout/openspout
```

To enable XLSX in the UI, update your `zhortein_datatable` call:

```twig
{{ zhortein_datatable('users', {
    exportFormats: ['csv', 'xlsx']
}) }}
```

### Header translations

CSV and XLSX use the same declarative translation contract as the rendered
table. When a definition declares a domain, explicit column labels are treated
as translation keys and resolved with Symfony's current request locale:

```php
$definition
    ->setTranslationDomain('admin')
    ->addColumn('e.email', label: 'users.columns.email')
;
```

The application catalog may then contain:

```yaml
# translations/admin.fr.yaml
users:
    columns:
        email: Adresse e-mail
```

The table header, column-visibility control and exported header will all use
`Adresse e-mail` for a French request. With no translation domain, declared
labels remain literal. A missing label falls back to the column name and that
fallback is never looked up as a translation key.

## Export Modes

Exports respect the current state of the datatable:
- Search queries
- Simple filters
- **Advanced filter expressions**
- Ordered multi-column sorting
- Runtime column visibility

State restored from a namespaced page URL is translated back into the existing
export query parameters before navigation. Custom export URLs therefore receive
the same state as built-in routes. See [URL state and browser
history](url-state.md).

| Mode | Behavior |
|---|---|
| `current` | Exports the rows of the current page. |
| `full` | Exports all rows matching the current filters, ignoring pagination. |

**Note**: "Full" export means the *filtered* dataset, not the raw database table.

The complete sort order is sent to both current and full exports. The primary
criterion is also emitted as the historical `sortField`/`sortDirection` pair
for custom-route compatibility. See [multi-column sorting](sorting.md).

## Performance and Memory Constraints

Exports remain synchronous HTTP operations, but the built-in Doctrine provider
and CSV/XLSX writers use an additive bounded-memory pipeline. The controller
never materializes the complete streamed dataset.

### Export row limits

The bundle defaults to a maximum of 10,000 rows per synchronous export:

```yaml
zhortein_datatable:
    export:
        max_rows: 10000
        batch_size: 500
        format_limits:
            csv: 10000
            xlsx: 5000
```

Before invoking CSV or XLSX writers, the controller asks the provider for the
filtered row count. Full exports compare the complete filtered count with the
effective limit. Current-page exports compare only the rows remaining on that
page. A request exactly at the limit is accepted; a request above it returns
HTTP `413` with a translated message that discloses the configured limit but
not the filtered data count.

Trusted definitions may override the limit:

```php
use Zhortein\DatatableBundle\Enum\ExportFormat;

$definition
    ->setExportLimit(2500)
    ->setExportLimit(1000, ExportFormat::Xlsx)
;
```

Definition overrides take precedence over global per-format and default
limits. Client parameters cannot change any limit.

Built-in Array and Doctrine providers implement
`ExportRowCountProviderInterface`. A custom provider used for synchronous
exports must implement the same additive capability:

```php
use Zhortein\DatatableBundle\Contract\ExportRowCountProviderInterface;

final class ApiDataProvider implements DataProviderInterface, ExportRowCountProviderInterface
{
    public function countExportRows(
        DatatableDefinition $definition,
        DatatableRequest $request,
    ): int {
        // Return the exact filtered count or a conservative upper bound.
    }
}
```

The count must ignore pagination and sorting while applying permanent context,
search, simple filters and advanced filters. Providers without this capability
receive HTTP `422` before `getData()` is called; the bundle will not load a
full dataset merely to discover its size.

### Streaming capabilities

Streaming is selected only when both the provider and writer expose the
additive capabilities:

- `StreamingDataProviderInterface`;
- `StreamingExportWriterInterface`.

`DataProviderInterface`, `ExportWriterInterface` and their existing signatures
are unchanged. If either capability is absent, the controller uses the
historical `DatatableResult` path. This keeps custom 1.x providers and writers
compatible. The built-in Array provider deliberately uses this fallback
because its source dataset is already an in-memory array.

The Doctrine provider fetches scalar projections in bounded batches. It
preserves permanent context, simple and advanced filters, search, ordered
sorting and current/full pagination semantics. Scalar hydration does not grow
Doctrine's UnitOfWork, and the provider never clears the application's entity
manager.

Configure the maximum provider batch:

```yaml
zhortein_datatable:
    export:
        batch_size: 500
```

The accepted range is `1` through `10000`. The value is trusted server-side
configuration and cannot be overridden by the export URL.

### Custom streaming provider

A custom streaming provider still implements the regular data and count
contracts. The streaming capability yields normalized `ExportRow` values:

```php
use Zhortein\DatatableBundle\Contract\DataProviderInterface;
use Zhortein\DatatableBundle\Contract\ExportRowCountProviderInterface;
use Zhortein\DatatableBundle\Contract\StreamingDataProviderInterface;
use Zhortein\DatatableBundle\Export\ExportRow;
use Zhortein\DatatableBundle\Export\ExportStreamContext;

final class ApiDataProvider implements
    DataProviderInterface,
    ExportRowCountProviderInterface,
    StreamingDataProviderInterface
{
    public function streamExportRows(
        DatatableDefinition $definition,
        DatatableRequest $request,
        ExportStreamContext $context,
    ): iterable {
        foreach ($this->api->pages($request, $context->getBatchSize()) as $page) {
            foreach ($page as $item) {
                if ($context->isCancelled()) {
                    return;
                }

                yield new ExportRow(
                    values: $this->normalize($item),
                    source: $item,
                );
            }
        }
    }
}
```

The request is the same canonical request used by the normal provider path.
For `full` mode pagination is disabled; for `current` mode page and offset are
preserved. Providers must keep sorting stable between batches and should check
`ExportStreamContext::isCancelled()` before remote calls and between rows.

`ExportRow::getSource()` feeds the existing server-side `CellContext`, so
computed values behave identically in streamed and materialized exports.

### Cancellation, disconnects and late failures

The default `ExportCancellationInterface` implementation reports native PHP
client disconnects. Applications may replace the service alias when they have
an additional cancellation signal.

Cancellation is cooperative: providers and writers stop at the next check.
CSV may therefore end after its last complete row. XLSX closes its OpenSpout
writer, but does not send the generated file after cancellation. No success
footer or synthetic row is added.

An exception before the stream callback runs can still become a normal error
response. An exception raised after response streaming starts is rethrown and
the download is considered incomplete; it is never hidden or converted into a
successful partial export. Applications should log such exceptions at the
HTTP runtime boundary.

### Recommendations for Large Datasets
-   Encourage users to apply filters before performing a "full" export.
-   Keep the number of exported columns to a minimum.
-   Keep synchronous limits aligned with request timeouts, even though memory is bounded.
-   For long-running remote sources, enable [asynchronous export jobs](async-exports.md) and configure a persistent repository/result storage.

## Customization

### Toolbar Controls
Exports are enabled by default. You can disable them or provide custom URLs:

```twig
{{ zhortein_datatable('users', {
    export: false, // Disable all exports
    exportUrl: path('custom_export'), // Custom URL for default format (CSV)
    exportUrls: {
        csv: path('custom_csv'),
        xlsx: path('custom_xlsx')
    }
}) }}
```

### Column Visibility

By default, exports respect both definition and runtime visibility. A column hidden in the UI is therefore omitted from CSV and XLSX files.

The nullable `exportable` argument can override this behavior per column:

| Value | Behavior |
|---|---|
| `null` | Follows definition and runtime visibility. This is the default. |
| `true` | Always exports the column, even when it is hidden. |
| `false` | Never exports the column, even when it is visible. |

For example, a technical reference can remain hidden in the table while still being included in exports:

```php
$definition->addColumn(
    name: 'e.internalReference',
    label: 'Internal reference',
    visible: false,
    exportable: true,
);
```

A visible sensitive column can be excluded explicitly:

```php
$definition->addColumn(
    name: 'e.privateNote',
    label: 'Private note',
    exportable: false,
);
```

The default is intentionally visibility-aware for backward compatibility and to prevent hidden technical or sensitive values from being exported after a bundle update without an explicit decision.

### Computed Values

Computed columns use the same `CellValueResolverInterface` service for Twig,
CSV and XLSX:

```php
$definition->addComputedColumn(
    name: 'account_summary',
    valueResolver: 'account_summary',
    label: 'Account',
    exportable: true,
);
```

The resolver receives the normalized row, optional provider source, row
identifier, column/table definitions and explicit `DatatableContext`.
Visibility and `exportable` policies are applied before resolution.

Twig templates are never written into export files. The resolver's return
value is normalized by the selected writer. Existing boolean `negate` remains
a display-only modifier.

Enum columns are resolved through the same locale-aware presentation contract
as Twig cells and filters. CSV/XLSX receive only the final human-readable
label; badge, color and icon markup never enters an export. See [enum
presentation](enum-presentation.md).

See [Cell Context and Computed Values](cell-context.md) for the resolver
contract and [the complete example](examples/computed-cell.md).

## Security

The default checker remains allow-all for backward compatibility. Host
applications should both protect the route and replace
`DatatableExportAuthorizationCheckerInterface` when access depends on the
datatable, user, format, mode, tenant or restored request state:

```php
use Symfony\Bundle\SecurityBundle\Security;
use Zhortein\DatatableBundle\Contract\DatatableExportAuthorizationCheckerInterface;
use Zhortein\DatatableBundle\Export\DatatableExportAuthorizationContext;

final readonly class DatatableExportAuthorizationChecker implements DatatableExportAuthorizationCheckerInterface
{
    public function __construct(private Security $security)
    {
    }

    public function isGranted(DatatableExportAuthorizationContext $context): bool
    {
        return $this->security->isGranted('DATATABLE_EXPORT', [
            'definition' => $context->getDefinition(),
            'format' => $context->getFormat(),
            'mode' => $context->getMode(),
            'state' => $context->getDatatableRequest(),
            'businessContext' => $context->getDatatableContext(),
            'instance' => $context->getInstance(),
            'child' => $context->isChildDatatable(),
        ]);
    }
}
```

Replace the default alias:

```yaml
services:
    Zhortein\DatatableBundle\Contract\DatatableExportAuthorizationCheckerInterface:
        alias: App\Security\DatatableExportAuthorizationChecker
```

Denied exports return HTTP `403` before provider counting. This ordering avoids
revealing row-count information to unauthorized callers. The context also
exposes the current Symfony `Request` for application attributes, but
authorization should never trust client-provided limits or counts.

Explicit browser-safe datatable context is signed and restored before the
provider builds an export. A valid token prevents tampering but does not
replace authorization or tenant-scope validation. See [explicit datatable
context](context.md).

## Related documentation

- [Doctrine provider](doctrine-provider.md)
- [Explicit datatable context](context.md)
- [URL state and browser history](url-state.md)
- [UI/UX customization](ui-ux.md)
- [Cell context and computed values](cell-context.md)
- [Configuration](configuration.md)
- [Architecture](architecture/overview.md)
