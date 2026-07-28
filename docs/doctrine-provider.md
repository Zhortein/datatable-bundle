# Doctrine ORM Provider

The Doctrine ORM provider is the primary production-oriented data provider for `zhortein/datatable-bundle`. It allows you to build powerful, searchable, and sortable datatables backed by your database entities.

## Requirements

- **Doctrine ORM**: 3.4+ or 4.0+
- **DoctrineBundle**: 3.2+

## Basic Setup

To use the Doctrine provider, specify `provider: 'doctrine'` (optional if it's your default) and set the entity class.

```php
use App\Entity\User;
use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;

#[AsDatatable(name: 'users', provider: 'doctrine')]
final class UserDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition->setEntityClass(User::class);
        
        // Add columns using the main alias 'e'
        $definition->addColumn('e.id', visible: false);
        $definition->addColumn('e.email', label: 'Email');
    }
}
```

## Main Alias: `e`

The Doctrine provider always uses the alias `e` for the main entity. All scalar fields from the main entity must be prefixed with `e.`.

## Columns

### Scalar Columns
```php
$definition->addColumn('e.username', label: 'Username');
```

### Metadata-based Guessing
The bundle automatically detects Doctrine types (`string`, `numeric`,
`boolean`, `datetime`, `array` and backed `enum`) and applies the matching cell
templates and alignments.

Enrichment runs when the datatable definition is created, before the same
definition is consumed by initial Twig rendering, Ajax fragments, filters and
exports. It covers:

- fields on the main `e` alias;
- fields below explicitly declared mapped joins;
- chained mapped joins;
- fields below custom joins, using the declared target entity metadata.

An explicit column `type` always takes priority:

```php
$definition->addColumn(
    'organization.enabled',
    label: 'Organization status',
    type: 'string',
);
```

Computed columns, unknown fields and fields using undeclared aliases are left
untouched. Metadata enrichment never creates a join or traverses an
association implicitly.

### Computed Columns

`addComputedColumn()` derives a display/export value in PHP without selecting
the virtual column name:

```php
$definition
    ->addColumn('e.email', visible: false)
    ->addColumn('e.displayName', visible: false)
    ->addComputedColumn(
        name: 'account_summary',
        valueResolver: 'account_summary',
        label: 'Account',
        template: 'datatable/cell/account_summary.html.twig',
    )
;
```

Every dependency must remain in the scalar projection as a regular column. A
computed column is always non-searchable and non-sortable because its resolver
cannot be translated into DQL.

The Doctrine provider does not hydrate the root entity as a cell source. This
is deliberate: custom templates receive the selected scalar row and `source =
null`, so traversing a lazy association cannot introduce one hidden query per
row.

See [Cell Context and Computed Values](cell-context.md) and the [complete
computed-cell example](examples/computed-cell.md).

## Joins

Associations must be declared explicitly. The provider does not automatically traverse associations.

### Explicit Joins
```php
use Zhortein\DatatableBundle\Enum\JoinType;

$definition->addJoin(
    alias: 'org',
    join: 'e.organization',
    type: JoinType::Left
);

$definition->addColumn('org.name', label: 'Organization');
```

### Chained Joins
You can chain joins by referencing previous aliases.
```php
$definition->addJoin('org', 'e.organization', JoinType::Left);
$definition->addJoin('grp', 'org.group', JoinType::Left);

$definition->addColumn('grp.name', label: 'Group Name');
```

The type of `org.name` and `grp.name` is inferred from the target entity
metadata after the declared aliases have been resolved.

### Safe Custom Joins
Custom joins allow joining entities without a mapped Doctrine association.
```php
$definition->addCustomJoin(
    alias: 'audit',
    targetEntityClass: AuditLog::class,
    condition: 'audit.objectId = e.id AND audit.className = :audit_class',
    type: JoinType::Left
);
$definition->setOption('customJoinParameters', ['audit_class' => User::class]);
$definition->addColumn('audit.createdAt', label: 'Last audit');
```

The type of `audit.createdAt` is inferred from `AuditLog` metadata. The join
condition and parameters remain explicit application responsibilities.

## Aggregate Columns

You can add aggregate columns (COUNT, SUM, AVG, MIN, MAX).
```php
use Zhortein\DatatableBundle\Enum\AggregateFunction;

$definition->addAggregateColumn(
    name: 'loginCount',
    field: 'e.logins',
    function: AggregateFunction::Sum,
    label: 'Total Logins'
);
```

## Filtering

### Permanent Filters
Backend-defined filters that are always applied and cannot be changed by the user.
```php
use Zhortein\DatatableBundle\Enum\FilterOperator;

$definition->addPermanentFilter('e.enabled', FilterOperator::Equals, true);
```

For a signed child datatable, defer the permanent-filter value until the
request context has been restored:

```php
use Zhortein\DatatableBundle\Definition\ContextFilterValue;

$definition->addPermanentFilter(
    'e.orderId',
    FilterOperator::Equals,
    ContextFilterValue::from('orderId'),
);
```

See [hierarchical datatables](hierarchical-datatables.md) for the required
context allowlist and authorization boundary.

### User-facing Filters
Filters rendered in the UI for user interaction.
```php
use Zhortein\DatatableBundle\Enum\FilterType;

$definition->addFilter('email', 'e.email', type: FilterType::Text);
```

## Global Search
Searchable columns participate in the global search. By default, the provider uses a case-insensitive `LIKE` search.

## Sorting

Columns marked as `sortable: true` can be sorted by clicking the header.
Ordered multi-column sorting works across main-entity and explicitly joined
fields. Unsupported criteria are skipped independently instead of disabling
later valid criteria. See [multi-column sorting](sorting.md).

## Performance

The Doctrine provider handles pagination and filtering at the database level. For detailed tuning, see **[Doctrine Performance Guidance](doctrine-performance.md)**.

### Count and Distinct Strategy
The provider automatically uses `COUNT(DISTINCT e.id)` when joins or aggregates are present to ensure accurate row counts even if joins would otherwise duplicate root entity rows.

## Current Limitations

- **Automatic Joins**: No automatic association traversal (all joins must be explicit).
- **Deep Nesting**: Complex deep association paths may require multiple explicit joins.
- **Collection Aggregations**: ManyToMany or OneToMany collections are not fully supported for direct column display.
- **Async Exports**: Large exports are currently synchronous.
- **Streaming exports**: Synchronous exports use bounded scalar batches; configure `export.batch_size` according to row width and query cost.
- **Source objects**: Doctrine uses scalar projections and never exposes root entities to cell templates implicitly.
- **Computed filtering/sorting**: PHP-resolved columns cannot participate in DQL filtering or sorting.

See [Roadmap](roadmap.md) for planned improvements.
