# 0002 - Initial public datatable API

## Status

Proposed

## Context

The bundle must provide a PHP-first API to declare datatables in host Symfony applications.

The target developer experience is inspired by a previous application-specific implementation, but the bundle must not reuse its architecture or source code.

The public API must remain:

- explicit;
- typed;
- Symfony-friendly;
- easy to read in business applications;
- extensible toward non-Doctrine data providers.

## Decision

A datatable is declared as a PHP class in the host application.

The class must:

- use the `#[AsDatatable]` attribute;
- implement `DatatableInterface`;
- configure a `DatatableDefinition`.

Example:

```php
<?php

declare(strict_types=1);

namespace App\Datatable;

use App\Entity\User;
use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;

#[AsDatatable(name: 'users')]
final class UserDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setEntityClass(User::class)
            ->setTranslationDomain('user')
            ->addColumn('e.id', visible: false, sortable: false, searchable: false)
            ->addColumn('e.email')
            ->addColumn('e.displayName')
            ->addColumn('e.createdAt', searchable: false)
        ;
    }
}
```

## Attribute

The initial attribute is:

```php
#[AsDatatable(
    name: 'users',
    label: 'Users',
    provider: 'doctrine',
)]
```

### `name`

The public datatable identifier.

It is used by:

- the registry;
- routes;
- Twig helpers;
- frontend data attributes.

If omitted, the bundle may infer a name from the class name in a later version, but explicit names should be recommended.

### `label`

Optional human-readable label.

It may be used by documentation, debug tooling or future admin features.

### `provider`

Optional provider identifier.

Initial expected value:

```text
doctrine
```

If omitted, the default provider from bundle configuration should be used.

## Interface

The initial interface is:

```php
interface DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void;
}
```

A context object is intentionally not introduced yet.

## Context object decision

A `DatatableContext` object may become necessary later to expose:

- the current user;
- request options;
- locale;
- route parameters;
- security context;
- runtime configuration.

For now, it is postponed.

Reasons:

- the initial foundation should remain minimal;
- context design depends on controller and provider design;
- adding context too early may create an unstable public API.

A future compatible evolution may be:

```php
interface ContextAwareDatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition, DatatableContext $context): void;
}
```

or a second optional interface.

## Definition API

The initial `DatatableDefinition` should support:

- name;
- entity class;
- translation domain;
- columns.

The following concepts are expected later, but not required in the initial API issue:

- row actions;
- global actions;
- permanent filters;
- custom joins;
- default sorting;
- pagination options;
- provider options.

## Column API

The initial `addColumn()` method should support:

```php
$definition->addColumn(
    name: 'e.email',
    label: 'Email',
    visible: true,
    sortable: true,
    searchable: true,
    className: null,
);
```

Column names may reference Doctrine aliases such as:

```text
e.email
customer.name
```

The bundle must not assume Doctrine forever, but Doctrine-style names are acceptable for the first provider.

## Consequences

The initial API remains small and easy to test.

It allows useful examples without implementing the full data-loading pipeline.

The future registry can instantiate a `DatatableDefinition`, call `buildDatatable()`, and pass the resulting definition to providers and renderers.

The context object decision is intentionally delayed to avoid designing around unknown provider/controller requirements.

## Follow-up tasks

- Implement or refine `AsDatatable`.
- Implement or refine `DatatableInterface`.
- Implement or refine `DatatableDefinition`.
- Implement or refine `ColumnDefinition`.
- Add unit tests.
- Align README examples with this decision.
