# Fresh Symfony smoke test report — local path repository

## Metadata

| Field | Value |
|---|---|
| Date | 2026-05-09 |
| Tester | David |
| Bundle repository | `zhortein/datatable-bundle` |
| Bundle branch | `develop` |
| Bundle installation mode | Composer path repository with symlink |
| Smoke app | Fresh Symfony application |
| PHP version | 8.4 |
| Symfony version | 8.x |
| Frontend setup | AssetMapper + Symfony UX Stimulus |
| CSS framework | Bootstrap loaded by host application |

## Goal

Validate the bundle in a fresh Symfony application outside the bundle repository.

The smoke test validates:

- Composer path repository installation;
- bundle registration;
- route loading;
- Twig rendering;
- translation loading;
- Stimulus integration;
- Bootstrap host integration;
- array-backed datatable behavior;
- Ajax fragments;
- search and filters;
- column visibility;
- CSV exports;
- actions/security basics where applicable.

## Setup summary

The bundle was installed in a fresh Symfony application through a local Composer path repository.

The first installation attempt failed because the consuming application did not yet declare the path repository.

After adding the local repository and using the bundle `develop` branch, Composer installation succeeded.

## Bundle registration

The bundle was successfully registered.

The `zhortein_datatable` routes were available after importing the bundle route file.

Validated routes:

- `zhortein_datatable_fragments`
- `zhortein_datatable_export`

## Twig rendering

The `zhortein_datatable()` Twig function rendered the datatable shell successfully.

Initial issue:

```text
The datatable "demo-users" is not registered.
```

Cause:

No datatable class was defined in the host application yet.

Resolution:

A datatable class was created with:

```php
#[AsDatatable(name: 'demo-users', provider: 'array')]
```

After cache refresh, the datatable was registered and rendered.

## Stimulus integration

Initial issue:

```text
GET /vendor/zhortein/datatable-bundle/assets/controllers/datatable_controller.js 404
```

Cause:

The controller was not exposed correctly through AssetMapper/Symfony UX.

Temporary smoke workaround:

The Stimulus controller source was copied into the host application.

Final fix created separately:

- Stimulus controller exposed through a UX-compatible assets package.
- AssetMapper path is configured by the bundle.
- Controller name was aligned with Symfony UX naming:

```text
zhortein--datatable-bundle--datatable
```

Validated after correction:

- Stimulus application starts.
- Datatable controller initializes.
- Datatable controller connects.
- No controller asset 404 remains.

## Bootstrap integration

The host application initially rendered unstyled markup.

Cause:

The bundle renders Bootstrap-first markup but does not load Bootstrap CSS or JavaScript.

Smoke workaround:

Bootstrap was installed/imported in the host application through AssetMapper/importmap.

Validated:

- table styling is applied;
- dropdowns work;
- controls display correctly.

Follow-up documentation:

Bootstrap CSS and JS requirements were documented for host applications.

## Initial data loading

Initial issue:

The table rendered with:

```text
No data available
```

even though `ArrayDataProvider` had rows.

Cause:

The Stimulus controller connected successfully but did not automatically refresh fragments on connect.

Fix:

Added an `autoLoad` Stimulus value and triggered `refresh()` on connect when enabled.

Validated:

- datatable loads data automatically on page load;
- manual sorting still refreshes data.

## Loading and error states

Initial issue:

An empty red alert and the loading spinner were visible under the toolbar.

Cause:

Bootstrap display utility classes such as `d-flex` overrode the native `hidden` attribute.

Fix:

Replaced hidden toggling with Bootstrap-compatible `d-none` / `d-flex` class toggling.

Validated:

- error alert is hidden by default;
- loading indicator is hidden by default;
- loading state appears only during refresh.

## ArrayDataProvider global search

Smoke finding:

Global search appeared to behave like a starts-with search.

Investigation/fix:

Additional tests were added to cover contains-style matching.

Validated:

- global search matches values inside strings;
- search is case-insensitive;
- non-searchable fields are ignored.

## ArrayDataProvider user filters

Initial issue:

User-facing filters rendered and triggered refresh but did not affect array data.

Fix:

User-facing filters were implemented in `ArrayDataProvider`.

Validated:

- text filter works;
- boolean filter works;
- unknown filters are ignored;
- array-backed smoke example now behaves consistently.

## Column visibility

Initial issue:

When a visible column was unchecked:

- body was refreshed correctly;
- header remained unchanged.

Cause:

Ajax fragments returned body, pagination and summary, but not a header fragment.

Fix:

Added a header fragment to the Ajax response and a Stimulus `header` target.

Validated:

- hiding a column updates the body;
- hiding a column updates the header.

Second issue:

Definition-hidden columns, such as `id`, appeared in the column visibility dropdown but could not be shown.

Fix:

Definition-hidden columns are no longer rendered as toggleable visibility controls.

Validated:

- `id` no longer appears in the column visibility dropdown.

## CSV export

Initial issue:

CSV export did not include current frontend state.

Examples:

- current page size was not reflected;
- filters were not reflected;
- column visibility was not reflected.

Fix:

Export links now include the current Stimulus state.

Validated:

- current export keeps pagination;
- full export removes pagination;
- filters/search/sort are preserved;
- hidden columns are excluded from export.

Behavior decision:

`full` export means:

```text
full filtered dataset, without pagination
```

It does not mean unfiltered raw database export.

This behavior is accepted.

## CSV format

Smoke finding:

CSV default delimiter was comma, while semicolon is often expected in French/European spreadsheet workflows.

Fix:

CSV formatting options were made configurable.

Configuration now supports:

- delimiter;
- enclosure;
- escape;
- optional UTF-8 BOM.

Validated:

- semicolon delimiter can be configured;
- CSV output remains valid.

## Result summary

Initial issue:

The summary was hardcoded in English and used a DataTables-like wording:

```text
Showing 1 to 1 of 1 entries, filtered from 3 total entries
```

Fix:

A dedicated translated summary renderer was added.

Validated:

- empty summary is translated;
- single result summary is clearer;
- multiple result summary is clearer;
- filtered summary is translated and refined.

## Validated features

The smoke test validates:

- bundle installation through local path repository;
- bundle registration;
- route import;
- Twig function availability;
- Bootstrap rendering with host-provided Bootstrap assets;
- Stimulus controller loading through UX-compatible package;
- automatic initial data loading;
- Ajax fragments refresh;
- array provider data rendering;
- global search;
- user-facing filters for array provider;
- page size selector;
- sortable headers;
- column visibility controls;
- synchronized header/body updates;
- CSV current export;
- CSV full export;
- CSV formatting configuration;
- translated result summary.

## Blocking issues discovered and resolved

| Issue | Status |
|---|---|
| Stimulus controller not exposed as UX package | Resolved |
| Bootstrap host requirements unclear | Resolved/documented |
| Initial fragments not auto-loaded | Resolved |
| Loading/error states visible by default | Resolved |
| ArrayDataProvider filters not applied | Resolved |
| Header not refreshed on column visibility change | Resolved |
| Definition-hidden columns shown in visibility menu | Resolved |
| CSV export missing current state | Resolved |
| CSV delimiter not configurable | Resolved |
| Result summary hardcoded and unclear | Resolved |

## Remaining limitations

The smoke test does not yet validate:

- full Doctrine example in a fresh app;
- action visibility integration in a real app;
- optional Symfony authorization adapter in a real app;
- persisted user preferences;
- frontend automated tests;
- XLSX export;
- async exports.

These are not considered blockers for continuing alpha preparation, but they should remain documented limitations.

## Final outcome

- [x] Smoke test passed with issues.
- [x] Blocking issues were identified.
- [x] Blocking issues were fixed or documented.
- [x] Follow-up limitations are known.

## Go / no-go recommendation

Current recommendation:

```text
Go for continuing first alpha preparation, after validating the Doctrine smoke test.
```

The array-provider smoke path is now considered validated.

## Doctrine smoke test addendum

The Doctrine-backed datatable smoke test was also validated in the fresh Symfony application.

Validated behavior:

- Doctrine entities were created in the smoke application.
- Doctrine schema was generated successfully.
- Smoke data was loaded.
- Doctrine datatable service was detected through `#[AsDatatable]`.
- Doctrine datatable rendered through `zhortein_datatable()`.
- Joined organization column rendered.
- Permanent filters applied.
- User-facing filters applied.
- Sorting worked.
- CSV export worked.

Result:

```text
Doctrine smoke path is considered validated for alpha preparation.
```
