# Cell template reference

This document describes built-in cell templates and custom cell rendering.

The bundle uses Twig-first cell rendering.

Cell values are rendered server-side and escaped by Twig by default.

## Status

Implemented:

- built-in cell templates;
- `CellType` enum;
- custom column templates;
- fallback to default cell template;
- Doctrine metadata type enrichment;
- default Bootstrap alignment by cell type.

Implemented cell types:

```text
default
string
numeric
boolean
datetime
array
enum
```

Not implemented yet:

- rich enum badges/icons;
- per-column formatter services;
- user-defined type guessers;
- advanced value objects for cell rendering context;
- automatic currency/number localization;
- template cache/warmup validation.

## Built-in template location

Built-in Bootstrap cell templates live under:

```text
templates/bootstrap/cell/
```

Current templates:

```text
array.html.twig
boolean.html.twig
datetime.html.twig
default.html.twig
enum.html.twig
numeric.html.twig
string.html.twig
```

## Cell type selection

Cell template selection is handled by `DatatableRenderer`.

The renderer uses:

```php
ColumnDefinition::getTemplate()
ColumnDefinition::getType()
```

Resolution order:

```text
custom column template
→ built-in type-specific template
→ default template
```

## Fallback behavior

If the column has a custom template, that template is used.

If the column has a known type, the corresponding built-in template is used.

If the column type is missing or unknown, the default template is used.

Example:

```php
$definition->addColumn('e.email', type: 'string');
```

uses:

```text
@ZhorteinDatatable/bootstrap/cell/string.html.twig
```

Example:

```php
$definition->addColumn('e.unknown', type: 'custom');
```

falls back to:

```text
@ZhorteinDatatable/bootstrap/cell/default.html.twig
```

## Cell template context

All built-in and custom cell templates receive:

```twig
{{ column }}
{{ value }}
```

### `column`

Type:

```php
Zhortein\DatatableBundle\Definition\ColumnDefinition
```

Useful accessors:

```twig
{{ column.name }}
{{ column.label }}
{{ column.type }}
{{ column.className }}
{{ column.template }}
```

### `value`

The resolved value for the current cell.

Value can be:

- `null`;
- string;
- integer;
- float;
- boolean;
- `DateTimeInterface`;
- array;
- object;
- enum.

Twig auto-escaping remains enabled.

## Default cell template

Template:

```text
templates/bootstrap/cell/default.html.twig
```

Behavior:

```twig
{{ value }}
```

It is intentionally minimal and safe.

## String cell template

Template:

```text
templates/bootstrap/cell/string.html.twig
```

Behavior:

```twig
{{ value }}
```

Use for plain text values.

## Numeric cell template

Template:

```text
templates/bootstrap/cell/numeric.html.twig
```

Behavior:

```twig
{{ value }}
```

Numeric cells are right-aligned by default through renderer cell class resolution.

Default alignment:

```text
text-end
```

## Boolean cell template

Template:

```text
templates/bootstrap/cell/boolean.html.twig
```

Behavior:

- true values render a success badge;
- false values render a secondary badge.

Labels are translated through the `zhortein_datatable` domain:

```text
zhortein_datatable.boolean.yes
zhortein_datatable.boolean.no
```

Default alignment:

```text
text-center
```

## Datetime cell template

Template:

```text
templates/bootstrap/cell/datetime.html.twig
```

Datetime cells use the `zhortein_datatable_datetime()` Twig function.

This function delegates to `DateTimeFormatterInterface`.

Default implementation:

```php
DefaultDateTimeFormatter
```

The default formatter:

- uses the current Symfony request locale when available;
- uses `IntlDateFormatter` when PHP Intl is available;
- falls back to a deterministic PHP date format otherwise.

Applications can replace the formatter service for user-specific timezones or advanced localization.

## Array cell template

Template:

```text
templates/bootstrap/cell/array.html.twig
```

Behavior:

- iterable values are JSON-encoded inside a `<code>` element;
- non-iterable values are rendered as-is.

This is a simple debugging-friendly default.

Applications should usually provide a custom column template for rich array rendering.

## Enum cell template

Template:

```text
templates/bootstrap/cell/enum.html.twig
```

Current behavior:

```twig
{{ value }}
```

Enum rendering is intentionally minimal for now.

Rich enum labels, badges and icons are planned later.

Default alignment:

```text
text-center
```

## Custom column templates

A column can define its own Twig template:

```php
$definition->addColumn(
    name: 'e.status',
    label: 'Status',
    template: 'admin/datatable/cell/status.html.twig',
    type: 'string',
);
```

Custom templates take precedence over built-in type-specific templates.

Custom template example:

```twig
<span class="badge text-bg-info">
    {{ value }}
</span>
```

## Custom template context

Custom templates receive:

```twig
{{ column }}
{{ value }}
```

Example:

```twig
{% if value == 'enabled' %}
    <span class="badge text-bg-success">Enabled</span>
{% else %}
    <span class="badge text-bg-secondary">Disabled</span>
{% endif %}
```

## Default alignment by type

When no explicit column `className` is configured, the renderer applies default Bootstrap alignment classes for selected cell types.

Current defaults:

| Cell type | Default class |
|---|---|
| numeric | `text-end` |
| boolean | `text-center` |
| enum | `text-center` |
| default/string/datetime/array | none |

Explicit column classes always win:

```php
$definition->addColumn(
    name: 'e.amount',
    label: 'Amount',
    className: 'text-start',
    type: 'numeric',
);
```

In this case, `text-start` is preserved and `text-end` is not added.

## Doctrine type enrichment

Doctrine-backed datatables can receive inferred cell types.

`DoctrineDatatableDefinitionEnricher` uses `DoctrineFieldTypeGuesser`.

Examples:

| Doctrine type | Cell type |
|---|---|
| string/text/guid | string |
| integer/smallint/bigint/decimal/float | numeric |
| boolean | boolean |
| datetime/date/time | datetime |
| json/simple_array | array |
| backed enum | enum |

Explicit column types are preserved.

Example:

```php
$definition->addColumn('e.createdAt', type: 'string');
```

will keep `string` even if Doctrine would infer `datetime`.

## Overriding built-in templates

Host applications can override built-in templates through Symfony bundle template overrides.

Example:

```text
templates/bundles/ZhorteinDatatableBundle/bootstrap/cell/boolean.html.twig
```

This overrides the boolean cell template globally.

Prefer custom column templates when only one column needs special rendering.

## Recommended strategy

Use this order:

1. Let Doctrine infer the type for simple cases.
2. Set `type` explicitly when inference is not enough.
3. Use `className` for alignment or CSS customization.
4. Use `template` for one-column custom rendering.
5. Override built-in templates only for global behavior changes.

## Current limitations

### No formatter chain yet

Only datetime has a dedicated formatter abstraction.

Other types do not have formatter services yet.

### Basic enum rendering

Enum rendering is currently plain.

Badge/icon rendering will be designed later.

### No currency support

Currency formatting is not implemented.

### No locale-aware number formatting

Numeric values are rendered as-is.

### No template validation

Custom template existence is not validated when the datatable definition is built.

Twig errors remain explicit at render time.

## Boolean display modes

Boolean cells support several display modes:

```text
badge
icon
switch
text
```

### Badge mode

Badge mode is the default.

It renders translated `Yes` / `No` labels as Bootstrap badges.

```twig
{{ zhortein_datatable('users', {
    booleanDisplayMode: 'badge'
}) }}
```

### Icon mode

Icon mode renders decorative check/cross characters with visually hidden translated labels.

```twig
{{ zhortein_datatable('users', {
    booleanDisplayMode: 'icon'
}) }}
```

No icon library is required.

### Switch mode

Switch mode renders a disabled Bootstrap switch.

```twig
{{ zhortein_datatable('users', {
    booleanDisplayMode: 'switch'
}) }}
```

The switch is display-only and does not update data.

### Text mode

Text mode renders the translated label only.

```twig
{{ zhortein_datatable('users', {
    booleanDisplayMode: 'text'
}) }}
```

## Related documentation

- [`templates.md`](templates.md)
- [`template-context.md`](template-context.md)
- [`actions-and-cells.md`](actions-and-cells.md)
- [`doctrine-provider.md`](doctrine-provider.md)
