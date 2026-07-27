# Computed cell example

This complete example builds a Doctrine-backed account summary from selected
scalar fields. The same resolver drives the HTML cell and CSV/XLSX exports.

## Resolver

Create `src/Datatable/Cell/AccountSummaryResolver.php`:

```php
<?php

declare(strict_types=1);

namespace App\Datatable\Cell;

use Zhortein\DatatableBundle\Cell\CellContext;
use Zhortein\DatatableBundle\Contract\CellValueResolverInterface;

final readonly class AccountSummaryResolver implements CellValueResolverInterface
{
    public function getName(): string
    {
        return 'account_summary';
    }

    public function resolve(CellContext $context): mixed
    {
        $row = $context->getRow();
        $email = (string) ($row['e_email'] ?? '');
        $displayName = (string) ($row['e_displayName'] ?? '');

        return [
            'email' => $email,
            'displayName' => $displayName,
            'locale' => (string) $context->getDatatableContext()->get('locale', 'en'),
        ];
    }
}
```

The service is registered automatically with the standard Symfony service
defaults:

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    App\:
        resource: '../src/'
```

## Datatable definition

```php
<?php

declare(strict_types=1);

namespace App\Datatable;

use App\Entity\User;
use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;

#[AsDatatable(name: 'users', provider: 'doctrine')]
final class UserDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setEntityClass(User::class)
            ->setContext(new DatatableContext([
                'locale' => 'en',
            ], [
                'locale',
            ]))
            ->addColumn('e.id', visible: false, sortable: false, searchable: false)
            ->addColumn('e.email', visible: false)
            ->addColumn('e.displayName', visible: false)
            ->addComputedColumn(
                name: 'account_summary',
                valueResolver: 'account_summary',
                label: 'Account',
                template: 'datatable/cell/account_summary.html.twig',
                type: 'array',
                exportable: true,
            )
        ;
    }
}
```

Doctrine selects `e.id`, `e.email` and `e.displayName`. It does not try to
select the virtual `account_summary` name and does not hydrate the `User`
entity.

## Twig cell

Create `templates/datatable/cell/account_summary.html.twig`:

```twig
<div>
    <strong>{{ value.displayName }}</strong>
    <div class="small text-body-secondary">{{ value.email }}</div>
    <span class="badge text-bg-light">{{ value.locale|upper }}</span>
</div>
```

The custom template could also use `row`, `row_identifier`, `datatable`,
`datatable_context` or the canonical `cell` DTO.

## Rendering

```twig
{{ zhortein_datatable('users', {
    search: true,
    exportFormats: ['csv', 'xlsx'],
    context: {
        locale: app.request.locale
    }
}) }}
```

The initial shell contains no entity or row data. The fragment endpoint runs
the resolver on the server and returns rendered HTML. Export endpoints run the
same resolver and normalize its returned array according to the selected
format.

## Array-provider variant

The same resolver works with an array definition. Array rows are also supplied
as `source`, so a resolver may access `getSource()` without an additional
query:

```php
$definition
    ->setOption(ArrayDataProvider::OPTION_ROWS, [
        ['id' => 1, 'email' => 'alice@example.test', 'displayName' => 'Alice'],
    ])
    ->addColumn('email', visible: false)
    ->addColumn('displayName', visible: false)
    ->addComputedColumn(
        name: 'account_summary',
        valueResolver: 'account_summary',
        label: 'Account',
        type: 'array',
    )
;
```

Adapt the resolver keys from `e_email`/`e_displayName` to
`email`/`displayName` for that definition, or use a resolver dedicated to the
array projection.

## Related documentation

- [Cell context and computed values](../cell-context.md)
- [Doctrine provider](../doctrine-provider.md)
- [Array provider](../array-provider.md)
- [Exports](../exports.md)
