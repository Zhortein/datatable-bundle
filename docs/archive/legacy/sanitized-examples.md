# Sanitized Datatable Examples

This document provides public, generic examples inspired by a previous application-specific implementation.

The examples are intentionally anonymized and simplified.

## Basic datatable

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

## Datatable with contextual filters

```php
<?php

declare(strict_types=1);

namespace App\Datatable;

use App\Entity\Order;
use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterOperator;

#[AsDatatable(name: 'orders')]
final class OrderDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setEntityClass(Order::class)
            ->setTranslationDomain('order')
            ->addColumn('e.id', visible: false, sortable: false, searchable: false)
            ->addColumn('e.reference')
            ->addColumn('e.customerName')
            ->addColumn('e.status')
            ->addColumn('e.createdAt', searchable: false)
            ->addPermanentFilter('e.deletedAt', FilterOperator::IsNull)
        ;
    }
}
```

## Datatable with actions

```php
<?php

declare(strict_types=1);

namespace App\Datatable;

use App\Entity\Product;
use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;

#[AsDatatable(name: 'products')]
final class ProductDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setEntityClass(Product::class)
            ->setTranslationDomain('product')
            ->addColumn('e.id', visible: false, sortable: false, searchable: false)
            ->addColumn('e.name')
            ->addColumn('e.sku')
            ->addColumn('e.price', searchable: false, className: 'text-end')
            ->addRowAction('view', route: 'app_product_show')
            ->addRowAction('edit', route: 'app_product_edit')
        ;
    }
}
```

## Expected Twig usage

```twig
{{ zhortein_datatable('users') }}
```

The exact Twig API is not final yet.

## Expected Stimulus behavior
The generated HTML should include enough data attributes for the Stimulus controller to load and refresh the datatable.
```html
<div
    data-controller="zhortein--datatable-bundle--datatable"
    data-zhortein--datatable-bundle--datatable-name-value="users"
    data-zhortein--datatable-bundle--datatable-data-url-value="/_zhortein/datatable/users/data"
>
    <!-- Datatable markup -->
</div>
```
The final HTML structure will be defined when the rendering layer is implemented.