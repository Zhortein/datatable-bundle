# Frontend test strategy

This document describes the JavaScript test setup for the bundle frontend code.

The bundle frontend remains:

- vanilla JavaScript;
- Stimulus-based;
- independent from jQuery;
- independent from DataTables.net.

## Test tooling

Frontend tests use:

```text
Vitest
jsdom
@hotwired/stimulus
```

Vitest provides the test runner.

jsdom provides a DOM-like environment for controller tests.

Stimulus is used directly to register and exercise the bundle controller.

## Test location

Frontend tests live under:

```text
tests/Frontend/
```

Current include pattern:

```text
tests/Frontend/**/*.test.js
```

## Commands

Install frontend dependencies:

```bash
npm install
```

Run frontend tests:

```bash
npm run test:frontend
```

Watch mode:

```bash
npm run test:frontend:watch
```

When using the project tooling container, use the Makefile targets documented in the repository once they are added.

## Current coverage

The first frontend test validates that the datatable controller can be registered by Stimulus with the UX-compatible identifier:

```text
zhortein--datatable-bundle--datatable
```

Further tests are added in later issues for:

- auto-load behavior;
- Ajax fragment application;
- search/filter/page size interactions;
- sorting and pagination;
- column visibility;
- export URL generation;
- confirmation behavior.

## Current limitations

This is not an end-to-end browser test suite.

It does not test:

- real browser layout;
- Bootstrap dropdown internals;
- network behavior;
- file downloads;
- real Symfony routes.

Those remain covered by smoke tests for now.

### Stimulus connect and auto-load tests

Frontend tests now cover the initial Stimulus lifecycle.

Covered behavior:

- the datatable controller can connect to the rendered datatable element;
- `autoLoad` defaults to enabled;
- initial fragment loading is triggered on connect;
- `autoLoad=false` prevents initial refresh;
- a missing fragments URL displays the configured error target instead of performing a network call.

These tests protect the smoke-test fix that made datatables load their first dataset automatically.

### Stimulus Ajax fragment application tests

Frontend tests now cover Ajax fragment application.

Covered behavior:

- header fragment replacement;
- body fragment replacement;
- pagination fragment replacement;
- summary update;
- page and page size state updates from payload;
- invalid payload state is ignored;
- partial payloads do not break refresh;
- failed refreshes display the error target;
- loading state is toggled during refresh.

These tests protect the server-rendered fragment model used by the bundle.

### Stimulus search, filters and page size tests

Frontend tests now cover user input interactions that rebuild the Ajax refresh URL.

Covered behavior:

- global search value serialization;
- search debounce before refresh;
- filter serialization through `filters[...]`;
- empty filters are omitted;
- unchecked checkbox/radio filters are ignored;
- active filter UI state updates;
- page size changes reset the current page;
- invalid page size values are ignored;
- clear filters resets controls, active state and refresh URL.

These tests protect the interaction layer used by toolbar filters and header filter dropdown controls.

### Stimulus sorting and pagination tests

Frontend tests now cover sorting and pagination interactions.

Covered behavior:

- sorting a new field starts with ascending direction;
- sorting the same field toggles direction;
- sorting another field resets direction to ascending;
- sorting resets the current page to 1;
- invalid sort events are ignored;
- pagination changes the current page;
- invalid page values are ignored;
- current sort state is preserved when navigating pages.

These tests protect the datatable's core navigation and ordering behavior.

### Stimulus column visibility tests

Frontend tests now cover column visibility interactions.

Covered behavior:

- checked column controls are serialized as `visibleColumns[]`;
- unchecked column controls are serialized as `hiddenColumns[]`;
- definition-hidden columns are ignored;
- missing column names are ignored;
- column visibility changes reset the current page to 1;
- column visibility refresh is debounced;
- header, body and summary fragments update after refresh.

These tests protect the column visibility behavior discovered during the fresh Symfony smoke test.

### Stimulus export URL generation tests

Frontend tests now cover CSV export URL generation.

Covered behavior:

- current export includes page and page size;
- full export omits pagination;
- search state is included;
- filter values are included;
- sort state is included;
- column visibility state is included;
- definition-hidden columns are not serialized;
- custom export URL values are respected;
- link href fallback is supported.

These tests protect the smoke-test fix that made exports reflect the current datatable state.
