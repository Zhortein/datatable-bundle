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
- Composer 2+

Doctrine ORM is optional at package level, but required for Doctrine-backed datatables.

## Installation

Installation instructions are documented in [`docs/installation.md`](docs/installation.md).

## Basic usage

Basic usage is documented in [`docs/basic-usage.md`](docs/basic-usage.md).

Expected direction:

```php
<?php

declare(strict_types=1);

namespace App\Datatable;

use App\Entity\User;
use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterOperator;

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
            ->addPermanentFilter('e.deletedAt', FilterOperator::IsNull)
        ;
    }
}
```

Expected Twig usage:

```twig
{{ zhortein_datatable('users') }}
```

With runtime options:

```twig
{{ zhortein_datatable('users', {
    search: true
}) }}
```

## Examples

- [Minimal array datatable example](docs/examples/array-datatable.md)
- [Doctrine datatable example](docs/examples/doctrine-datatable.md)


## Documentation

- [Documentation index](docs/index.md)
- [Installation](docs/installation.md)
- [Basic usage](docs/basic-usage.md)
- [Configuration](docs/configuration.md)
- [Development workflow](docs/development.md)
- [CI matrix and dependency strategy](docs/ci.md)
- [Release workflow](docs/release.md)
- [First pre-release checklist](docs/release-checklist.md)
- [Packagist readiness](docs/packagist.md)
- [Features](docs/features.md)
- [Architecture](docs/architecture.md)
- [Documentation review checklist](docs/documentation-review.md)
- [Roadmap](docs/roadmap.md)
- [First end-to-end flow](docs/end-to-end-flow.md)
- [Doctrine-backed datatables](docs/doctrine-provider.md)
- [Actions and typed cell rendering](docs/actions-and-cells.md)
- [Routes](docs/routes.md)
- [Stimulus and AssetMapper integration](docs/stimulus-assetmapper.md)
- [Table controls and interactions](docs/table-controls.md)
- [Column visibility and preferences](docs/preferences.md)
- [User-facing filters](docs/filters.md)
- [Server-side exports](docs/exports.md)
- [Twig templates and overrides](docs/templates.md)
- [Template context reference](docs/template-context.md)
- [Optional icon rendering strategy](docs/icons.md)
- [Cell template reference](docs/cell-templates.md)
- [Theming and rendering customization](docs/theming.md)
- [Action security and visibility](docs/action-security.md)
- [Changelog strategy](docs/changelog.md)
- [Public API review](docs/public-api-review.md)

## Architecture decisions

- [0001 - Legacy code as functional reference only](docs/decisions/0001-legacy-code-as-functional-reference-only.md)
- [0002 - Initial public datatable API](docs/decisions/0002-initial-public-api.md)
- [0003 - Bootstrap rendering strategy](docs/decisions/0003-bootstrap-rendering-strategy.md)
- [0004 - Vanilla Stimulus interaction model](docs/decisions/0004-vanilla-stimulus-interaction-model.md)
- [0005 - Doctrine ORM provider architecture](docs/decisions/0005-doctrine-orm-provider-architecture.md)

## Development

```bash
composer install
composer qa
```

With the local Docker tooling:

```bash
make installdeps
make qa
```

## Quality requirements

The project must pass:

- PHPUnit;
- PHPStan at maximum level;
- PHP-CS-Fixer with Symfony-oriented rules;
- twigcs when Twig templates are present;
- GitHub Actions CI.

## License

This bundle is released under the MIT License.
