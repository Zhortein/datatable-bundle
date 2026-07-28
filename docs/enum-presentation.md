# Enum presentation

Enum columns can share one localized presentation across Twig cells, simple
filters, advanced filters and CSV/XLSX exports. The bundle supports backed and
pure PHP enums without requiring application enums to implement a bundle
interface.

## Basic usage

For an Array or custom provider, declare the enum class on the column:

```php
use App\Enum\OrderStatus;

$definition->addColumn(
    name: 'status',
    label: 'orders.columns.status',
    enumClass: OrderStatus::class,
);
```

`enumClass` automatically selects the `enum` cell type when no explicit type is
declared. Doctrine definitions normally omit it: runtime metadata enrichment
copies the mapped `enumType` to the column, including explicitly declared
mapped, chained and custom joins.

The default resolver uses this deterministic order:

1. an explicit presentation matched by case name or backed value;
2. the translated enum case name in the definition translation domain;
3. the literal case name;
4. the scalar value when no enum case can be resolved.

For example, `OrderStatus::Pending` looks up `Pending` in the definition
translation domain. Existing literal labels remain valid when no domain is
configured.

## Badges, colors and icons

Declare rich presentation metadata by case name or backed value:

```php
use App\Enum\OrderStatus;
use Zhortein\DatatableBundle\EnumPresentation\EnumPresentation;

$statusPresentations = [
    OrderStatus::Pending->value => new EnumPresentation(
        label: 'orders.status.pending',
        badgeVariant: 'warning',
        icon: 'bi bi-hourglass-split',
    ),
    OrderStatus::Paid->name => new EnumPresentation(
        label: 'orders.status.paid',
        badgeVariant: 'success',
        color: '#146c43',
        icon: 'bi bi-check-circle',
    ),
];

$definition
    ->setTranslationDomain('orders')
    ->addColumn(
        name: 'status',
        label: 'orders.columns.status',
        enumClass: OrderStatus::class,
        enumPresentations: $statusPresentations,
    )
;
```

`badgeVariant` is appended to Bootstrap's `text-bg-*` class. `color` supplies a
custom badge background color when the standard variants are not sufficient.
`icon` is a provider-specific icon identifier declared by the application. It
is a CSS class string with the default provider or an icon name such as
`bi:hourglass-split` with Symfony UX Icons. All configured
values are escaped by Twig.

Decoration is presentation-only. CSV and XLSX contain the same resolved label,
without badge or icon markup.

## Enum filters

The same resolver can derive simple-filter and Search Builder choices:

```php
$definition
    ->addFilter(
        name: 'status',
        field: 'status',
        enumClass: OrderStatus::class,
        enumPresentations: $statusPresentations,
    )
    ->addAdvancedFilterField(
        name: 'status',
        field: 'e.status',
        enumClass: OrderStatus::class,
        enumPresentations: $statusPresentations,
    )
;
```

Backed values are submitted for backed enums. Pure enum filters use case names
and are supported by the Array provider. Explicit `choices` remain
authoritative and keep the existing label-to-value contract.

## Custom Twig templates

Every custom cell template receives `enum_presentation`, either an
`EnumPresentation` instance or `null`:

```twig
{% if enum_presentation is not null %}
    {{ enum_presentation.label }}
{% endif %}
```

Its public properties are `label`, `badgeVariant`, `color` and `icon`. The
original `value`, `column`, `cell`, `row` and other documented cell variables
remain unchanged.

## Replacing the resolver

Applications can replace the default behavior without modifying their enums:

```php
use Zhortein\DatatableBundle\Contract\EnumPresentationResolverInterface;

final readonly class ApplicationEnumPresentationResolver implements EnumPresentationResolverInterface
{
    // Implement resolve() and resolveChoices().
}
```

```yaml
services:
    App\Datatable\ApplicationEnumPresentationResolver: ~

    Zhortein\DatatableBundle\Contract\EnumPresentationResolverInterface:
        alias: App\Datatable\ApplicationEnumPresentationResolver
```

The resolver is called at render or export time, so Symfony's current locale
and the definition translation domain are available consistently for initial
HTML, Ajax fragments, child datatables and exports.

## Related documentation

- [Cell context and computed values](cell-context.md)
- [Simple filters](filters.md)
- [Advanced filters](advanced-filters.md)
- [Exports](exports.md)
- [Doctrine runtime enrichment](doctrine-provider.md)
