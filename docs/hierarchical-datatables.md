# Hierarchical datatables

Hierarchical datatables render one expandable child datatable for each parent
row. The child shell and its data are loaded only when the user expands that
row. Parent and child definitions may use different data providers.

Use this feature for bounded business structures such as orders and lines,
accounts and transactions, or projects and tasks. It is not a general-purpose
client-side tree widget.

## Define the parent

Declare the child name and an explicit mapping for every value it needs:

```php
<?php

declare(strict_types=1);

namespace App\Datatable;

use App\Entity\Order;
use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\ChildContextValue;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;

#[AsDatatable(name: 'orders', provider: 'doctrine')]
final class OrderDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setEntityClass(Order::class)
            ->addColumn('e.id', visible: false, exportable: false)
            ->addColumn('e.reference', label: 'Order')
            ->setChildDatatable(
                name: 'order-lines',
                context: [
                    'orderId' => ChildContextValue::row('e.id'),
                ],
                maxDepth: 2,
            )
        ;
    }
}
```

The parent result must expose a scalar row identifier. The built-in providers
recognize `id` and Doctrine's `e_id`; a custom key can be selected with the
definition option `identifier`. Rows without an identifier cannot be expanded.

`ChildContextValue` supports:

- `row()` and `context()` for required values;
- `optionalRow()` and `optionalContext()` for omitted values;
- `rowOr()` and `contextOr()` for explicit fallbacks;
- `literal()` for a fixed scalar value.

Transported values must be scalar, `null`, a backed enum or `Stringable`.
Collections and objects are rejected.

## Scope a Doctrine child

The child declares which context keys may be restored, then uses
`ContextFilterValue` in a permanent provider filter. The value is resolved
after the signed child request has restored its context:

```php
<?php

declare(strict_types=1);

namespace App\Datatable;

use App\Entity\OrderLine;
use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\ContextFilterValue;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterOperator;

#[AsDatatable(name: 'order-lines', provider: 'doctrine')]
final class OrderLineDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setEntityClass(OrderLine::class)
            ->setContext(new DatatableContext(
                values: ['orderId' => null],
                browserSafeKeys: ['orderId'],
            ))
            ->addPermanentFilter(
                'e.orderId',
                FilterOperator::Equals,
                ContextFilterValue::from('orderId'),
            )
            ->addColumn('e.id', visible: false, exportable: false)
            ->addColumn('e.product', label: 'Product')
            ->addColumn('e.quantity', label: 'Quantity')
        ;
    }
}
```

Do not read `$definition->getContext()` while building the definition to
create a literal filter. Definitions are built before request context is
restored. `ContextFilterValue` deliberately defers that lookup until the
provider executes.

## Scope an Array child

The built-in Array provider applies the same permanent-filter contract before
computing total and filtered counts:

```php
$definition
    ->setContext(new DatatableContext(
        values: ['orderId' => null],
        browserSafeKeys: ['orderId'],
    ))
    ->setOption(ArrayDataProvider::OPTION_ROWS, $orderLines)
    ->addPermanentFilter(
        'orderId',
        FilterOperator::Equals,
        ContextFilterValue::from('orderId'),
    )
;
```

A parent, child and grandchild may independently use Array, Doctrine or a
custom provider. A custom provider must implement permanent scoping itself if
it accepts `ContextFilterValue`.

## Nested children and state isolation

A child can declare another child with `setChildDatatable()`. Every occurrence
receives a stable opaque instance key derived from its ancestry and row
identifier. URL state, fragments, exports and nested requests are namespaced
to that instance, so expanding one row does not overwrite another row's
search, filters or pagination.

Depth is explicit and bounded. `maxDepth` accepts `1` to `3`; expansion controls
are omitted once the configured limit is reached. The bundle also bounds child
names, context keys, context value counts and signed payload sizes before
provider execution.

## Authorization and trust boundary

Child context is signed with `kernel.secret` and bound to the child datatable
name and instance. It provides integrity, not encryption: values remain
readable in the browser and must never contain secrets or unnecessary personal
data. Do not construct child URLs or context tokens yourself.
The bundle-owned route is named `zhortein_datatable_child`.

The default `ChildDatatableAuthorizationCheckerInterface` implementation
allows child requests. Applications with tenant, ownership or business-scope
rules should replace it:

```php
<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Zhortein\DatatableBundle\Contract\ChildDatatableAuthorizationCheckerInterface;
use Zhortein\DatatableBundle\Hierarchy\ChildDatatableAuthorizationContext;

final readonly class ChildDatatableAuthorizationChecker implements ChildDatatableAuthorizationCheckerInterface
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function isGranted(ChildDatatableAuthorizationContext $context): bool
    {
        $orderId = $context->getContext()->get('orderId');

        return is_int($orderId)
            && $this->authorizationChecker->isGranted('ORDER_VIEW', $orderId);
    }
}
```

Alias the contract to the application service:

```yaml
# config/services.yaml
services:
    App\Security\ChildDatatableAuthorizationChecker: ~

    Zhortein\DatatableBundle\Contract\ChildDatatableAuthorizationCheckerInterface:
        alias: App\Security\ChildDatatableAuthorizationChecker
```

The checker runs when a child URL is issued and again when the child request
is consumed. Normal firewall rules still apply. Permanent provider filters
remain mandatory for data isolation: hiding an expand button is not an
authorization boundary.

## Interaction and accessibility

The generated Bootstrap markup provides:

- a keyboard-operable button with `aria-expanded` and `aria-controls`;
- translated expand, collapse, loading, failure and retry labels;
- an announced loading status and an explicit error alert;
- focus restoration when focused child content is collapsed;
- at-most-once loading across collapse/re-expand cycles;
- an explicit retry after a failed request.

Custom expand and collapse labels use the datatable translation domain:

```php
$definition
    ->setTranslationDomain('datatables')
    ->setChildDatatable(
        name: 'order-lines',
        context: ['orderId' => ChildContextValue::row('e.id')],
        expandLabel: 'orders.lines.expand',
        collapseLabel: 'orders.lines.collapse',
    )
;
```

## Performance and limitations

- Each expanded row performs one child-shell request, followed by the child's
  normal fragment request. Failed loads are retried only on explicit action.
- Only one child datatable definition can be attached to a parent definition.
- Expansion state is local to the rendered page and is not encoded in URL
  state or saved views.
- The feature does not eagerly fetch whole trees, virtualize large hierarchies,
  merge parent and child exports, or provide background loading.
- Providers should keep permanent filters indexed and avoid per-row queries.
  Normal pagination still applies independently to each child.

See also [explicit context](context.md), [providers](providers.md),
[routes](routes.md), [URL state](url-state.md) and
[actions and security](actions.md).
