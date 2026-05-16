# Smoke test report — post-0.20 UI/UX findings

## Metadata

| Field | Value |
|---|---|
| Scope | Post-0.20 smoke test |
| Area | UI/UX, exports, controls, actions, filters |
| Context | Fresh Symfony smoke application updated against current `develop` |
| Status | Findings recorded and tracked through dedicated issues |

## Goal

Record UI/UX regressions and product-polish issues discovered after updating the smoke application following milestone `0.20 - XLSX export decision`.

## Smoke context

The smoke application renders the datatable with options similar to:

```twig
{{ zhortein_datatable('demo-users', {
    search: true,
    pageSize: 10,
    pageSizeSelector: true,
    allowedPageSizes: [10, 25, 50],
    export: true,
    exportFormats: ['csv', 'xlsx'],
    filterLayout: 'header'
}) }}
```

The smoke datatable uses the array provider and includes:

- hidden `id` column;
- `email`;
- `displayName`;
- `enabled`;
- text filter on `email`;
- boolean filter on `enabled`;
- row actions:
  - `view`;
  - `edit`;
  - `delete` as non-GET action with CSRF and confirmation;
- global action:
  - `create`;
- row action display modes tested with `dropdown` and `list`.

## Findings

### 1. XLSX export filename extension

Initial smoke finding:

```text
XLSX export suggested a CSV filename.
```

Additional investigation showed that the direct XLSX export URL worked correctly.

Tracked by:

```text
#335 Fix XLSX export filename extension
```

Expected behavior:

- CSV export suggests `.csv`;
- XLSX export suggests `.xlsx`;
- XLSX export links use `/export/xlsx`;
- Turbo prefetch/navigation does not interfere with export links.

Status:

```text
Tracked and fixed/validated through #335.
```

### 2. Duplicated controls in split layout

Smoke finding:

```text
When using controlsLayout: 'split', column visibility and page-size selector appeared both in the top toolbar and below the table.
```

Tracked by:

```text
#336 Fix duplicated controls in split layout
```

Status:

```text
Tracked and fixed/validated through #336.
```

### 3. Row action dropdown overflow in short tables

Smoke finding:

```text
rowActionDisplayMode: 'dropdown' worked, but when the table had too few rows, the dropdown overflowed inside the table wrapper and created an unpleasant vertical scrollbar.
```

Tracked by:

```text
#337 Fix row action dropdown overflow in short tables
```

Status:

```text
Tracked and fixed/validated through #337.
```

### 4. Native confirmation UX

Smoke finding:

```text
Action confirmations worked, but native window.confirm() felt rough compared to the Bootstrap UI.
```

Tracked by:

```text
#338 Implement Bootstrap modal action confirmation
```

Status:

```text
Tracked and fixed/validated through #338.
```

### 5. Non-GET actions in list mode

Smoke finding:

```text
rowActionDisplayMode: 'list' worked, but non-GET actions rendered as forms/buttons did not stretch full width and looked inconsistent.
```

Tracked by:

```text
#339 Fix non-GET action width in list display mode
```

Status:

```text
Tracked and fixed/validated through #339.
```

### 6. Sort indicator state

Smoke finding:

```text
Sorting by clicking a column header worked, but the icon remained neutral instead of showing ascending/descending state.
```

Root cause found:

```text
DatatableRequest::getColumnVisibilityOptions() was used for header rendering but did not include sort state.
```

Tracked by:

```text
#340 Fix sortable header indicator state after sorting
```

Status:

```text
Tracked and fixed/validated through #340.
```

### 7. Header filter dropdown rendering

Smoke finding:

```text
filterLayout: 'header' did not display the expected filter dropdowns in column headers.
```

Tracked by:

```text
#341 Fix header filter dropdown rendering
```

Status:

```text
Tracked and fixed/validated through #341.
```

### 8. Missing roadmap ideas

Smoke/roadmap finding:

```text
The roadmap was missing two previously discussed features:
- bulk actions with row selector;
- hierarchical / expandable child datatables.
```

Tracked by:

```text
#342 Add bulk actions and hierarchical tables to roadmap ideas
```

Status:

```text
Tracked and fixed/validated through #342.
```

### 9. Documentation overhaul need

Project finding:

```text
Documentation has grown quickly and is now difficult to navigate, with duplicates, stale snippets and scattered feature information.
```

Tracked by:

```text
#343 Plan documentation overhaul milestone
```

Status:

```text
Tracked and planned through #343.
```

## Related issues

| Issue | Title |
|---|---|
| #335 | Fix XLSX export filename extension |
| #336 | Fix duplicated controls in split layout |
| #337 | Fix row action dropdown overflow in short tables |
| #338 | Implement Bootstrap modal action confirmation |
| #339 | Fix non-GET action width in list display mode |
| #340 | Fix sortable header indicator state after sorting |
| #341 | Fix header filter dropdown rendering |
| #342 | Add bulk actions and hierarchical tables to roadmap ideas |
| #343 | Plan documentation overhaul milestone |
| #344 | Record post-0.20 UI UX smoke test findings |

## What still worked correctly

The smoke test also confirmed:

- datatable rendering;
- Stimulus controller loading;
- Ajax refresh;
- array provider data display;
- CSV export URL generation;
- XLSX direct export URL behavior;
- row actions rendering;
- global actions rendering;
- `rowActionDisplayMode: dropdown`;
- `rowActionDisplayMode: list`;
- sorting behavior itself;
- split layout bottom area concept;
- export controls rendering for CSV and XLSX.

## Outcome

The post-0.20 smoke test exposed mostly UI/UX polish issues, not foundational backend failures.

The milestone created a focused corrective path before continuing with larger roadmap items.

## Status

```text
Post-0.20 UI/UX smoke findings are recorded and tracked.
```

## Recommendation

Finish and validate all issues created in `0.21 - UI/UX smoke test fixes`.

Then continue with:

- documentation overhaul milestone;
- browser-level/E2E/accessibility evaluation;
- future larger product features such as bulk actions and hierarchical tables.
