# Zhortein Datatable Bundle

A Symfony 8+ bundle for Bootstrap-first business datatables driven by PHP definitions.

## Status

Early development. Not production-ready.

## Goals

- PHP-first datatable definitions.
- PHP attributes.
- Bootstrap-first rendering.
- Twig templates.
- Stimulus Ajax refresh.
- Vanilla JavaScript.
- Doctrine ORM provider.
- Declarative actions.
- Native Symfony translations.
- Extensible provider architecture.

## Requirements

- PHP 8.4+
- Symfony 8+

## Development

```bash
composer install
composer qa
```

## Example

```php
use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;

#[AsDatatable(name: 'user')]
final class UserDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setEntityClass(User::class)
            ->addColumn('e.id', visible: false)
            ->addColumn('e.email')
            ->addColumn('e.createdAt')
        ;
    }
}
```
