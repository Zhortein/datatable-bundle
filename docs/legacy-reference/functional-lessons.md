# Legacy Datatable Functional Lessons

This document summarizes the functional lessons learned from a previous application-specific datatable implementation.

The legacy implementation must be considered as functional inspiration only. It must not be copied into this bundle.

## Validated concepts

### PHP-first datatable declarations

A datatable should be declared as a PHP class in the host application.

This provides:
- type safety;
- IDE autocompletion;
- explicit business rules;
- easy customization per use case;
- compatibility with Symfony dependency injection.

### Attribute-based discovery

A PHP attribute should mark classes that define datatables.

Expected developer experience:

```php
#[AsDatatable(name: 'users')]
final class UserDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        // Datatable definition goes here.
    }
}
```

The final bundle should use Symfony service autoconfiguration and service tags to discover these datatables.

### Fluent column definition API

The legacy implementation proved that a fluent API is pleasant for declaring columns.
Expected developer experience:
```php
$definition
    ->setEntityClass(User::class)
    ->addColumn('e.id', visible: false)
    ->addColumn('e.email')
    ->addColumn('e.createdAt')
;
```
The final API should remain explicit, typed and readable.

### Doctrine metadata inspection
Doctrine metadata can be used to infer useful information about fields:

- field type;
- enum support;
- association path;
- default rendering type;
- sortable/searchable capabilities.

This feature is important because it reduces repetitive configuration.

### Persistent filters
Datatables often need permanent filters that are not controlled by the frontend.

Examples:
- hide soft-deleted rows;
- restrict rows according to the current user;
- restrict rows according to a parent object;
- restrict rows according to application context.

The final bundle should support persistent filters as first-class objects.

### Custom joins
Some business cases require custom joins that are not directly represented by Doctrine associations.

The final bundle should support this use case, but with a safer and more explicit API than raw unstructured strings whenever possible.

### Twig cell rendering
Cell values should be rendered through Twig templates.

Useful defaults:

- string;
- numeric;
- boolean;
- date/datetime;
- array/json;
- custom actions;
- selector checkbox.

The final bundle should provide Bootstrap-first default templates and allow host applications to override them.

### Ajax-based refresh
The frontend should update data through Ajax calls.

The final implementation should use:

- Symfony routes;
- a Stimulus controller;
- vanilla JavaScript;
- JSON or server-rendered HTML partials depending on the selected architecture. 

### Declarative actions

Datatables need row and global actions.

Examples:

- view;
- edit;
- delete;
- restore;
- custom business operation.

Actions should be declared in PHP and rendered consistently by the bundle.

## Final direction

The legacy implementation validated the business value and developer experience.

The new bundle must keep the concepts but rebuild the architecture from scratch with clear responsibilities.