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
Playwright
axe-core
```

Vitest is used as the JavaScript test runner.

jsdom provides a DOM-like environment.

Stimulus is started directly in tests to register and exercise the controller with its real identifier:

```text
zhortein--datatable-bundle--datatable
```

Playwright provides a focused Chromium smoke suite against the fresh Symfony
application. axe-core checks the rendered datatable against the WCAG 2.0,
2.1 and 2.2 A/AA rules that belong to the bundle component.

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

Browser tests live under:

```text
tests/E2E/
```

Their configuration is:

```text
playwright.config.js
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

Install the Chromium browser used by the E2E suite:

```bash
npx playwright install --with-deps chromium
```

Run the E2E suite through the real fresh-Symfony smoke application:

```bash
SMOKE_E2E=1 tools/smoke-test/fresh-symfony-app.sh
```

The smoke script creates a temporary Symfony 8 application, installs the
current bundle through a Composer path repository, compiles AssetMapper
assets, starts the PHP test server and then runs:

```bash
npm run test:e2e
```

`PLAYWRIGHT_BASE_URL` may point the browser suite to an already running
compatible smoke host when debugging.

---

## CI execution

Frontend tests are part of the GitHub Actions quality gates.

The CI workflow runs:

```bash
npm ci
npm run test:frontend
```

The dedicated browser job additionally installs Chromium and runs the fresh
Symfony smoke script with `SMOKE_E2E=1`. Failed runs upload Playwright traces
as a workflow artifact.

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
- Shift-modified activation appends, toggles and removes ordered criteria;
- multi-column criteria are serialized for fragments and exports;
- legacy single-column state remains accepted;
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

### Namespaced URL state and history

Covered behavior:

- complete state is restored before the initial fragments request;
- state parameters are isolated across several tables on one page;
- successful interactions preserve existing Turbo history metadata;
- `popstate` restores controls without creating a new history entry;
- invalid and unsupported payloads are ignored;
- nested advanced filters are rebuilt for fragments and exports;
- restored state remains compatible with signed context query parameters.
- missing column names are ignored;
- column visibility changes reset the current page to 1;
- column visibility refresh is debounced;
- header, body and summary fragments update after refresh.

### Named saved views

Covered behavior:

- a named default is restored before the initial fragments request;
- a valid namespaced URL remains authoritative over that default;
- selected views restore search, filters, sorting, pagination and columns;
- create/update/rename/default/delete requests carry CSRF and opaque revisions;
- stale revisions surface a conflict without replacing current state;
- two table instances keep separate named-view endpoints and scopes.

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

### Browser E2E and accessibility

The focused Chromium suite validates behavior that jsdom cannot represent
faithfully:

- the bundle loads through real Symfony routes, AssetMapper and Stimulus;
- Ajax fragments update the rendered table;
- sorting and pagination work from the keyboard;
- Bootstrap action and header-filter dropdowns open in a real browser;
- search and typed filters refresh real backend results;
- row selection exposes bulk actions;
- the Bootstrap confirmation modal receives keyboard focus and closes with
  `Escape`;
- CSV export produces a real browser download;
- the rendered datatable has no axe-core violations in the selected WCAG
  A/AA baseline.

The `region` rule is intentionally excluded from the component scan because
page landmarks are owned by the host application's layout, not by an embedded
datatable.

---

## Vitest conventions

Vitest tests should:

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

## Playwright conventions

Playwright tests should:

- use the generated fresh Symfony application;
- exercise only behavior that needs a real browser;
- prefer accessible roles and names over implementation-specific selectors;
- wait for the real fragments response before asserting refreshed content;
- keep each test isolated in its own browser context;
- run against Chromium unless a concrete compatibility risk justifies another
  engine;
- scan the component with axe-core while leaving host-page responsibilities
  explicit.

---

## Coverage boundaries

The combined frontend strategy does not validate:

- pixel-perfect CSS rendering;
- Firefox or WebKit behavior;
- host-application page landmarks;
- every possible definition and provider combination.

PHP tests remain authoritative for provider behavior and the complete
definition matrix. The Chromium E2E suite protects only the highest-risk
integration paths and must remain small enough to diagnose reliably.

---

## Future improvements

Possible future improvements:

- add a small shared frontend test fixture helper;
- add coverage for additional controller methods when new interactions are added;
- add Firefox or WebKit only when a concrete compatibility risk justifies the
  additional CI cost;
- add deeper manual accessibility review for complex Search Builder trees.

Vitest remains the fast, exhaustive controller test layer. Playwright is the
small integration layer for behavior that requires a real browser.
