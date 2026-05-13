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
