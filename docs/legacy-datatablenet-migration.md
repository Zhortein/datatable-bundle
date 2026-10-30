# Migrating a DatatableNet-style application

This guide helps migrate an application-specific, DataTables.net-oriented
datatable layer to `zhortein/datatable-bundle`.

It is separate from [Migrating from 1.x to 2.0](migration-2.0.md). That guide
covers consumers already using this bundle. This guide covers applications
that own a legacy datatable engine with a fluent PHP API, automatic Doctrine
inspection and Twig cell templates.

The examples are deliberately generic. Do not copy the legacy engine into the
host application or the bundle.

## Migration strategy

Migrate one datatable at a time:

1. keep the existing page route and business authorization;
2. replace the legacy definition with an `#[AsDatatable]` service;
3. declare every Doctrine join explicitly;
4. convert backend-only restrictions to permanent filters;
5. convert request options to signed datatable context;
6. replace legacy cell templates or update their context variables;
7. render the new datatable next to the legacy table in a non-production
   environment and compare rows, counts, sorting, search and exports;
8. remove the old endpoint only after the new table passes the comparison.

Do not migrate the shared legacy engine first. A vertical migration of one
business table exposes missing capabilities without preserving obsolete
DataTables.net, jQuery or service-locator architecture.

## API mapping

| Legacy concept | Bundle equivalent |
|---|---|
| Marker attribute without metadata | `#[AsDatatable(name: '...', provider: 'doctrine')]` |
| `defineDatatable($service, $user, $options)` | `buildDatatable(DatatableDefinition $definition)` |
| Table factory call | Attribute name plus `setEntityClass()` |
| Default translation domain | `setTranslationDomain()` |
| `addField()` positional flags | `addColumn()` named arguments |
| Automatic association recursion | Explicit `addJoin()` declarations |
| Entity join with a DQL condition | `addCustomJoin()` |
| Persistent data filter | `addPermanentFilter()` with `FilterOperator` |
| Request-dependent option | Signed `DatatableContext` plus `ContextFilterValue` |
| Selector pseudo-column | Declarative bulk action and built-in row selection |
| Actions pseudo-column | `addRowAction()` and `addGlobalAction()` |
| Automatic template lookup | Explicit column `template` or a custom theme |
| `fieldValue` Twig variable | `value` |
| Raw Doctrine result in Twig | Normalized `row`, `source` and `cell` context |
| DataTables SearchBuilder metadata | `addAdvancedFilterField()` |
| Full export callback | Export-safe computed resolver or custom export writer |

Named arguments are important during migration. They make the old positional
sequence of visibility, sorting and searching flags unambiguous:

```php
$definition->addColumn(
    name: 'e.quantity',
    label: 'Quantity',
    visible: true,
    sortable: true,
    searchable: false,
    className: 'text-end',
);
```

## A joined, user-scoped Doctrine table

Legacy business tables commonly combine:

- mapped associations;
- entities joined with an explicit DQL condition;
- columns from several aliases;
- restrictions derived from the authenticated user.

The equivalent definition keeps joins and scope explicit:

```php
<?php

declare(strict_types=1);

namespace App\Datatable;

use App\Entity\Shipment;
use App\Entity\ShipmentBatch;
use App\Entity\Warehouse;
use App\Security\ShipmentScope;
use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterOperator;
use Zhortein\DatatableBundle\Enum\JoinType;

#[AsDatatable(name: 'shipment-list', provider: 'doctrine')]
final readonly class ShipmentDatatable implements DatatableInterface
{
    public function __construct(
        private ShipmentScope $scope,
    ) {
    }

    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setEntityClass(Shipment::class)
            ->setTranslationDomain('shipment')
            ->addCustomJoin(
                alias: 'batch',
                targetEntityClass: ShipmentBatch::class,
                condition: 'e.batch = batch.id',
                type: JoinType::Left,
            )
            ->addCustomJoin(
                alias: 'warehouse',
                targetEntityClass: Warehouse::class,
                condition: 'warehouse.id = batch.warehouse',
                type: JoinType::Left,
            )
            ->addJoin('recipient', 'e.recipient', JoinType::Left)
            ->addJoin('packageType', 'e.packageType', JoinType::Left)
            ->addColumn(
                name: 'e.id',
                visible: false,
                sortable: false,
                searchable: false,
            )
            ->addColumn('batch.reference', label: 'Batch reference')
            ->addColumn('warehouse.name', label: 'Warehouse')
            ->addColumn('recipient.name', label: 'Recipient')
            ->addColumn(
                name: 'e.quantity',
                label: 'Quantity',
                searchable: false,
                className: 'text-end',
            )
            ->addColumn('packageType.name', label: 'Package type')
            ->addColumn(
                name: 'e.deleted',
                visible: false,
                sortable: false,
                searchable: false,
            )
        ;

        if (!$this->scope->canViewDeletedRows()) {
            $definition->addPermanentFilter(
                'e.deleted',
                FilterOperator::Equals,
                false,
            );
        }

        $allowedRecipients = $this->scope->getAllowedRecipients();

        if ([] !== $allowedRecipients) {
            $definition
                ->addPermanentFilter(
                    'e.recipient',
                    FilterOperator::IsNotNull,
                )
                ->addPermanentFilter(
                    'e.recipient',
                    FilterOperator::In,
                    $allowedRecipients,
                )
            ;
        }
    }
}
```

`ShipmentScope` is an application service. It may use Symfony Security and
domain repositories, but it should return only the business scope needed by
the definition. The datatable remains a normal autowired Symfony service.

Do not put the authenticated user or lists of allowed entity identifiers in
browser-safe context. Authorization-derived permanent filters are rebuilt on
the server for the initial request, every fragment request and every export.
The target page, fragment and export routes must still enforce their normal
Symfony authorization.

The Doctrine provider accepts entity objects in an `IN` permanent filter.
Avoid adding the filter for an empty allowed collection unless the intended
result is explicitly an empty table.

## A context-scoped history table

A legacy table may receive several optional route parameters and conditionally
filter its rows. Replace that open-ended options array with one explicit,
fail-closed context shape:

```php
<?php

declare(strict_types=1);

namespace App\Datatable;

use App\Entity\HistoryEntry;
use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\ContextFilterValue;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterOperator;

#[AsDatatable(name: 'subject-history', provider: 'doctrine')]
final class SubjectHistoryDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setEntityClass(HistoryEntry::class)
            ->setTranslationDomain('history')
            ->setContext(new DatatableContext(
                values: [
                    'subjectClass' => '',
                    'subjectId' => 0,
                ],
                browserSafeKeys: [
                    'subjectClass',
                    'subjectId',
                ],
            ))
            ->addJoin('author', 'e.author')
            ->addColumn(
                name: 'e.id',
                visible: false,
                sortable: false,
                searchable: false,
            )
            ->addColumn('e.createdAt', label: 'Created at', searchable: false)
            ->addColumn('e.type', label: 'Type')
            ->addColumn('e.level', label: 'Level', searchable: false)
            ->addColumn('e.title', label: 'Title')
            ->addColumn('author.displayName', label: 'Author')
            ->addPermanentFilter(
                'e.subjectClass',
                FilterOperator::Equals,
                ContextFilterValue::from('subjectClass'),
            )
            ->addPermanentFilter(
                'e.subjectId',
                FilterOperator::Equals,
                ContextFilterValue::from('subjectId'),
            )
        ;
    }
}
```

Render it with the two required values:

```twig
{{ zhortein_datatable('subject-history', {
    instance: 'order-' ~ order.id ~ '-history',
    context: {
        subjectClass: order_class,
        subjectId: order.id
    }
}) }}
```

The bundle signs these allowlisted values and restores them before the
Doctrine provider runs. The same scope is therefore applied to fragments,
CSV/XLSX exports and opt-in Ajax actions.

The token provides integrity, not authorization or secrecy. Revalidate access
to the subject in a voter or an application-specific endpoint checker. Use a
stable public discriminator instead of a PHP class name when the data model
supports one.

## Custom joins

Mapped associations must use `addJoin()`:

```php
$definition
    ->addJoin('customer', 'e.customer', JoinType::Left)
    ->addJoin('group', 'customer.group', JoinType::Left)
;
```

Use `addCustomJoin()` only when no mapped association exists:

```php
$definition
    ->addCustomJoin(
        alias: 'audit',
        targetEntityClass: AuditEntry::class,
        condition: 'audit.objectId = e.id AND audit.subjectClass = :subject_class',
    )
    ->setOption('customJoinParameters', [
        'subject_class' => Order::class,
    ])
;
```

Aliases are declared in insertion order. Declare an alias before another join
condition references it. The provider infers column types from the target
entity metadata for mapped, chained and custom joins.

## Cell templates

The bundle already supplies numeric, boolean, datetime, array and enum cell
renderers. Remove legacy templates that only repeat the raw value or call
`format_datetime`.

For a real customization, update the Twig context:

| Legacy variable | Current variable |
|---|---|
| `fieldValue` | `value` |
| raw result `row` | normalized `row` |
| entity or hydrated object | provider-specific `source` |
| ad hoc options | `datatable_context` |
| enum helper variables | `enum_presentation` |

Example:

```twig
{% if value %}
    <span class="text-success" aria-label="{{ 'common.yes'|trans }}">
        {{ zhortein_datatable_icon(boolean_true_icon) }}
    </span>
{% else %}
    <span class="text-danger" aria-label="{{ 'common.no'|trans }}">
        {{ zhortein_datatable_icon(boolean_false_icon) }}
    </span>
{% endif %}
```

Prefer the built-in boolean cell unless this presentation is a business
requirement. Do not restore Font Awesome, jQuery or application globals inside
bundle templates.

See [Cell Context and Computed Values](cell-context.md) and
[Enum Presentation](enum-presentation.md) for the complete template contract.

## Selection and actions

Do not migrate selector and actions pseudo-columns as ordinary data columns.

Declare bulk behavior:

```php
$definition->addBulkAction(
    name: 'archive',
    route: 'app_shipment_archive',
    label: 'Archive',
    selectedRowsParameterName: 'ids',
    permission: 'SHIPMENT_ARCHIVE',
);
```

Declare row behavior:

```php
$definition->addRowAction(
    name: 'view',
    route: 'app_shipment_show',
    label: 'View',
    routeParameters: [
        'id' => RouteParameter::row('e.id'),
    ],
    permission: 'SHIPMENT_VIEW',
);
```

The bundle renders selection and action columns only when their definitions
require them.

## Verification checklist

For every migrated datatable, compare:

- row count before and after permanent filters;
- authorization scope for an administrator and a restricted user;
- empty allowed association collections;
- mapped, chained and custom joined values;
- default sort and every sortable column;
- global search and advanced filters;
- enum labels in the current locale;
- null, false, zero, empty array and datetime cells;
- initial render, Ajax refresh and Turbo restoration;
- CSV and XLSX contents, escaping and formula neutralization;
- two instances of the same definition on one page;
- row, global and bulk action permissions and CSRF handling.

Remove the legacy endpoint, compiler pass and service locator only after every
migrated table no longer references them.
