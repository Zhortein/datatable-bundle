# Server-side Exports

`zhortein/datatable-bundle` provides server-side exports in CSV and XLSX formats. These exports are generated server-side and do not rely on client-side plugins.

## Status

Currently implemented:
-   **Formats**: CSV (native), XLSX (via optional OpenSpout dependency).
-   **Modes**: 
    -   `current`: Exports only the currently visible page.
    -   `full`: Exports the entire filtered dataset (pagination disabled).
-   **Features**: Toolbar export dropdown, custom export URLs, column visibility awareness.

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
Exports respect runtime column visibility. Columns hidden in the UI will not be included in the exported file.

## Security

The export endpoint does not include a built-in authorization layer beyond the route protection. Host applications should protect the `zhortein_datatable_export` route according to their security requirements.

## Related documentation

- [Doctrine provider](doctrine-provider.md)
- [UI/UX customization](ui-ux.md)
- [Architecture](architecture/overview.md)
