# Multi-column sorting

The bundle supports ordered multi-column sorting while keeping the original
single-column interaction and query parameters backward compatible.

## User interaction

A regular click or keyboard activation keeps the familiar single-column
behavior:

1. activating a new column replaces the current ordering and starts ascending;
2. activating the same single column toggles between ascending and descending.

Hold `Shift` while clicking a header or activating it with the keyboard to
build an ordered list:

1. an inactive column is appended ascending;
2. the next activation changes it to descending;
3. the next activation removes it.

The small numbered badges show the effective priority. Priority `1` is applied
first, then priority `2` resolves equal values, and so on.

The bundle accepts at most eight unique criteria. This keeps user-controlled
request and URL state bounded without imposing a database-specific query
policy on host applications.

## Initial sorting

Use the `sorts` Twig option to render an initial multi-column order:

```twig
{{ zhortein_datatable('users', {
    sorts: [
        { field: 'e.enabled', direction: 'desc' },
        { field: 'e.displayName', direction: 'asc' }
    ]
}) }}
```

The existing options remain supported:

```twig
{{ zhortein_datatable('users', {
    sortField: 'e.email',
    sortDirection: 'asc'
}) }}
```

When `sorts` is present, its first criterion is also exposed through
`sortField` and `sortDirection` for compatibility.

## PHP request API

`Sorting\SortCriterion` is the typed public value object:

```php
use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Sorting\SortCriterion;

$request = DatatableRequest::create(sorts: [
    SortCriterion::create('e.enabled', SortDirection::Desc),
    SortCriterion::create('e.displayName'),
]);

foreach ($request->getSorts() as $criterion) {
    $criterion->getField();
    $criterion->getDirection();
}
```

The compatibility accessors return the primary criterion:

```php
$request->getSortField();     // e.enabled
$request->getSortDirection(); // SortDirection::Desc
```

Custom providers should iterate over `getSorts()` in order, validate each field
against their own declared sortable-column capabilities, and ignore
unsupported criteria independently. They must never concatenate
user-controlled field names into a query.

## Preferences

Applications can provide an ordered default through the existing preference
extension point:

```php
use Zhortein\DatatableBundle\Preference\DatatablePreference;
use Zhortein\DatatableBundle\Sorting\SortCriterion;

$preference = DatatablePreference::create(sorts: [
    SortCriterion::create('e.status'),
    SortCriterion::create('e.createdAt', 'desc'),
]);
```

Runtime Twig options still override preferences. An explicit legacy
`sortField` or `sortDirection` option replaces a preferred multi-column list,
preserving the 1.x precedence rules.

## HTTP transport and compatibility

Fragments and exports send the ordered list as nested query parameters:

```text
sorts[0][field]=e.enabled
sorts[0][direction]=desc
sorts[1][field]=e.displayName
sorts[1][direction]=asc
```

They also send the primary compatibility pair:

```text
sortField=e.enabled
sortDirection=desc
```

Requests that contain only this historical pair continue to produce one
criterion. Malformed list entries are ignored, duplicate fields keep their
first occurrence, and the request factory reads no more than eight valid
criteria.

## URL state, saved views and exports

Version 1 `DatatableState` includes an ordered `sorts` list and retains
`sortField`/`sortDirection` for compatibility. Version 1 URLs and saved views
created before `1.8` remain valid when the list is absent.

The complete list is reused by:

- namespaced page URL state and browser history;
- named saved views;
- Ajax fragments;
- current-page CSV and XLSX exports;
- full filtered CSV and XLSX exports.

The Array and Doctrine providers therefore receive the same order for screen
rendering and export execution.

## Provider behavior

Both built-in providers apply only declared sortable columns:

- `ArrayDataProvider` compares each criterion until one differs;
- `DoctrineOrmDataProvider` emits one `ORDER BY` item per supported criterion;
- explicit joined Doctrine fields work like main-entity fields;
- an unsupported or non-sortable criterion does not prevent later valid
  criteria from being applied.

String collation and null placement remain provider-specific. In-memory natural
case-insensitive comparison and a database collation can produce different
orders for locale-sensitive strings. Applications that need identical
cross-provider collation should normalize source values or implement an
application-specific provider.

## Accessibility

Every sortable header remains a native button. Its accessible name explains
the `Shift` interaction and, when active, announces its direction and priority.

ARIA defines `aria-sort` for the primary sorted header. The bundle therefore
sets it only on priority `1`; secondary directions and priorities are conveyed
through their accessible names and visible numbered badges. This avoids
claiming several simultaneous primary sort columns.

## Related documentation

- [UI/UX and controls](ui-ux.md)
- [URL state and browser history](url-state.md)
- [Named saved views](saved-views.md)
- [Server-side exports](exports.md)
- [Providers](providers.md)
