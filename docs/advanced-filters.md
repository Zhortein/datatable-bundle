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

$definition->addAdvancedFilterField(
    name: 'email',
    field: 'e.email',
    label: 'Email',
    type: FilterType::Text,
    allowedOperators: [
        FilterOperator::Equals,
        FilterOperator::Contains,
        FilterOperator::StartsWith,
    ]
);
```

### Options

| Option | Description |
|---|---|
| `name` | Public field name used in the frontend payload. |
| `field` | Provider field targeted (e.g., `e.email` or `organization.name`). |
| `label` | (Optional) Human-readable label rendered in the UI. Defaults to a capitalized version of `name`. |
| `type` | `FilterType` enum value (Text, Choice, Boolean, Date, Number). |
| `allowedOperators` | (Optional) List of `FilterOperator` allowed for this field. If empty, all operators compatible with the type are allowed. |
| `choices` | (Optional) Array of choices for `Choice` fields. |

## Supported Types and Operators

### Types

The Search Builder supports the following types from the `FilterType` enum:
- `Text`
- `Choice`
- `Boolean`
- `Date`
- `Number`

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

The Search Builder supports `AND` and `OR` logic. Users can create nested groups to build complex expressions:

- **Root Group**: The top-level group (defaults to `AND`).
- **Sub-groups**: Additional groups can be added inside other groups (up to a depth of 3).

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
