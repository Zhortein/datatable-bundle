# Frontend test strategy

This document describes the JavaScript test strategy for `zhortein/datatable-bundle`.

The bundle frontend remains intentionally lightweight:

- vanilla JavaScript;
- Stimulus-based;
- Bootstrap-compatible;
- independent from jQuery;
- independent from DataTables.net;
- independent from Webpack Encore.

Frontend tests protect the behavior of the bundle Stimulus controller.

---

## Tooling

Frontend tests use:

```text
Vitest
jsdom
@hotwired/stimulus
```

Vitest is used as the JavaScript test runner.

jsdom provides a DOM-like environment.

Stimulus is started directly in tests to register and exercise the controller with its real identifier:

```text
zhortein--datatable-bundle--datatable
```

---

## Files

Frontend tests live under:

```text
tests/Frontend/
```

The Vitest configuration includes:

```text
tests/Frontend/**/*.test.js
```

The shared setup file is:

```text
tests/Frontend/setup.js
```

The bundle controller currently tested is:

```text
assets/controllers/datatable_controller.js
```

---

## Local commands

Install frontend dependencies:

```bash
make frontenddeps
```

Run frontend tests:

```bash
make frontendtest
```

Without the local Docker tooling:

```bash
npm ci
npm run test:frontend
```

For development watch mode:

```bash
npm run test:frontend:watch
```

---

## CI execution

Frontend tests are part of the GitHub Actions quality gates.

The CI workflow runs:

```bash
npm ci
npm run test:frontend
```

`package-lock.json` is committed so CI can use reproducible dependency installation with `npm ci`.

The CI uses Node.js through `actions/setup-node`.

---

## Current coverage

### Stimulus setup

Covered behavior:

- the datatable controller can be registered by Stimulus;
- the UX-compatible controller identifier is valid.

### Connect and auto-load

Covered behavior:

- the controller connects to the rendered datatable element;
- `autoLoad` defaults to enabled;
- initial fragment loading is triggered on connect;
- `autoLoad=false` prevents initial refresh;
- a missing fragments URL displays the error target instead of performing a network call.

### Ajax fragments

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

### Search, filters and page size

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

### Sorting and pagination

Covered behavior:

- sorting a new field starts with ascending direction;
- sorting the same field toggles direction;
- sorting another field resets direction to ascending;
- sorting resets the current page to 1;
- invalid sort events are ignored;
- pagination changes the current page;
- invalid page values are ignored;
- current sort state is preserved when navigating pages.

### Column visibility

Covered behavior:

- checked column controls are serialized as `visibleColumns[]`;
- unchecked column controls are serialized as `hiddenColumns[]`;
- definition-hidden columns are ignored;
- missing column names are ignored;
- column visibility changes reset the current page to 1;
- column visibility refresh is debounced;
- header, body and summary fragments update after refresh.

### Export URL generation

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

### Action confirmation

Covered behavior:

- actions without confirmation metadata are ignored;
- blank confirmation messages are ignored;
- confirmed link actions are allowed;
- cancelled link actions are prevented;
- confirmed form submissions are allowed;
- cancelled form submissions are prevented;
- non-HTMLElement event targets are ignored.

### Ajax actions

Covered behavior:

- row, global and bulk opt-in execution;
- GET links and Symfony method-override forms;
- CSRF and selected-identifier payloads;
- confirmation before execution;
- duplicate-submission prevention and loading-state restoration;
- versioned success, HTTP/business failure and invalid-response handling;
- built-in accessible success/error feedback;
- cancellable before, success, error and complete events;
- `refresh_table`, `refresh_row`, `remove_row`, `none` and `redirect` strategies;
- preservation of current search, filters, sort, page, page size and column visibility during a table refresh.

### Stimulus XLSX export URL generation tests

Frontend tests now cover XLSX export URL generation.

Covered behavior:

- XLSX current export uses the XLSX endpoint and keeps pagination;
- XLSX full export uses the XLSX endpoint and omits pagination;
- search, filters, sorting and column visibility are preserved;
- link-specific export URL params override the root CSV export URL;
- link href fallback still works when no export URL value is configured.

These tests protect conditional multi-format export controls.

---

## Testing conventions

Tests should:

- use the real Stimulus controller;
- register it with the real UX-compatible identifier;
- keep DOM fixtures small but realistic;
- mock `fetch` for Ajax behavior;
- mock `window.confirm` for confirmation behavior;
- mock navigation for export tests;
- avoid real network calls;
- avoid Bootstrap internals;
- avoid jQuery;
- avoid browser-only assumptions not supported by jsdom.

When the controller behavior is debounced, tests may use fake timers.

When fake timers are used, helpers must not rely on unadvanced `setTimeout()` calls.

---

## What frontend tests do not cover

This is not an end-to-end browser test suite.

The frontend tests do not validate:

- real browser layout;
- CSS rendering;
- Bootstrap dropdown internals;
- real file downloads;
- real Symfony routing;
- real backend provider behavior;
- full application integration.

Those remain covered by PHP tests, smoke tests and future E2E testing if needed.

---

## Future improvements

Possible future improvements:

- add a small shared frontend test fixture helper;
- add coverage for additional controller methods when new interactions are added;
- add accessibility-focused assertions for generated controls;
- decide whether browser E2E tests are needed before 1.0;
- consider Playwright only if jsdom becomes insufficient.

For now, Vitest + jsdom provides a good balance between confidence, speed and maintenance cost.
