# Fresh Symfony smoke test plan

This document describes how to validate `zhortein/datatable-bundle` in a fresh Symfony application before tagging a first alpha release.

The smoke test checks that the bundle works outside its own repository and test suite.

## Goals

The smoke test must verify:

- package installation through a local path repository;
- bundle registration;
- route import;
- translation loading;
- Stimulus/AssetMapper integration;
- minimal array datatable rendering;
- Doctrine-backed datatable rendering;
- Ajax fragments;
- filters;
- pagination;
- page size selector;
- column visibility;
- row/global actions;
- CSRF-aware non-GET actions;
- CSV export.

## Out of scope

The smoke test does not aim to:

- create a permanent demo application;
- test every edge case;
- benchmark performance;
- publish the package;
- validate production deployment;
- test all future 1.0 features.

## Recommended location

Create the smoke test app **outside** the bundle repository.

Example local layout:

```text
~/datatable-bundle
~/datatable-bundle-smoke
```

The smoke app should not be committed into the bundle repository.

## Prerequisites

Recommended local requirements:

- PHP 8.4+
- Composer 2+
- Symfony CLI or Composer create-project workflow
- SQLite support for Doctrine smoke tests
- Git
- a local clone of `zhortein/datatable-bundle`

## 1. Create a fresh Symfony application

Using Symfony CLI:

```bash
cd ~
symfony new datatable-bundle-smoke --webapp
cd datatable-bundle-smoke
```

Alternative using Composer:

```bash
cd ~
composer create-project symfony/skeleton datatable-bundle-smoke
cd datatable-bundle-smoke
composer require webapp
```

## 2. Add the bundle as a path repository

Edit the smoke app `composer.json`.

Add:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../datatable-bundle",
      "options": {
        "symlink": true
      }
    }
  ]
}
```

Then require the bundle:

```bash
composer require zhortein/datatable-bundle:*
```

If the package is not stable yet, the smoke application may need:

```json
{
  "minimum-stability": "dev",
  "prefer-stable": true
}
```

or a branch alias/version constraint appropriate for the current repository state.

## 3. Register the bundle

If Symfony Flex does not register the bundle automatically, add it manually:

```php
// config/bundles.php

return [
    // ...
    Zhortein\DatatableBundle\ZhorteinDatatableBundle::class => ['all' => true],
];
```

## 4. Import bundle routes

Create a route file:

```yaml
# config/routes/zhortein_datatable.yaml

zhortein_datatable:
    resource: '@ZhorteinDatatableBundle/config/routes.php'
```

Verify routes:

```bash
php bin/console debug:router zhortein_datatable
```

Expected routes include:

```text
zhortein_datatable_fragments
zhortein_datatable_export
```

## 5. Check translations

Ensure Symfony Translation is enabled in the application.

Run:

```bash
php bin/console debug:translation en --domain=zhortein_datatable
```

Expected keys include:

```text
zhortein_datatable.search.label
zhortein_datatable.empty
zhortein_datatable.actions
zhortein_datatable.export.label
```

Also check French if relevant:

```bash
php bin/console debug:translation fr --domain=zhortein_datatable
```

## 6. Expose the Stimulus controller

Until automatic controller registration or a Flex recipe exists, create a wrapper controller in the smoke app.

Create:

```text
assets/controllers/zhortein_datatable_controller.js
```

Content:

```js
export { default } from '../../vendor/zhortein/datatable-bundle/assets/controllers/datatable_controller.js';
```

Then ensure AssetMapper/Stimulus setup is installed:

```bash
composer require symfony/asset-mapper symfony/stimulus-bundle
```

Check that the application includes the importmap / asset rendering expected by the Symfony app skeleton.

## 7. Create a page to render datatables

Create a controller:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DatatableDemoController extends AbstractController
{
    #[Route('/datatable-demo', name: 'app_datatable_demo')]
    public function __invoke(): Response
    {
        return $this->render('datatable_demo/index.html.twig');
    }
}
```

Create the template:

```twig
{# templates/datatable_demo/index.html.twig #}

{% extends 'base.html.twig' %}

{% block body %}
    <main class="container py-4">
        <h1>Datatable demo</h1>

        {{ zhortein_datatable('demo-users', {
            search: true,
            pageSize: 10,
            pageSizeSelector: true,
            allowedPageSizes: [10, 25, 50],
            export: true
        }) }}
    </main>
{% endblock %}
```

Open:

```text
/datatable-demo
```

## 8. Smoke test the minimal array datatable

Follow the documented example:

```text
docs/examples/array-datatable.md
```

Create a datatable class:

```text
src/Datatable/UserArrayDatatable.php
```

Expected checks:

- [ ] page loads without exception;
- [ ] datatable shell renders;
- [ ] table header renders;
- [ ] empty/data state renders correctly;
- [ ] search input appears;
- [ ] filters appear;
- [ ] page size selector appears;
- [ ] column visibility control appears;
- [ ] export control appears;
- [ ] Ajax fragments endpoint is called;
- [ ] search refreshes data;
- [ ] page size refreshes data;
- [ ] column visibility refreshes data;
- [ ] CSV export downloads a file.

Useful browser checks:

- DevTools Console has no JavaScript error.
- DevTools Network shows the fragments request.
- The fragments response contains JSON with `body`, `pagination`, `summary`.

## 9. Smoke test Doctrine datatable

Install Doctrine if not already installed:

```bash
composer require doctrine/orm doctrine/doctrine-bundle
```

Use SQLite for the smoke test if convenient.

Example `.env.local`:

```dotenv
DATABASE_URL="sqlite:///%kernel.project_dir%/var/smoke.db"
```

Create sample entities:

- `User`
- `Organization`

Follow:

```text
docs/examples/doctrine-datatable.md
```

Create migrations or schema manually.

Example quick schema command for a disposable app:

```bash
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:schema:update --force
```

Add fixtures manually, through a command, or with a temporary controller/fixture class.

Expected checks:

- [ ] Doctrine datatable renders;
- [ ] joined organization column renders;
- [ ] permanent filters apply;
- [ ] user-facing filters apply;
- [ ] global search works;
- [ ] sorting works;
- [ ] pagination works;
- [ ] CSV current export works;
- [ ] CSV full export works.

## 10. Smoke test row and global actions

Add simple host application routes:

```php
#[Route('/users/{id}', name: 'app_user_show')]
public function show(int $id): Response
{
    return new Response(sprintf('Show user %d', $id));
}
```

```php
#[Route('/users/create', name: 'app_user_create')]
public function create(): Response
{
    return new Response('Create user');
}
```

For non-GET action:

```php
#[Route('/users/{id}/delete', name: 'app_user_delete', methods: ['POST', 'DELETE'])]
public function delete(int $id): Response
{
    return new Response(sprintf('Delete user %d', $id));
}
```

Expected checks:

- [ ] GET row actions render as links;
- [ ] global action renders in toolbar;
- [ ] non-GET action renders as form;
- [ ] CSRF token is present when CSRF is configured;
- [ ] confirmation message triggers browser confirmation;
- [ ] canceling confirmation prevents action.

## 11. Smoke test action visibility extension

Create a custom checker:

```php
<?php

declare(strict_types=1);

namespace App\Datatable;

use Zhortein\DatatableBundle\Action\ActionVisibilityCheckerInterface;
use Zhortein\DatatableBundle\Action\ActionVisibilityContext;
use Zhortein\DatatableBundle\Definition\ActionDefinition;

final class DemoActionVisibilityChecker implements ActionVisibilityCheckerInterface
{
    public function isVisible(ActionDefinition $action, ActionVisibilityContext $context): bool
    {
        if ('delete' !== $action->getName()) {
            return true;
        }

        $row = $context->getRow();

        return is_array($row) && true !== ($row['e_locked'] ?? false);
    }
}
```

Register it:

```yaml
services:
    App\Datatable\DemoActionVisibilityChecker: ~

    Zhortein\DatatableBundle\Action\ActionVisibilityCheckerInterface:
        alias: App\Datatable\DemoActionVisibilityChecker
```

Expected checks:

- [ ] replacing the checker works;
- [ ] hidden row actions do not render;
- [ ] visible actions still render.

## 12. Smoke test preferences extension point

Create a custom preference provider:

```php
<?php

declare(strict_types=1);

namespace App\Datatable;

use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Preference\DatatablePreference;
use Zhortein\DatatableBundle\Preference\DatatablePreferenceProviderInterface;

final class DemoPreferenceProvider implements DatatablePreferenceProviderInterface
{
    public function getPreference(string $datatableName): DatatablePreference
    {
        return DatatablePreference::create(
            pageSize: 50,
            sortField: 'e.email',
            sortDirection: SortDirection::Asc,
            visibleColumns: ['e.email', 'e.displayName'],
        );
    }
}
```

Register it:

```yaml
services:
    App\Datatable\DemoPreferenceProvider: ~

    Zhortein\DatatableBundle\Preference\DatatablePreferenceProviderInterface:
        alias: App\Datatable\DemoPreferenceProvider
```

Expected checks:

- [ ] page size preference applies;
- [ ] sort preference applies;
- [ ] visible columns preference applies;
- [ ] explicit Twig options override preference values.

## 13. Smoke test exports

Expected checks:

- [ ] CSV current view downloads;
- [ ] CSV full dataset downloads;
- [ ] filename is reasonable;
- [ ] hidden columns are not exported;
- [ ] joined columns export;
- [ ] filters/search/sort affect exported rows as documented.

URLs:

```text
/_zhortein/datatable/demo-users/export/csv?mode=current
/_zhortein/datatable/demo-users/export/csv?mode=full
```

## 14. Smoke test accessibility basics

Manual checks:

- [ ] search input has an accessible label;
- [ ] page size selector has an accessible label;
- [ ] sortable headers have accessible labels;
- [ ] active sort uses `aria-sort`;
- [ ] pagination has labels;
- [ ] loading state uses `role="status"`;
- [ ] error state uses `role="alert"`;
- [ ] no obvious keyboard trap.

This is not a full accessibility audit.

## 15. Record findings

Create a smoke test note with:

```text
Symfony version:
PHP version:
Bundle branch/commit:
Date:
Tester:
```

Record:

- setup issues;
- documentation gaps;
- runtime errors;
- JavaScript errors;
- missing dependencies;
- unexpected behavior;
- blockers;
- non-blocking improvements.

## 16. Go / no-go outcome

After the smoke test, classify findings.

### Blocking

Issues that prevent first alpha:

- bundle cannot install;
- bundle cannot boot;
- routes cannot load;
- Twig function unavailable;
- basic array datatable cannot render;
- Ajax fragments fail;
- serious documented setup step missing.

### Non-blocking

Issues that can become follow-up tasks:

- minor visual polish;
- optional docs improvement;
- missing advanced feature;
- known limitation already documented.

## 17. Follow-up

For every blocker:

- create a GitHub issue;
- link it to the alpha preparation milestone;
- do not tag alpha until resolved.

For non-blocking findings:

- create follow-up issues;
- mark them as future milestone candidates.
