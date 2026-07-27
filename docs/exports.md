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

## Export Modes

Exports respect the current state of the datatable:
- Search queries
- Simple filters
- **Advanced filter expressions**
- Sorting
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
- [Architecture](architecture/overview.md)
