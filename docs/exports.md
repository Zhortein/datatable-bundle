# Server-side Exports

`zhortein/datatable-bundle` provides server-side exports in CSV and XLSX formats. These exports are generated server-side and do not rely on client-side plugins.

## Status

Currently implemented:
-   **Formats**: CSV (native), XLSX (via optional OpenSpout dependency).
-   **Modes**: 
    -   `current`: Exports only the currently visible page.
    -   `full`: Exports the entire filtered dataset (pagination disabled).
-   **Features**: Toolbar export dropdown, custom export URLs, and per-column export policies.

Not implemented yet:
-   Asynchronous/Background exports.
-   Export progress UI.
-   Export size limits configuration.
-   Streamed Doctrine iterators (currently loads full result set into memory).

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

The current implementation is **synchronous**. The data is loaded into memory before being written to the response.

### Recommendations for Large Datasets
-   Encourage users to apply filters before performing a "full" export.
-   Keep the number of exported columns to a minimum.
-   Be cautious with "full" exports on datasets with more than 10,000 rows.
-   For millions of rows, synchronous export is not recommended and may hit PHP memory or execution time limits.

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

The export endpoint does not include a built-in authorization layer beyond the route protection. Host applications should protect the `zhortein_datatable_export` route according to their security requirements.

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
- [Architecture](architecture/overview.md)
