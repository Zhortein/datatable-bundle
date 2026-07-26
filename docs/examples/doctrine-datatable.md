# Doctrine datatable example

This example shows a realistic Doctrine-backed datatable.

It demonstrates:

- entity-class based datatable declaration;
- scalar columns;
- joined entity columns;
- permanent filters;
- user-facing filters;
- row actions;
- global actions;
- typed cell rendering;
- Twig rendering;
- CSV export controls.

## Context

Assume a host application with two entities:

```text
User
Organization
```

A `User` belongs to an optional `Organization`.

The datatable lists users and displays their organization name.

## Example entities

The exact entities are not part of the bundle, but this is the expected shape.

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Organization
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180)]
    private string $name;

    #[ORM\Column(type: 'boolean')]
    private bool $enabled = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180)]
    private string $email;

    #[ORM\Column(type: 'string', length: 120)]
    private string $displayName;

    #[ORM\Column(type: 'boolean')]
    private bool $enabled = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    private ?Organization $organization = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }
}
```

## Datatable class

Create a datatable class in the host application.

Example path:

```text
src/Datatable/UserDatatable.php
```

Example:

```php
<?php

declare(strict_types=1);

namespace App\Datatable;

use App\Entity\User;
use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Definition\RouteParameter;
use Zhortein\DatatableBundle\Enum\FilterOperator;
use Zhortein\DatatableBundle\Enum\FilterType;
use Zhortein\DatatableBundle\Enum\JoinType;

#[AsDatatable(name: 'users', provider: 'doctrine')]
final class UserDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setEntityClass(User::class)
            ->setTranslationDomain('user')
            ->addJoin('organization', 'e.organization', JoinType::Left)

            ->addColumn('e.id', label: 'Id', visible: false, sortable: false, searchable: true)
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.displayName', label: 'Display name')
            ->addColumn('e.enabled', label: 'Enabled', type: 'boolean')
            ->addColumn('e.createdAt', label: 'Created at', type: 'datetime', searchable: false)
            ->addColumn('organization.name', label: 'Organization')

            ->addPermanentFilter('e.enabled', FilterOperator::Equals, true)

            ->addFilter(
                name: 'email',
                field: 'e.email',
                label: 'Email',
                type: FilterType::Text,
                placeholder: 'Search an email',
            )
            ->addFilter(
                name: 'organization_name',
                field: 'organization.name',
                label: 'Organization',
                type: FilterType::Text,
                placeholder: 'Search an organization',
            )
            ->addFilter(
                name: 'enabled',
                field: 'e.enabled',
                label: 'Enabled',
                type: FilterType::Boolean,
            )

            ->addRowAction(
                name: 'view',
                route: 'app_user_show',
                label: 'View',
                icon: 'bi bi-eye',
                routeParameters: [
                    'id' => RouteParameter::row('e.id'),
                ],
                className: 'btn btn-sm btn-outline-primary',
                permission: 'USER_VIEW',
            )
            ->addRowAction(
                name: 'edit',
                route: 'app_user_edit',
                label: 'Edit',
                icon: 'bi bi-pencil',
                routeParameters: [
                    'id' => RouteParameter::row('e.id'),
                ],
                className: 'btn btn-sm btn-outline-secondary',
                permission: 'USER_EDIT',
            )
            ->addRowAction(
                name: 'delete',
                route: 'app_user_delete',
                label: 'Delete',
                icon: 'bi bi-trash',
                httpMethod: 'DELETE',
                confirmationMessage: 'Delete this user?',
                routeParameters: [
                    'id' => RouteParameter::row('e.id'),
                ],
                className: 'btn btn-sm btn-outline-danger',
                permission: 'USER_DELETE',
            )

            ->addGlobalAction(
                name: 'create',
                route: 'app_user_create',
                label: 'Create user',
                icon: 'bi bi-plus-lg',
                className: 'btn btn-sm btn-primary',
                permission: 'USER_CREATE',
            )
        ;
    }
}
```

## Render the datatable

Render from Twig:

```twig
{{ zhortein_datatable('users', {
    search: true,
    pageSize: 25,
    allowedPageSizes: [10, 25, 50, 100],
    pageSizeSelector: true,
    columnVisibility: true,
    export: true
}) }}
```

## What this example supports

With the current bundle implementation, this datatable supports:

- server-side Doctrine loading;
- pagination;
- global search;
- user-facing filters;
- permanent backend filters;
- joined entity column display;
- search on joined fields;
- sorting on joined fields;
- permanent filters on joined fields;
- row actions;
- global actions;
- CSRF-aware non-GET action forms;
- action confirmation metadata;
- native Stimulus confirmation;
- column visibility controls;
- page size selector;
- CSV exports;
- typed cell templates.

## Main alias

The Doctrine provider uses the main alias:

```text
e
```

Scalar fields from the main entity should be declared with:

```php
$definition->addColumn('e.email');
```

## Joins

Associations must be declared explicitly.

```php
$definition->addJoin('organization', 'e.organization', JoinType::Left);
```

After declaring the join, columns can use the join alias:

```php
$definition->addColumn('organization.name', label: 'Organization');
```

## Permanent filters

Permanent filters are backend-defined.

They are never controlled by the frontend.

```php
$definition->addPermanentFilter('e.enabled', FilterOperator::Equals, true);
```

Permanent filters affect:

- loaded rows;
- total visible item count;
- filtered item count.

## User-facing filters

User-facing filters are declared in PHP and rendered in the toolbar.

```php
$definition->addFilter(
    name: 'email',
    field: 'e.email',
    label: 'Email',
    type: FilterType::Text,
);
```

The frontend sends:

```text
filters[email]=alice
```

Only declared filters are applied.

## Actions

### GET row actions

GET row actions are rendered as links.

```php
$definition->addRowAction(
    name: 'view',
    route: 'app_user_show',
    label: 'View',
    routeParameters: [
        'id' => 'e.id',
    ],
);
```

### Non-GET row actions

Non-GET row actions are rendered as forms.

```php
$definition->addRowAction(
    name: 'delete',
    route: 'app_user_delete',
    label: 'Delete',
    httpMethod: 'DELETE',
    confirmationMessage: 'Delete this user?',
    routeParameters: [
        'id' => 'e.id',
    ],
);
```

When a CSRF token manager is available, the form includes a `_token` field.

### Action visibility

The example uses the dedicated `permission` option:

```php
permission: 'USER_DELETE',
```

This metadata is used only if the application enables the optional authorization action visibility adapter. It is not rendered in the HTML.

See [`../actions.md`](../actions.md).

## Column visibility

Column visibility controls are enabled by default.

A user can show/hide rendered columns in the current table state.

Runtime column visibility can also be passed explicitly:

```twig
{{ zhortein_datatable('users', {
    visibleColumns: ['e.email', 'e.displayName', 'organization.name'],
    hiddenColumns: ['e.createdAt']
}) }}
```

## CSV exports

CSV export controls are enabled by default.

The toolbar renders:

- CSV current view;
- CSV full dataset.

Disable exports:

```twig
{{ zhortein_datatable('users', {
    export: false
}) }}
```

## Custom cell template example

For a custom enabled/disabled badge:

```php
$definition->addColumn(
    name: 'e.enabled',
    label: 'Enabled',
    type: 'boolean',
    template: 'admin/datatable/cell/enabled.html.twig',
);
```

Example template:

```twig
{% if value %}
    <span class="badge text-bg-success">Enabled</span>
{% else %}
    <span class="badge text-bg-secondary">Disabled</span>
{% endif %}
```

## Current limitations

This example avoids features not yet implemented.

Current limitations:

- no automatic association traversal;
- no deep joins;
- no collection joins;
- no multi-column sorting;
- no saved filter presets;
- no persisted column preferences;
- no built-in action controllers;
- no built-in voters;
- no asynchronous exports yet.

## Related documentation

- [`../doctrine-provider.md`](../doctrine-provider.md)
- [`../filters.md`](../filters.md)
- [`../actions.md`](../actions.md)
- [`../exports.md`](../exports.md)
- [`../preferences.md`](../preferences.md)
- [`../ui-ux.md`](../ui-ux.md)
- [`../theming.md`](../theming.md)

## Smoke-test validation

This example has been validated in a fresh Symfony application using a Composer path repository.

Validated behavior:

- Doctrine entity mapping;
- joined entity column display;
- permanent filters;
- user-facing filters;
- sorting;
- pagination;
- Twig rendering;
- Ajax fragments;
- CSV export.

The smoke test used SQLite locally, but the feature is provider-level Doctrine ORM behavior and is not SQLite-specific.
