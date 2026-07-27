# Cell context and computed values

Every body cell is rendered on the server through a stable `CellContext`.
Custom Twig templates keep the historical `column` and `value` variables and
also receive the normalized row, row identifier, source value, datatable
definition and explicit application context.

This contract supports richer badges, links and summaries without adding
presentation-only SQL columns or exposing source objects to JavaScript.

## Custom cell templates

Declare a template on a regular column:

```php
$definition->addColumn(
    name: 'e.status',
    label: 'Status',
    template: 'datatable/cell/status.html.twig',
);
```

The custom template can use:

```twig
{# templates/datatable/cell/status.html.twig #}
<span class="badge text-bg-info">
    {{ value }}
</span>

<small class="text-body-secondary">
    Row {{ row_identifier }} from {{ datatable.name }}
</small>
```

The same variables are available during the initial render and every Ajax
fragment refresh.

## Template variables

| Variable | Type | Meaning |
|---|---|---|
| `cell` | `CellContext` | Canonical server-side DTO for this cell. |
| `value` | `mixed` | Final rendered value. Existing templates remain compatible. |
| `column` | `ColumnDefinition` | Current column definition. |
| `column_label` | `string` | Final translated column label. |
| `row` | `array<string, mixed>` | Normalized provider projection. |
| `source` | `mixed` | Optional source attached by the provider; otherwise `null`. |
| `row_identifier` | `string|null` | Normalized configured identifier, `id` or `e_id`. |
| `datatable` | `DatatableDefinition` | Current datatable definition. |
| `datatable_context` | `DatatableContext` | Explicit server-side application context. |
| `translation_domain` | `string|null` | Definition translation domain. |
| `boolean_display_mode` | `string` | Resolved boolean presentation mode. |
| `boolean_true_icon` | `string|null` | Resolved icon for true values. |
| `boolean_false_icon` | `string|null` | Resolved icon for false values. |
| `enum_presentation` | `EnumPresentation|null` | Resolved label and optional badge, color and icon metadata for enum cells. |

`cell.value`, `cell.row`, `cell.source`, `cell.rowIdentifier`, `cell.column`,
`cell.definition` and `cell.datatableContext` expose the same data through the
DTO.

## Provider capabilities

| Provider | Normalized `row` | `source` |
|---|---|---|
| Array | The filtered, sorted and paginated associative array. | The same associative array. |
| Doctrine ORM | Scalar projection aliases such as `e_id` and `organization_name`. | `null`; root entities are never hydrated implicitly. |
| Custom | Provider-defined associative projection. | Optional array or object supplied explicitly through `DatatableResult`. |

Custom providers may pass server-only sources aligned with rows:

```php
return new DatatableResult(
    rows: $normalizedRows,
    sources: $domainObjects,
    page: $request->getPage(),
    pageSize: $request->getPageSize(),
    totalItems: $totalItems,
);
```

`sources` must be empty or contain exactly one value for each returned row.
They are consumed only by server-side rendering and exports.

## Computed columns

Use a named resolver when the visible/exported value is derived from selected
row fields or explicit context:

```php
use Zhortein\DatatableBundle\Cell\CellContext;
use Zhortein\DatatableBundle\Contract\CellValueResolverInterface;

final readonly class AccountSummaryResolver implements CellValueResolverInterface
{
    public function getName(): string
    {
        return 'account_summary';
    }

    public function resolve(CellContext $context): mixed
    {
        $row = $context->getRow();
        $email = (string) ($row['e_email'] ?? '');
        $locale = (string) $context->getDatatableContext()->get('locale', 'en');

        return sprintf('%s [%s]', $email, strtoupper($locale));
    }
}
```

Resolver services are autoconfigured when the host application keeps the
standard Symfony `autoconfigure: true` default. Manual registration uses:

```yaml
services:
    App\Datatable\Cell\AccountSummaryResolver:
        tags:
            - { name: zhortein_datatable.cell_value_resolver }
```

Declare the computed column:

```php
$definition
    ->addColumn('e.email', visible: false)
    ->addComputedColumn(
        name: 'account_summary',
        valueResolver: 'account_summary',
        label: 'Account',
        template: 'datatable/cell/account_summary.html.twig',
        type: 'string',
    )
;
```

Computed columns are deliberately non-sortable and non-searchable. A PHP
resolver cannot be translated safely into SQL or into the in-memory provider's
filter pipeline. Keep every dependency as a selected column; it may be hidden
from the UI.

## Export behavior

CSV and XLSX writers call the same named resolver with the same row, source,
identifier, definitions and `DatatableContext`.

- computed values are exported when normal column visibility/export policy
  includes the computed column;
- custom Twig templates are not rendered into export files;
- existing typed export normalization still applies after resolution;
- boolean `negate` remains a display-only modifier, preserving existing export
  behavior.

This keeps one business calculation for the table and its exports.

Enum cells use a parallel presentation contract. See [enum
presentation](enum-presentation.md) for the fallback order, rich metadata and
custom resolver extension point.

## Security and N+1 boundaries

`CellContext` is server-side only. The bundle never serializes `row`, `source`,
`datatable` or `datatable_context` into HTML attributes or JSON. Only the
resulting Twig HTML enters the fragment response.

A template or resolver can still print a value deliberately. Treat access to
`source`, definition options and server-only context as application code:

- add only values the template needs to `DatatableContext`;
- never print credentials, tokens or unrestricted personal data;
- keep normal route and provider authorization in place;
- do not attach full source objects from a custom provider unless necessary.

The Doctrine provider intentionally returns scalar projections and `source =
null`. This prevents a template from traversing lazy associations and causing
an implicit query per row. When a custom provider supplies objects, fetch all
required associations in the provider before building `DatatableResult`; a
resolver must never execute a repository query once per cell.

## Related documentation

- [Computed cell example](examples/computed-cell.md)
- [Theming and templates](theming.md)
- [Providers](providers.md)
- [Explicit datatable context](context.md)
- [Exports](exports.md)
- [Public API](public-api.md)
