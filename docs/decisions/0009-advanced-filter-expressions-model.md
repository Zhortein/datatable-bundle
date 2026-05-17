# 0009 - Advanced filter expressions model

## Status

Proposed

## Context

The current filtering system in `zhortein/datatable-bundle` is limited to a flat list of filters that are always combined using the `AND` logic operator.
To support more complex scenarios (e.g., "(A OR B) AND (C OR D)"), we need a more flexible model: **Advanced Filter Expressions**.

The goal is to provide a backend-controlled model that allows the frontend to send complex filter trees while maintaining strict security boundaries. We must avoid exposing Doctrine internals (DQL, SQL) or allowing arbitrary expression injection.

## Terminology

- **Advanced Filter Expression**: The full tree structure representing the complex filtering logic.
- **Condition**: A leaf node in the expression tree, consisting of a field, a comparison operator, and one or more values.
- **Group**: A node in the expression tree that contains one or more children (Conditions or other Groups) and a logic operator to combine them.
- **Logic Operator**: An operator used to combine elements within a Group (e.g., `AND`, `OR`).
- **Comparison Operator**: An operator used in a Condition to compare a field against values (e.g., `equals`, `contains`, `greater than`).

## Decision

We will implement a recursive, tree-based model for advanced filter expressions.

### Payload Shape

The payload will be a JSON-serializable structure. A root element can be either a **Condition** or a **Group**.

#### Group Object
```json
{
  "type": "group",
  "logic": "AND",
  "children": [
    // ... Conditions or Groups
  ]
}
```

#### Condition Object
```json
{
  "type": "condition",
  "field": "email",
  "operator": "contains",
  "value": "gmail.com"
}
```

### Supported Field Types

The advanced filter system will support the following field types, mapped from existing `FilterType` where possible:
- `string` / `text`
- `number`
- `boolean`
- `date` / `datetime`
- `choice`

### Supported Operators

| Operator | Internal Enum Value | Description | Supported Types |
|---|---|---|---|
| Equals | `eq` | Field equals value | All |
| Not Equals | `neq` | Field does not equal value | All |
| Contains | `contains` | Field contains substring | string |
| Not Contains | `not_contains` | Field does not contain substring | string |
| Starts With | `starts_with` | Field starts with substring | string |
| Ends With | `ends_with` | Field ends with substring | string |
| Greater Than | `gt` | Field > value | number, date |
| Greater Than Or Equals | `gte` | Field >= value | number, date |
| Less Than | `lt` | Field < value | number, date |
| Less Than Or Equals | `lte` | Field <= value | number, date |
| Between | `between` | Field between val1 and val2 | number, date |
| Is Null | `is_null` | Field is null | All |
| Is Not Null | `is_not_null` | Field is not null | All |
| In | `in` | Field in list of values | All |
| Not In | `not_in` | Field not in list of values | All |

### Logic Operators

- `AND`: All children must be true.
- `OR`: At least one child must be true.

### Nesting Policy

- **Max Depth**: To prevent stack overflow and overly complex queries, a maximum depth of **3** (including the root) will be enforced.
- **Root**: The root of an advanced filter expression must be a **Group**.

### Security Boundaries

1. **Declared Fields Only**: Only fields explicitly marked as `filterable` in the `DatatableDefinition` can be used in conditions.
2. **Operator Validation**: Each field type will have a whitelist of allowed operators.
3. **No Arbitrary DQL/SQL**: The backend translates the declarative payload into DQL/SQL using a secure builder. No raw DQL/SQL is ever accepted from the frontend.
4. **Parameter Binding**: All values provided by the frontend MUST be bound as parameters in the resulting query.
5. **No Join Injection**: Frontend cannot specify joins. Joins must be defined in the backend `DatatableDefinition`.

### Provider Mapping

#### Doctrine Provider
- Groups are translated into `Andx` or `Orx` expressions.
- Conditions are translated into DQL comparison expressions.
- Field names are resolved against the QueryBuilder aliases.

#### Array Provider
- Groups are translated into PHP closures using `array_filter` logic.
- Conditions are translated into PHP comparison logic.

## Out of Scope (First Version)

- **Saved Filters**: Persisting user-defined expressions in a database.
- **User Presets**: Allowing users to save and load named filter sets.
- **Async Filtering**: Fetching filter options (like choices) asynchronously based on other filters.
- **Custom Widgets**: Supporting third-party JS widgets (Select2, Flatpickr) within the search builder UI.
- **Collection-Valued Associations**: Filtering based on "has any of" or "has all of" for collections.
- **Arbitrary Expression Language**: Using Symfony ExpressionLanguage or similar for complex calculations.

## Consequences

- The frontend can build complex query logic using a "Search Builder" UI.
- The backend remains the source of truth for security and field availability.
- The implementation is decoupled from the underlying data source (Doctrine or Array).
- Clear boundaries prevent SQL injection and unauthorized data access.
