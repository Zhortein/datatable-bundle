# User-facing filters

This document explains how to declare and use user-facing datatable filters.

User-facing filters are different from permanent filters:

- **permanent filters** are backend-defined and never controlled by the frontend;
- **user-facing filters** are declared by the backend, rendered in the UI and filled by users.

The bundle intentionally does not expose arbitrary query expressions from the frontend.

Only declared filters are parsed and applied.

## Status

The current implementation supports:

- filter definition objects;
- request normalization for `filters[...]`;
- Bootstrap filter toolbar rendering;
- Stimulus refresh on filter changes;
- active filter summary;
- clear filters action;
- Doctrine provider support for declared filters;
- Doctrine filters on joined fields.

Implemented filter types:

- text;
- choice;
- boolean;
- date;
- date range;
- number;
- number range.

Not implemented yet:

- nested filter groups;
- SearchBuilder-style expressions;
- saved filter presets;
- custom filter widgets;
- Select2 integration;
- datepicker integration;
- frontend automated tests.

## Declaring filters

Filters are declared in the datatable class.

Example:

```php
<?php

declare(strict_types=1);

namespace App\Datatable;

use App\Entity\User;
use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterType;

#[AsDatatable(name: 'users', provider: 'doctrine')]
final class UserDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setEntityClass(User::class)
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.enabled', label: 'Enabled')
            ->addFilter(
                name: 'email',
                field: 'e.email',
                label: 'Email',
                type: FilterType::Text,
                placeholder: 'Search an email',
            )
            ->addFilter(
                name: 'enabled',
                field: 'e.enabled',
                label: 'Enabled',
                type: FilterType::Boolean,
            )
        ;
    }
}
```

## Filter definition options

`addFilter()` supports:

```php
$definition->addFilter(
    name: 'email',
    field: 'e.email',
    label: 'Email',
    type: FilterType::Text,
    choices: [],
    placeholder: 'Search an email',
    required: false,
    options: [],
);
```

### `name`

The public filter name.

It is used in request parameters:

```text
filters[email]
```

### `field`

The provider field targeted by the filter.

Examples:

```text
e.email
e.enabled
organization.name
```

Only supported fields for the chosen provider can be applied.

### `label`

Human-readable filter label.

It is rendered in the toolbar.

### `type`

The filter type.

Supported values:

```php
FilterType::Text
FilterType::Choice
FilterType::Boolean
FilterType::Date
FilterType::DateRange
FilterType::Number
FilterType::NumberRange
```

### `choices`

Choice filter values.

Example:

```php
$definition->addFilter(
    name: 'status',
    field: 'e.status',
    label: 'Status',
    type: FilterType::Choice,
    choices: [
        'Enabled' => 'enabled',
        'Disabled' => 'disabled',
    ],
);
```

### `placeholder`

Placeholder text for text-like controls or the empty option of select controls.

### `required`

Marks the generated control as required.

### `options`

Reserved for future filter-specific options.

## Request parameters

Filter values are sent under the `filters` parameter.

Examples:

```text
filters[email]=alice
filters[enabled]=1
filters[createdAt][from]=2026-01-01
filters[createdAt][to]=2026-01-31
```

The `DatatableRequestFactory` normalizes these values into `DatatableRequest`.

Empty values are removed.

Unknown frontend filter names are ignored by providers unless they match a declared filter.

## Text filters

Text filters render an input:

```html
<input name="filters[email]" type="text">
```

Doctrine behavior:

- applies `LOWER(field) LIKE :value`;
- value is bound as a parameter;
- search is case-insensitive;
- only declared filters are applied.

Example:

```php
$definition->addFilter(
    name: 'email',
    field: 'e.email',
    label: 'Email',
    type: FilterType::Text,
);
```

## Choice filters

Choice filters render a select:

```php
$definition->addFilter(
    name: 'status',
    field: 'e.status',
    label: 'Status',
    type: FilterType::Choice,
    choices: [
        'Enabled' => 'enabled',
        'Disabled' => 'disabled',
    ],
);
```

Doctrine behavior:

- scalar value applies equality;
- array value applies `IN`;
- values are bound as parameters.

## Boolean filters

Boolean filters render a select with yes/no values.

```php
$definition->addFilter(
    name: 'enabled',
    field: 'e.enabled',
    label: 'Enabled',
    type: FilterType::Boolean,
);
```

Accepted values include:

```text
1
0
true
false
yes
no
on
off
```

Doctrine behavior:

- normalized value is bound as boolean;
- invalid boolean values are ignored.

## Date filters

Date filters render a date input:

```php
$definition->addFilter(
    name: 'createdAt',
    field: 'e.createdAt',
    label: 'Created at',
    type: FilterType::Date,
);
```

Doctrine behavior:

- date is interpreted as a full-day range;
- provider applies `field >= start_of_day`;
- provider applies `field < next_day`.

Expected input format:

```text
Y-m-d
```

## Date range filters

Date range filters render two date inputs:

```php
$definition->addFilter(
    name: 'createdAt',
    field: 'e.createdAt',
    label: 'Created at',
    type: FilterType::DateRange,
);
```

Request shape:

```text
filters[createdAt][from]=2026-01-01
filters[createdAt][to]=2026-01-31
```

Doctrine behavior:

- `from` applies `>=`;
- `to` applies `<=`.

## Number filters

Number filters render a number input:

```php
$definition->addFilter(
    name: 'amount',
    field: 'e.amount',
    label: 'Amount',
    type: FilterType::Number,
);
```

Doctrine behavior:

- numeric values apply equality;
- non-numeric values are ignored.

## Number range filters

Number range filters render two number inputs:

```php
$definition->addFilter(
    name: 'amount',
    field: 'e.amount',
    label: 'Amount',
    type: FilterType::NumberRange,
);
```

Request shape:

```text
filters[amount][from]=10
filters[amount][to]=100
```

Doctrine behavior:

- `from` applies `>=`;
- `to` applies `<=`.

## Filters on joined Doctrine fields

Filters can target fields from explicitly declared joins.

Example:

```php
use Zhortein\DatatableBundle\Enum\FilterType;
use Zhortein\DatatableBundle\Enum\JoinType;

$definition
    ->setEntityClass(User::class)
    ->addJoin('organization', 'e.organization', JoinType::Left)
    ->addColumn('e.email', label: 'Email')
    ->addColumn('organization.name', label: 'Organization')
    ->addFilter(
        name: 'organization_name',
        field: 'organization.name',
        label: 'Organization',
        type: FilterType::Text,
    )
    ->addFilter(
        name: 'organization_enabled',
        field: 'organization.enabled',
        label: 'Organization enabled',
        type: FilterType::Boolean,
    )
;
```

Frontend request example:

```text
filters[organization_name]=acme
filters[organization_enabled]=1
```

Only declared filters are applied, and only explicit join aliases can be referenced.

## Filter toolbar rendering

Declared filters are rendered in the datatable toolbar.

Supported controls:

- text input;
- select input for choice filters;
- select input for boolean filters;
- date input;
- date range inputs;
- number input;
- number range inputs.

Generated names follow:

```text
filters[filterName]
filters[filterName][from]
filters[filterName][to]
```

## Stimulus behavior

Filter controls expose:

```html
data-zhortein--datatable-bundle--datatable-filter-control="true"
```

and call:

```html
data-action="...->zhortein--datatable-bundle--datatable#changeFilter"
```

When a filter changes:

1. current page is reset to 1;
2. the datatable refresh is debounced;
3. Ajax fragments are refreshed;
4. filter values are appended to the fragments URL.

## Active filters and clear action

When filters are declared, the toolbar renders:

- an active filter summary area;
- a clear filters button.

The Stimulus controller shows them when at least one filter control has a value.

Clicking the clear button:

1. clears filter controls;
2. resets current page to 1;
3. refreshes datatable fragments.

## Combining filters with search and sorting

Filters can be combined with:

- permanent filters;
- global search;
- sorting;
- pagination.

Provider order is:

```text
permanent filters
→ user-facing filters
→ global search
→ sorting
→ pagination
```

This means user filters affect `filteredItems` and rendered rows.

Permanent filters affect both `totalItems` and `filteredItems`.

## Security model

The frontend never sends arbitrary field names.

It sends filter values by filter name:

```text
filters[email]=alice
```

The provider only applies filters declared in `DatatableDefinition`.

This prevents arbitrary frontend-provided DQL fields or expressions.

## Current limitations

### No nested expressions

Filters are combined with `AND`.

Nested `AND`/`OR` groups are not implemented.

### No SearchBuilder

There is no DataTables.net-style search builder.

### No custom widgets

Filters currently use native HTML controls.

No Select2, datepicker or custom JS widgets are included.

### No saved presets

Filter presets are not implemented yet.

### No user preferences

Filter state is not persisted.

### Limited date handling

Date filters expect `Y-m-d` input.

Timezone-specific date handling may require future configuration.

### No collection filters

Filters on collection-valued associations are not supported yet.

## Column header filter dropdowns

A future filter layout will allow filters to appear directly in column headers.

The design decision is documented in:

```text
docs/decisions/0006-column-header-filter-dropdowns.md
```

The chosen approach is Bootstrap dropdowns.

The current toolbar filter layout remains the default until the feature is implemented and validated.

## Filter layout

The `filterLayout` render option controls where user-facing filters are rendered.

```twig
{{ zhortein_datatable('users', {
    filterLayout: 'toolbar'
}) }}
```

Supported values:

| Value | Behavior |
|---|---|
| `toolbar` | Render filters in the toolbar. This is the default. |
| `header` | Hide toolbar filters and render filters in column headers when header filters are enabled. |
| `none` | Hide filter controls. Backend filter parsing remains available. |

Header rendering is implemented by the column header filter dropdown feature.

## Column header filter dropdowns

Header filters can be enabled with:

```twig
{{ zhortein_datatable('users', {
    filterLayout: 'header'
}) }}
```

When enabled, filters are matched to columns by field name.

Example:

```php
$definition
    ->addColumn('e.email', label: 'Email')
    ->addFilter(
        name: 'email',
        field: 'e.email',
        label: 'Email',
        type: FilterType::Text,
    )
;
```

The `Email` column renders a filter dropdown in its header.

The generated input still uses:

```text
filters[email]
```

so the existing filter request handling is reused.

## Related documentation

- [`doctrine-provider.md`](doctrine-provider.md)
- [`table-controls.md`](table-controls.md)
- [`end-to-end-flow.md`](end-to-end-flow.md)
- [`architecture.md`](architecture.md)
