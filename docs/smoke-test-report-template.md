# Fresh Symfony smoke test report

> Copy this file when running a real smoke test.
>
> Suggested filename:
>
> ```text
> docs/smoke-reports/YYYY-MM-DD-local-path-repository.md
> ```

## Metadata

| Field | Value |
|---|---|
| Date | YYYY-MM-DD |
| Tester |  |
| Bundle repository | `zhortein/datatable-bundle` |
| Bundle branch |  |
| Bundle commit |  |
| Smoke app path |  |
| PHP version |  |
| Symfony version |  |
| Composer version |  |
| OS / environment |  |

## Goal

Validate the bundle in a fresh Symfony application through a local Composer path repository.

The smoke test must verify that the bundle works outside its own repository and test suite.

## Setup

### Fresh Symfony application creation

Command used:

```bash
symfony new datatable-bundle-smoke --webapp
```

or:

```bash
composer create-project symfony/skeleton datatable-bundle-smoke
cd datatable-bundle-smoke
composer require webapp
```

Result:

- [ ] Success
- [ ] Failed

Notes:

```text

```

### Path repository configuration

Composer repository added:

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

Result:

- [ ] Success
- [ ] Failed

Notes:

```text

```

### Bundle installation

Command used:

```bash
composer require zhortein/datatable-bundle:*
```

Result:

- [ ] Success
- [ ] Failed

Notes:

```text

```

### Bundle registration

Check `config/bundles.php`.

- [ ] Bundle registered automatically
- [ ] Bundle registered manually
- [ ] Failed

Notes:

```text

```

### Routes import

Route file added:

```yaml
zhortein_datatable:
    resource: '@ZhorteinDatatableBundle/config/routes.php'
```

Command:

```bash
php bin/console debug:router zhortein_datatable
```

Expected routes:

- `zhortein_datatable_fragments`
- `zhortein_datatable_export`

Result:

- [ ] Success
- [ ] Failed

Notes:

```text

```

### Translations

Command:

```bash
php bin/console debug:translation en --domain=zhortein_datatable
```

Result:

- [ ] Success
- [ ] Failed

Notes:

```text

```

### Stimulus / AssetMapper integration

Wrapper controller created:

```js
// assets/controllers/zhortein_datatable_controller.js

export { default } from '../../vendor/zhortein/datatable-bundle/assets/controllers/datatable_controller.js';
```

Result:

- [ ] Success
- [ ] Failed

Browser console:

- [ ] No JavaScript error
- [ ] Errors found

Notes:

```text

```

## Array datatable smoke test

Reference:

```text
docs/examples/array-datatable.md
```

### Declaration

- [ ] `UserArrayDatatable` created
- [ ] `#[AsDatatable]` detected
- [ ] `ArrayDataProvider` rows configured

Notes:

```text

```

### Rendering

- [ ] Page loads
- [ ] Twig function `zhortein_datatable()` works
- [ ] Datatable shell renders
- [ ] Toolbar renders
- [ ] Table headers render
- [ ] Empty/data rows render

Notes:

```text

```

### Ajax fragments

- [ ] Fragments endpoint called
- [ ] JSON response contains `body`
- [ ] JSON response contains `pagination`
- [ ] JSON response contains `summary`
- [ ] DOM updates after refresh

Notes:

```text

```

### Controls

- [ ] Search input works
- [ ] Page size selector works
- [ ] Pagination works
- [ ] Sortable headers work
- [ ] Column visibility controls work
- [ ] Filters work
- [ ] Clear filters works

Notes:

```text

```

### CSV export

- [ ] CSV current view downloads
- [ ] CSV full dataset downloads
- [ ] Headers are correct
- [ ] Hidden columns are not exported
- [ ] Filter/search/sort state is respected

Notes:

```text

```

## Doctrine datatable smoke test

Reference:

```text
docs/examples/doctrine-datatable.md
```

### Doctrine setup

- [ ] Doctrine ORM installed
- [ ] Database configured
- [ ] Entities created
- [ ] Schema created
- [ ] Test data loaded

Notes:

```text

```

### Datatable declaration

- [ ] Entity class configured
- [ ] Columns configured
- [ ] Join configured
- [ ] Permanent filters configured
- [ ] User-facing filters configured
- [ ] Row actions configured
- [ ] Global actions configured

Notes:

```text

```

### Rendering

- [ ] Doctrine datatable renders
- [ ] Joined column renders
- [ ] Typed cells render
- [ ] Custom cell template works if tested

Notes:

```text

```

### Doctrine provider behavior

- [ ] Pagination works
- [ ] Global search works
- [ ] Sorting works
- [ ] Joined field sorting works
- [ ] Filters work
- [ ] Joined field filters work
- [ ] Permanent filters work

Notes:

```text

```

### Exports

- [ ] CSV current view works
- [ ] CSV full dataset works
- [ ] Joined columns export
- [ ] Hidden columns are not exported
- [ ] Filter/search/sort state is respected

Notes:

```text

```

## Actions and security smoke test

### GET actions

- [ ] Row GET action renders as link
- [ ] Global GET action renders as link
- [ ] Route parameters resolve correctly
- [ ] Click follows expected route

Notes:

```text

```

### Non-GET actions

- [ ] Row non-GET action renders as form
- [ ] Global non-GET action renders as form
- [ ] `_method` hidden field exists
- [ ] CSRF token exists when CSRF is configured

Notes:

```text

```

### Confirmation behavior

- [ ] Confirmation message appears
- [ ] Cancel prevents navigation/submission
- [ ] Confirm allows navigation/submission

Notes:

```text

```

### Visibility extension

- [ ] Custom `ActionVisibilityCheckerInterface` can be registered
- [ ] Hidden row actions do not render
- [ ] Hidden global actions do not render

Notes:

```text

```

## Preferences smoke test

- [ ] Custom `DatatablePreferenceProviderInterface` can be registered
- [ ] Preferred page size applies
- [ ] Preferred sort applies
- [ ] Preferred visible columns apply
- [ ] Runtime options override preferences

Notes:

```text

```

## Accessibility quick check

- [ ] Search input has accessible label
- [ ] Page size selector has accessible label
- [ ] Sortable headers have accessible labels
- [ ] Active sort uses `aria-sort`
- [ ] Pagination controls have labels
- [ ] Loading state uses `role="status"`
- [ ] Error state uses `role="alert"`
- [ ] No obvious keyboard trap

Notes:

```text

```

## Issues found

### Blocking issues

| Reference | Description | Follow-up issue |
|---|---|---|
|  |  |  |

### Non-blocking issues

| Reference | Description | Follow-up issue |
|---|---|---|
|  |  |  |

## Documentation gaps

| Document | Gap | Follow-up issue |
|---|---|---|
|  |  |  |

## Final outcome

- [ ] Smoke test passed
- [ ] Smoke test passed with non-blocking findings
- [ ] Smoke test failed with blockers

## Go / no-go recommendation

- [ ] Go for first alpha
- [ ] No-go until blockers are fixed

Reason:

```text

```
