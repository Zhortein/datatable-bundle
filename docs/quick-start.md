# Quick Start

This guide creates a complete page backed by the in-memory array provider. It assumes every step in the [Installation Guide](installation.md) is complete.

## 1. Create the datatable

Create `src/Datatable/DemoUserDatatable.php`:

```php
<?php

declare(strict_types=1);

namespace App\Datatable;

use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;

#[AsDatatable(name: 'demo-users', provider: 'array')]
final class DemoUserDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setOption(ArrayDataProvider::OPTION_ROWS, [
                ['id' => 1, 'email' => 'admin@example.test', 'role' => 'ROLE_ADMIN'],
                ['id' => 2, 'email' => 'user@example.test', 'role' => 'ROLE_USER'],
            ])
            ->addColumn('id', visible: false)
            ->addColumn('email', label: 'Email')
            ->addColumn('role', label: 'Role')
        ;
    }
}
```

The `#[AsDatatable]` attribute registers the service under the `demo-users` name and explicitly selects the array provider.

## 2. Create a page controller

Create `src/Controller/DemoUserController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DemoUserController extends AbstractController
{
    #[Route('/demo/users', name: 'app_demo_users', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('demo/users.html.twig');
    }
}
```

## 3. Create the Twig template

Create `templates/demo/users.html.twig`:

```twig
{% extends 'base.html.twig' %}

{% block title %}Demo users{% endblock %}

{% block body %}
    <main class="container py-4">
        <h1>Demo users</h1>

        {{ zhortein_datatable('demo-users', {
            search: true
        }) }}
    </main>
{% endblock %}
```

## 4. Verify registration and routes

Run:

```bash
php bin/console debug:container --tag=zhortein_datatable.datatable
php bin/console debug:router app_demo_users
php bin/console debug:router zhortein_datatable_fragments
php bin/console debug:router zhortein_datatable_export
```

The datatable service and all three routes must be present before opening the page.

If labels are translation keys, set the application catalog once on the
definition with `setTranslationDomain('your_domain')`. Columns, filters,
actions, confirmations and Search Builder labels will then use the current
request locale for both the initial page and Ajax fragments. See
[declarative translations](configuration.md#translating-declarative-labels).

## 5. Open the page

Start the application with your usual local server and open:

```text
/demo/users
```

The initial response renders the table shell. The lazy Stimulus controller then requests:

```text
/_zhortein/datatable/demo-users/fragments
```

The final table contains the two email addresses, search, sortable headers, pagination and CSV export. The hidden `id` column is not displayed or exported by default.

If the shell renders but the rows never appear, use the frontend checks in the [installation troubleshooting section](installation.md#the-table-shell-appears-but-stays-empty).

## 6. Move to Doctrine

For an entity-backed table:

- declare `provider: 'doctrine'`;
- call `$definition->setEntityClass(YourEntity::class)`;
- prefix fields from the root entity with `e.`.

Continue with the [Doctrine Provider Guide](doctrine-provider.md) for joins, filters, searching, sorting and performance guidance.

## Next steps

- [Array Provider](array-provider.md)
- [Doctrine Provider](doctrine-provider.md)
- [Filters](filters.md)
- [Actions and Security](actions.md)
- [Exports](exports.md)
- [Theming and Templates](theming.md)
