# Advanced Filter Expressions

The "Advanced Filter Expressions" system (also referred to as **Search Builder**) allows users to build complex, nested filtering logic using `AND`/`OR` groups and various operators.

## Enabling Advanced Filters

Advanced filters are disabled by default. You can enable them globally or per-datatable.

### Global Enablement

In your `zhortein_datatable.yaml` configuration:

```yaml
zhortein_datatable:
    search_builder_enabled: true
```

### Per-Datatable Enablement

When rendering the datatable in Twig:

```twig
{{ zhortein_datatable('users', {
    searchBuilder: true
}) }}
```

## Declaring Filterable Fields

Unlike simple filters, fields for the Search Builder must be explicitly declared in your `DatatableDefinition` using `addAdvancedFilterField()`. This ensures a strict security boundary where only intended fields are exposed to the frontend.

```php
use Zhortein\DatatableBundle\Enum\FilterType;
use Zhortein\DatatableBundle\Enum\FilterOperator;
use Zhortein\DatatableBundle\Filter\Expression\ComparisonOperator;

$definition->addAdvancedFilterField(
    name: 'email',
    field: 'e.email',
    label: 'Email',
    type: FilterType::Text,
    allowedOperators: [
        ComparisonOperator::Equals,
        ComparisonOperator::Contains,
        ComparisonOperator::StartsWith,
    ]
);
```

### Options

| Option | Description |
|---|---|
| `name` | Public field name used in the frontend payload. |
| `field` | Provider field targeted (e.g., `e.email` or `organization.name`). |
| `label` | (Optional) Human-readable label rendered in the UI. Defaults to a capitalized version of `name`. |
| `type` | `FilterType` enum value (Text, Choice, Enum, Boolean, Date, Number). |
| `allowedOperators` | (Optional) List of operators allowed for this field. Accepts both advanced `ComparisonOperator` values and legacy/simple `FilterOperator` values. Operators are normalized internally to the advanced `ComparisonOperator` model. If empty, all operators compatible with the type are allowed. |
| `choices` | (Optional) Array of choices for `Choice` fields. |
| `enumClass` | (Optional) Backed enum class. When provided, the field type is upgraded to `FilterType::Enum` and choices are derived from the enum. |
| `nullable` | (Optional) When `true`, `is_null` / `is_not_null` operators are exposed for this field. |

### Mixing `ComparisonOperator` and `FilterOperator`

Both operator enums may be used in `allowedOperators`. The bundle normalizes them
to `ComparisonOperator` internally. Legacy `FilterOperator::Like` expands to
`Contains`, `StartsWith` and `EndsWith`; `FilterOperator::NotLike` maps to
`NotContains`.

```php
// Using ComparisonOperator (advanced enum)
$definition->addAdvancedFilterField(
    name: 'email',
    field: 'e.email',
    type: FilterType::Text,
    allowedOperators: [
        ComparisonOperator::Contains,
        ComparisonOperator::StartsWith,
    ],
);

// Using legacy FilterOperator (still supported)
$definition->addAdvancedFilterField(
    name: 'enabled',
    field: 'e.enabled',
    type: FilterType::Boolean,
    allowedOperators: [
        FilterOperator::Equals,
        FilterOperator::NotEquals,
    ],
);
```

### Effective operators

The operator list displayed in the UI and accepted from the frontend is computed
as the intersection of:

1. **Type-compatible operators** for the field's `FilterType` (and nullability), and
2. **Developer-allowed operators** declared via `allowedOperators`.

If a developer accidentally allows an operator incompatible with the field type
(e.g., `Contains` on a `Boolean`), that operator is silently filtered out: it
will not appear in the UI and the backend will reject any condition that uses
it.

## Supported Types and Operators

### Types

The Search Builder supports the following types from the `FilterType` enum:
- `Text`
- `Choice`
- `Enum`
- `Boolean`
- `Date`
- `Number`
- `NumberRange`
- `DateRange`

### Enum / Choice fields

`Choice` fields use a static `choices` map (`label => value`).

`Enum` fields accept a backed enum class via the `enumClass` option. The bundle
automatically derives the choice map from the enum cases (case name → backed
value). Enum values are submitted as their backed (scalar) values.

For both `Choice` and `Enum`, the operators effectively available are limited to
the equality and set operators: `eq`, `neq`, `in`, `not_in` (and `is_null` /
`is_not_null` when the field is nullable). Operator restrictions declared via
`allowedOperators` apply on top.

Search Builder field labels and choice labels are resolved in the definition's
translation domain when `setTranslationDomain()` is configured. Without a
domain, the declared labels remain literal. This is the same contract used by
columns, simple filters and actions; see
[declarative translations](configuration.md#translating-declarative-labels).

### Operators

The following operators are supported (see `ComparisonOperator` enum for internal values):

| Label | Internal Code | Behavior |
|---|---|---|
| **Equals** | `eq` | Exact match. |
| **Not Equals** | `neq` | Not equal match. |
| **Contains** | `contains` | Case-insensitive `LIKE %value%`. |
| **Does not contain** | `not_contains` | Case-insensitive `NOT LIKE %value%`. |
| **Starts with** | `starts_with` | Case-insensitive `LIKE value%`. |
| **Ends with** | `ends_with` | Case-insensitive `LIKE %value`. |
| **Greater than** | `gt` | `>` comparison. |
| **Greater than or equals** | `gte` | `>=` comparison. |
| **Less than** | `lt` | `<` comparison. |
| **Less than or equals** | `lte` | `<=` comparison. |
| **Between** | `between` | `BETWEEN value1 AND value2`. |
| **Is null** | `is_null` | `IS NULL` check. |
| **Is not null** | `is_not_null` | `IS NOT NULL` check. |
| **In** | `in` | `IN (value1, value2, ...)` check. |
| **Not in** | `not_in` | `NOT IN (value1, value2, ...)` check. |

## Logic and Nesting

The Search Builder supports `AND` and `OR` logic. Users can create nested groups
to build complex expressions:

- **Root Group**: The top-level group (defaults to `AND`).
- **Sub-groups**: Additional groups can be added inside other groups (up to a
  depth of 3).
- **Add condition / Add subgroup**: Each group exposes buttons to add either a
  leaf condition or a nested subgroup.
- **Remove condition / Remove subgroup**: Each condition and each nested
  subgroup can be removed individually.
- **Change logic**: Each group exposes a logic select (`AND` / `OR`).
- **Clear**: The root group's "Clear" button removes all conditions and nested
  subgroups and resets the root logic to `AND`.

### Payload shape

The frontend serializes the tree using lowercase `logic` values and a
`conditions` array containing either leaf conditions or nested groups:

```json
{
  "logic": "and",
  "conditions": [
    {
      "field": "email",
      "operator": "contains",
      "value": "alice"
    },
    {
      "logic": "or",
      "conditions": [
        { "field": "enabled", "operator": "eq", "value": true },
        { "field": "status",  "operator": "eq", "value": "admin" }
      ]
    }
  ]
}
```

The backend factory also accepts the legacy `children` key in place of
`conditions`, and uppercase `AND` / `OR` for `logic`, for backward
compatibility.

## Provider Behavior

### Doctrine Provider

Advanced filters are applied directly to the Doctrine `QueryBuilder`. 
- **Join Handling**: Joins are automatically managed based on the field references (e.g., `organization.name` will use the `organization` alias).
- **Case Sensitivity**: String comparisons (`Contains`, `Starts with`, etc.) use `LOWER()` on both the field and the parameter for database-agnostic case-insensitivity.
- **Security**: All parameters are bound using Doctrine parameter binding to prevent SQL injection.

### Array Provider

Advanced filters work with the Array provider as well. The evaluator performs in-memory comparisons:
- **Case Sensitivity**: String comparisons are performed using `mb_strtolower`.
- **Date Handling**: Supports `\DateTimeInterface` objects and `Y-m-d` date strings.
- **Type Coercion**: Performs basic type coercion (e.g., numeric strings vs numbers) to ensure consistent results.

## Export Behavior

When exporting to CSV or XLSX, the active advanced filters are automatically applied to the exported dataset, ensuring the export matches the user's current view.

## Security Boundaries

Security is a core design principle of the Advanced Filters system:

1.  **Backend-defined Fields**: Only fields explicitly declared with `addAdvancedFilterField()` can be used in expressions. Attempting to filter on undeclared fields will result in the condition being ignored.
2.  **No Arbitrary DQL/SQL**: The frontend sends a declarative JSON payload. The backend parses this payload into a structured expression tree. No raw DQL or SQL is ever accepted from the client.
3.  **Strict Operators**: The backend validates that only supported operators are used.
4.  **Parameter Binding**: All values from the frontend are treated as parameters and bound using Doctrine's secure parameter binding system. No values are ever directly concatenated into query strings.
5.  **Depth Limit**: The expression tree depth is limited (default 3) to prevent complex query exhaustion attacks.

## Limitations

- **Saved Presets**: There is currently no support for saving or sharing filter presets.
- **User Persistence**: Advanced filters are not persisted between sessions or page reloads.
- **Third-party Widgets**: The current implementation uses standard Bootstrap inputs; custom widgets like Select2 or specialized datepickers are not yet supported.
- **Collection-valued Associations**: Filtering on collection-valued associations (e.g., "Users having at least one Role with name X") is not supported.
