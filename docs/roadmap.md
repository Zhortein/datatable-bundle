# Roadmap

This roadmap reflects the current implementation state of `zhortein/datatable-bundle`.

The project follows an incremental strategy: each milestone must keep the bundle usable, tested and documented.

---

## Legend

- ✅ Completed
- 🚧 In progress / next
- 🕒 Planned
- 🔭 Later
- ⏸️ Postponed / not planned for now

---

## 0.1 - Foundation ✅

Initial repository and bundle foundation.

Delivered:

- Bundle skeleton.
- Composer package metadata.
- GitHub repository setup.
- GitHub labels and milestones tooling.
- GitHub Actions CI.
- PHPUnit setup.
- PHPStan max level setup.
- PHP-CS-Fixer setup with Symfony-oriented rules.
- twigcs setup.
- `AGENTS.md`.
- Initial architecture documentation.
- Initial public API decision.
- Sanitized legacy functional reference.

Main outcome:

```text
The repository is ready for AI-assisted and human-reviewed development.
```

---

## 0.2 - Rendering foundation ✅

Initial rendering and frontend foundation.

Delivered:

- Typed `DatatableRequest`.
- Typed `DatatableResult`.
- `DataProviderInterface`.
- `DataProviderRegistry`.
- Initial Twig renderer.
- Initial Bootstrap templates.
- `zhortein_datatable()` Twig function.
- Ajax controller skeleton.
- Vanilla Stimulus controller skeleton.
- Symfony test kernel foundation.
- Twig-first rendering strategy.
- Vanilla Stimulus interaction model.
- Doctrine provider architecture decision.
- First documentation structure.

Main outcome:

```text
The bundle can render a Bootstrap datatable shell and expose the public Twig API.
```

---

## 0.3 - Data pipeline foundation ✅

First complete backend-to-frontend datatable flow.

Delivered:

- `DatatableDefinitionFactory`.
- `DatatableRequestFactory`.
- `ArrayDataProvider`.
- Provider registry Symfony container wiring.
- Row and cell rendering from `DatatableResult`.
- Bootstrap pagination rendering.
- Ajax fragments endpoint connected to provider and renderer.
- Stimulus search and pagination fragment refresh.
- End-to-end flow documentation.

Main outcome:

```text
A datatable can flow from PHP declaration to provider data to rendered Ajax fragments.
```

---

## 0.4 - Doctrine ORM provider foundation ✅

Doctrine ORM as the first production-oriented provider.

Delivered:

- Doctrine ORM functional test foundation.
- SQLite in-memory test setup.
- Doctrine test entity.
- `DoctrineFieldTypeGuesser`.
- `DoctrineFieldType` value object.
- `DoctrineOrmDataProvider` skeleton.
- Doctrine provider container wiring.
- Doctrine permanent filters.
- Doctrine global search.
- Doctrine single-column sorting.
- Doctrine-backed datatable documentation.

Current Doctrine provider capabilities:

- Entity-class based datatables.
- Main alias `e`.
- Visible scalar fields.
- Offset pagination.
- Total and filtered counts.
- Permanent filters.
- Simple global search.
- Single-column sorting.
- Type guessing foundation.

Main outcome:

```text
Simple Doctrine-backed datatables are now usable.
```

---

## 0.5 - Actions and cell rendering foundation ✅

Declarative actions and cell rendering customization.

Delivered:

- `RowActionRouteParameterResolver`.
- Row action rendering.
- Global action rendering.
- CSRF-aware non-GET action rendering.
- Built-in typed cell templates.
- `CellType` enum.
- Doctrine type enrichment into column metadata.
- Custom column template rendering.
- Actions and typed cell rendering documentation.

Current action capabilities:

- GET row/global actions as links.
- Non-GET row/global actions as forms.
- CSRF token support when available.
- Route parameters resolved from row data.
- Bootstrap-compatible action markup.

Current cell capabilities:

- Default, string, numeric, boolean, datetime, array and enum templates.
- Custom column template support.
- Doctrine-inferred cell types.
- Default Bootstrap alignment by type.

Main outcome:

```text
Datatables now support practical row/global actions and customizable cell rendering.
```

---

## 0.6 - Configuration and Symfony integration foundation ✅

Configuration, translations and Symfony integration polish.

Delivered:

- Initial bundle configuration under `zhortein_datatable`.
- Renderer and request factory configuration defaults.
- Route loading documentation.
- Built-in translation catalog.
- English and French translations.
- Datetime formatting strategy with `DateTimeFormatterInterface`.
- Stimulus and AssetMapper integration documentation.
- Installation documentation refresh.
- Configuration documentation refresh.
- Composer package metadata review.

Current configuration options:

```yaml
zhortein_datatable:
  default_provider: doctrine
  default_theme: bootstrap
  default_page_size: 25
  max_page_size: 500
  search_enabled: false
```

Main outcome:

```text
The bundle is easier to install, configure and integrate into a Symfony application.
```

---

## 0.7 - Table controls and accessibility foundation ✅

User-facing table controls and accessibility improvements.

Delivered:

- Sortable header rendering.
- Current sorting state rendering.
- Page size selector.
- Improved loading state.
- Improved error state.
- Default column alignment by cell type.
- Accessibility markup improvements.
- Table controls and interactions documentation.

Current table control capabilities:

- Search input.
- Page size selector.
- Sortable headers.
- Current sort state.
- Pagination controls.
- Loading state.
- Error state.
- Summary updates.
- Accessible labels and ARIA attributes.

Main outcome:

```text
The generated datatable UI is now much closer to a usable professional back-office component.
```

---

## 0.8 - Doctrine associations and joins ✅

Delivered:

- `JoinType` enum.
- `JoinDefinition` value object.
- Explicit `DatatableDefinition::addJoin()`.
- Doctrine association test fixtures.
- Explicit joins applied in Doctrine provider.
- Joined entity column selection.
- Sorting on joined Doctrine fields.
- Search on joined Doctrine fields.
- Permanent filters on joined Doctrine fields.
- Documentation for joins and association fields.

Main outcome:

```text
Doctrine datatables can now display and query simple associated entity fields through explicit joins.
```

Current limitations:

- no automatic association traversal;
- no deep joins;
- no collection joins;
- no custom non-mapped joins;
- no aggregate fields.

---

## 0.9 - Advanced filtering foundation ✅

Delivered:

- `FilterType` enum.
- `UserFilterDefinition` value object.
- User-facing filters on `DatatableDefinition`.
- Filter request normalization in `DatatableRequest`.
- Filter toolbar rendering.
- Stimulus refresh on filter changes.
- Doctrine provider support for declared user filters.
- User filters on explicitly joined Doctrine fields.
- Active filter summary.
- Clear filters action.
- User-facing filters documentation.

Main outcome:

```text
Datatables can expose explicit, typed user-facing filters safely.
```

Current limitations:

- no nested filter expressions;
- no SearchBuilder-style UI;
- no saved filter presets;
- no persisted user preferences;
- no custom filter widgets;
- no collection filters.

---

## 0.10 - Column visibility and user preferences ✅

Delivered:

- Runtime column visibility state.
- Column visibility toolbar controls.
- Stimulus refresh for column visibility changes.
- Column visibility request normalization.
- `DatatablePreference` value object.
- `DatatablePreferenceProviderInterface`.
- `NullDatatablePreferenceProvider`.
- Preferences applied to rendering defaults.
- Documentation for column visibility and preferences.

Main outcome:

```text
Applications can influence datatable display with runtime visibility options and provide user-specific defaults through a replaceable preference provider.
```

Current limitations:

- no built-in persistence;
- no save/reset preferences action;
- no user identity integration;
- no column ordering;
- no drag-and-drop.

---

## 0.11 - Export foundation ✅

Delivered:

- `ExportFormat` enum.
- `ExportMode` enum.
- `DatatableExportRequest`.
- `ExportWriterInterface`.
- `ExportWriterRegistry`.
- `CsvExportWriter`.
- CSV export endpoint.
- Current-view export mode.
- Full-dataset export mode.
- CSV export toolbar control.
- Server-side export documentation.

Main outcome:

```text
Datatables can now export CSV files server-side without client-side export plugins.
```

Current limitations:

- CSV only;
- no XLSX writer yet;
- no asynchronous exports;
- no export size limits;
- no streaming provider support for very large datasets;
- no built-in authorization layer.

---

## 0.12 - Theming and template override polish ✅

Delivered:

- Twig template override documentation.
- Template context reference.
- Bootstrap table display variants.
- Configurable Bootstrap rendering defaults.
- Optional icon rendering strategy documentation.
- Cell template reference.
- Bootstrap template cleanup.
- Theming limitations documentation.

Main outcome:

```text
Applications can customize Bootstrap-first rendering predictably without forking the bundle.
```

Current limitations:

- Bootstrap is the only maintained theme;
- no Tailwind support;
- no theme registry;
- no CSS asset package;
- no icon provider abstraction;
- template context may still evolve before 1.0.

---

## 0.13 - Security and action visibility ✅

Delivered:

- `ActionVisibilityCheckerInterface`.
- `ActionVisibilityContext`.
- Default allow-all visibility checker.
- Row action visibility filtering.
- Global action visibility filtering.
- Optional Symfony authorization adapter.
- Confirmation metadata rendering.
- Vanilla Stimulus confirmation behavior.
- CSRF action rendering review and tests.
- Action security and visibility documentation.

Main outcome:

```text
Applications can control action visibility and render safer action forms without coupling the bundle to a specific security model.
```

Current limitations:

- no built-in voters;
- no security expression language;
- no per-action callback API;
- no modal confirmation;
- no controller-side action handling;
- no batch action security yet.

---

## 0.14 - Developer experience and release hardening ✅

Delivered:

- Minimal array-backed datatable example.
- Complete Doctrine-backed datatable example.
- CI matrix and dependency strategy documentation.
- Changelog fragment strategy and automation script.
- GitHub release workflow.
- Packagist readiness checklist.
- Documentation navigation review checklist.
- Public API review before pre-release.
- First pre-release checklist.
- Release hardening roadmap update.

Main outcome:

```text
The project is now structured enough to prepare a first alpha/pre-release with clear documentation, CI expectations, release workflow and known limitations.
```

Known limitations before first alpha:

- no Symfony Flex recipe yet;
- Stimulus controller import is still manual;
- no Packagist publication yet;
- no real-world smoke test has been recorded yet;
- public API is still allowed to change before stable 1.0.

---

## 0.15 - First alpha preparation ✅

Delivered:

- Fresh Symfony smoke test plan.
- Fresh Symfony path repository smoke test.
- Minimal array datatable smoke test.
- Doctrine datatable smoke test.
- Actions and security smoke test.
- Documentation aligned with smoke test findings.
- Alpha-blocking smoke test issues resolved.
- Changelog prepared for `v0.1.0-alpha.1`.
- Go/no-go decision recorded.
- GitHub release `v0.1.0-alpha.1`.
- Packagist publication.

Main outcome:

```text
The bundle has passed its first real Symfony application smoke tests and is available as an alpha release.
```

Current alpha status:

- `v0.1.0-alpha.1` is released.
- Package is published on Packagist.
- Array provider smoke path is validated.
- Doctrine provider smoke path is validated.
- Actions/security smoke path is validated.
- Bootstrap and Stimulus host integration requirements are documented.

Known limitations:

- alpha-quality API;
- no Symfony Flex recipe yet;
- host applications must import routes manually;
- host applications must enable the Stimulus controller manually through `assets/controllers.json`;
- host applications must provide Bootstrap CSS/JS;
- public API may still change before stable 1.0.

---

## 0.16 - UI/UX rendering polish ✅

Delivered:

- Optional action icon rendering improvements.
- Action icon positioning.
- Row action display modes:
  - inline;
  - dropdown;
  - list.
- Boolean cell display modes:
  - badge;
  - icon;
  - switch;
  - text.
- Polished sortable header rendering.
- Configurable datatable control layout.
- Additional root, wrapper and table CSS class options.
- Column header filter dropdown design decision.
- UI/UX rendering customization documentation.

Main outcome:

```text
The datatable UI is now more adaptable to real business application layouts while remaining Bootstrap-first and dependency-light.
```

Current limitations:

- no icon provider abstraction;
- no icon-only action mode;
- no modal confirmation;
- no Tailwind theme;
- no column header filter implementation yet;
- no frontend automated test suite.

---

## 0.17 - Column header filters UX ✅

Delivered:

- `filterLayout` option:
  - `toolbar`;
  - `header`;
  - `none`.
- Column header filter dropdown rendering.
- Matching filters to columns by `filter.field === column.name`.
- Header filter controls reusing the existing `filters[...]` request format.
- Toolbar filter hiding when header filters are enabled.
- Header filter documentation and architecture notes.

Main outcome:

```text
Dense datatables can keep the toolbar cleaner by exposing filters directly from column headers.
```

Current limitations:

- no active header filter state yet;
- no clear-per-column filter action yet;
- no SearchBuilder-style nested expressions;
- no Select2 integration;
- no datepicker dependency;
- no custom filter widgets;
- no persisted filter presets.

---

## 0.18 - Frontend test foundation ✅

Delivered:

- Vitest frontend test tooling.
- jsdom test environment.
- Stimulus controller registration test.
- Frontend test setup file.
- Local frontend test commands.
- GitHub Actions frontend test execution.
- Frontend test strategy documentation.
- Stimulus connect and auto-load tests.
- Ajax fragment application tests.
- Search, filters and page size interaction tests.
- Sorting and pagination interaction tests.
- Column visibility interaction tests.
- Export URL generation tests.
- Action confirmation behavior tests.

Main outcome:

```text
The vanilla Stimulus controller now has automated frontend coverage for its core interactive behavior.
```

Current frontend test coverage:

- controller registration;
- initial connection;
- auto-load;
- Ajax refresh;
- fragment application;
- loading and error state;
- search;
- filters;
- page size;
- sorting;
- pagination;
- column visibility;
- CSV export URL generation;
- confirmation handling.

Current limitations:

- no browser E2E test suite;
- no CSS/layout assertions;
- no Bootstrap dropdown internals testing;
- no real download assertions;
- no accessibility audit automation yet.

---

# Current installation stance

Symfony Flex recipe support is **postponed for now**.

Reason:

- The bundle works without a recipe.
- Manual setup is explicit and documented.
- A private recipe server would require each consuming project to reference it.
- Publishing a recipe in Symfony contrib would require community review and acceptance.
- The current target user can integrate the bundle reliably through documentation.

Current installation model:

- Composer package from Packagist.
- Manual bundle registration if needed.
- Manual route import.
- Manual `assets/controllers.json` controller activation if needed.
- Host application provides Bootstrap CSS/JS.

This decision can be revisited later if external users repeatedly struggle with installation.

---

## 0.19 - Advanced Doctrine capabilities ✅

Delivered:

- Doctrine provider query-building responsibility extraction.
- `DoctrineFieldReference`.
- `DoctrineFieldReferenceResolver`.
- `DoctrineFieldMetadataResolver`.
- `DoctrineJoinApplier`.
- `DoctrinePaginationApplier`.
- `DoctrineCountExpressionFactory`.
- Explicit chained Doctrine joins.
- Safe backend-defined custom Doctrine joins.
- Reserved DQL alias validation for joins.
- Custom join parameters.
- Aggregate column foundation.
- Count/distinct strategy review and tests.
- Doctrine provider performance guidance.
- Advanced Doctrine capabilities documentation.

Main outcome:

```text
Doctrine-backed datatables now support more advanced backend-defined query shapes while keeping joins, filters and aggregates explicit and testable.
```

Current capabilities:

- main alias `e`;
- explicit to-one joins;
- explicitly chained joins;
- safe custom joins;
- scalar fields from joined aliases;
- permanent filters on main and joined fields;
- user-facing filters on main and joined fields;
- search on main and joined fields;
- sorting on main and joined fields;
- aggregate columns foundation;
- count strategy aware of custom joins and aggregate columns.

Current limitations:

- no automatic deep association traversal;
- no collection-valued association support;
- no ManyToMany aggregation support;
- no frontend-defined joins;
- aggregate columns are display-oriented and intentionally limited;
- async/streaming exports are not implemented yet;
- database-specific optimization remains the host application's responsibility.

---

## 0.20 - XLSX export decision ✅

Delivered:

- XLSX export strategy decision.
- `ExportFormat::Xlsx`.
- XLSX export route support.
- XLSX filename handling in export requests.
- Optional OpenSpout-based XLSX writer.
- Conditional XLSX export controls.
- Stimulus XLSX export URL generation tests.
- XLSX export strategy and usage documentation.
- XLSX memory and performance constraints documentation.

Main outcome:

```text
The bundle now has a clear XLSX export strategy and an optional OpenSpout-based implementation while keeping CSV dependency-free.
```

Current XLSX capabilities:

- XLSX is a known export format.
- XLSX export route is available.
- XLSX writer can be enabled when OpenSpout is installed.
- XLSX export respects visible/exportable columns.
- XLSX export supports current and full modes.
- XLSX export controls can be rendered conditionally through `exportFormats`.
- Stimulus export URL generation supports XLSX links.

Current limitations:

- XLSX export is synchronous.
- XLSX writer is data-focused and does not style workbooks.
- No multi-sheet exports.
- No formulas.
- No charts.
- No images.
- No export size limits yet.
- No async export jobs.
- No streaming provider contract.
- Very large XLSX exports should not be considered supported yet.

---

## 0.21 - UI/UX smoke test fixes ✅

Delivered:

- Fixed XLSX export filename/format behavior discovered during smoke testing.
- Fixed duplicated controls in `controlsLayout: split`.
- Fixed row action dropdown overflow in short tables.
- Added Bootstrap modal action confirmations with native confirmation fallback.
- Fixed non-GET action width in list display mode.
- Fixed sortable header indicator state after Ajax sorting.
- Fixed header filter dropdown rendering.
- Added bulk actions and hierarchical tables to roadmap ideas.
- Planned a dedicated documentation overhaul milestone.
- Recorded post-0.20 UI/UX smoke test findings.

Main outcome:

```text
The UI/UX regressions found after the XLSX milestone were resolved before preparing the next alpha release.
```

Current UI/UX smoke status:

- split controls layout is usable;
- row action dropdown/list modes are usable;
- modal confirmation improves action UX;
- sort indicators reflect current sort state;
- header filters render correctly;
- CSV/XLSX exports use correct routes and filenames.

---

## 0.22 - Documentation overhaul ✅

Delivered:

- Documentation audit and classification.
- README rewritten as project landing page.
- Installation and quick-start documentation rewritten.
- Provider documentation consolidated.
- Feature documentation consolidated.
- Architecture documentation split into focused pages.
- Obsolete snippets and stale notes removed.
- Final documentation consistency review against implemented features.
- Documentation navigation cleaned.

Main outcome:

```text
The documentation is now structured, clearer, and more suitable for external users evaluating or integrating the bundle.
```

Current documentation status:

- README acts as a project landing page.
- `docs/index.md` acts as the documentation table of contents.
- installation and quick-start paths are clearer.
- user-facing docs, reference docs, architecture docs, decisions, development docs and smoke reports are separated.
- known limitations remain explicit.

---

## 0.23 - Second alpha preparation ✅

Delivered:

- Second alpha smoke test.
- Second alpha blockers resolved.
- Composer and Packagist metadata review.
- Changelog prepared for the second alpha.
- Go/no-go decision recorded.
- Release tag published.
- GitHub Release published.
- Packagist updated automatically.
- Roadmap updated after second alpha.
- Dependabot PRs merged successfully after release preparation.

Main outcome:

```text
The bundle reached its second public alpha release after major improvements to UI/UX, Doctrine capabilities, XLSX exports, frontend tests and documentation.
```

Released version:

```text
v0.2.0-alpha.1
```

Release status:

- GitHub Release published.
- Packagist updated.
- PHP 8.4 and PHP 8.5 CI are green.
- Highest and lowest dependency checks are green.
- Fresh Symfony smoke testing passed after fixes.

Known limitations after second alpha:

- The package remains alpha-quality.
- Public APIs may still change before stable 1.0.
- Async exports are not implemented.
- Streaming export provider contracts are not implemented.
- Very large XLSX exports are not considered supported yet.
- Bulk actions are not implemented yet.
- Hierarchical tables are not implemented yet.
- Advanced Doctrine collection-valued association support remains out of scope.
- Full browser E2E coverage and accessibility audit are not implemented yet.
- No Symfony Flex recipe is provided for now.

---

# Current installation stance

Symfony Flex recipe support is postponed for now.

The bundle works without a recipe as long as the host application follows the documented manual setup.

Current required integration steps:

- install the Composer package;
- register the bundle if Symfony does not do it automatically;
- import the bundle routes;
- enable the Stimulus controller through `assets/controllers.json` when using Symfony UX;
- provide Bootstrap CSS and JavaScript in the host application.

The main repeated manual step is route import.

A Symfony Flex recipe may become useful later to automate:

- route import;
- optional configuration skeleton;
- installation notes for Bootstrap and Stimulus.

For now, a recipe is not blocking because:

- the bundle has working defaults;
- configuration is optional;
- documentation covers manual setup;
- publishing and maintaining a recipe adds process overhead;
- external usage feedback is still limited.

This decision should be revisited after more real-world installations.

---

# Next roadmap direction

The next milestone should focus on browser-level validation and accessibility.

The next milestone should focus on production-oriented table actions.

## 0.24 - Bulk actions and row selection ✅

Delivered:

- `BulkActionDefinition` value object.
- `DatatableDefinition::addBulkAction()` API.
- Automatic selector column rendering (checkboxes).
- "Select all" checkbox in header (current page).
- Stimulus state management for selected IDs.
- Bulk action toolbar rendering when rows are selected.
- Selection count display.
- CSRF-aware form submission for bulk actions.
- Confirmation metadata support.
- Customizable parameter name for selected IDs.
- Documentation for bulk actions.

Main outcome:

```text
Datatables can perform safe backend-defined actions on multiple selected rows, which is required for production back-office workflows.
```

Current limitations:

- no "select all matching rows" across pages;
- no selection persistence across navigation/refresh;
- no async/background bulk processing built-in;
- no bulk edit forms.

---

## 0.25 - Icon system and visual consistency ✅

Goal:

```text
Provide a consistent, configurable icon strategy across actions, booleans, sorting, filters and exports.
```

Delivered:

- **icon resolver**: a flexible icon resolution system;
- **configuration overrides**: global and per-datatable icon overrides;
- **actions**: icons for row and global actions;
- **bulk actions**: icons for bulk action triggers;
- **booleans**: configurable icons for boolean values;
- **sort indicators**: customizable icons for ascending/descending states;
- **filters**: icons for filter headers and actions;
- **exports**: icons for export formats.

Current limitations:

- no mandatory icon library;
- no SVG provider;
- no UX Icons hard integration;
- no icon-only actions unless implemented.

Main outcome:

```text
Generated datatables have a coherent visual language while allowing host applications to choose their icon system.
```

---

## 0.26 - Advanced filter expressions ✅

Goal:

```text
Introduce a safe advanced filter expression model without exposing Doctrine QueryBuilder directly to the frontend.
```

Delivered:

- **backend expression model**: structural `Expression`, `Group` and `Condition` value objects;
- **field declarations**: `addAdvancedFilterField()` API with strict security boundaries;
- **request normalization**: robust JSON payload normalization into internal expressions;
- **Bootstrap UI**: a recursive "Search Builder" interface with group/condition management;
- **Stimulus serialization**: vanilla Stimulus state management and Ajax serialization;
- **Array provider support**: full in-memory evaluation of advanced expressions;
- **Doctrine provider support**: DQL translation with automatic joins and case-insensitivity;
- **export compatibility**: filters automatically applied to CSV and XLSX exports.

Current limitations:

- no saved filter presets;
- no persistence between sessions or page reloads;
- no specialized third-party widgets (Select2, datepickers, etc.);
- no collection-valued associations (one-to-many/many-to-many filtering);
- tree depth is limited to 3 to prevent complex query exhaustion.

Main outcome:

```text
Users can build richer, nested filters safely using a Search Builder UI while the backend remains in control of query generation.
```

---

## 0.27 - First beta preparation ✅


Goal:

```text
Prepare the first beta release after bulk actions, icon system and advanced filter expressions. 
Target release: v0.3.0-beta.1.
```

Delivered:
- Run first beta smoke test
- Resolve first beta blockers
- Review public API before first beta
- Prepare changelog for first beta
- Review go-no-go for first beta tag
- Tag and publish first beta
- Update roadmap after first beta

Main outcome:
```text
Target release: v0.3.0-beta.1.
```

---

## 0.28 - Frontend E2E and accessibility evaluation 🚧

Goal:

```text
Validate the most interactive UI behavior in a real browser and define an accessibility baseline.
```

Planned:

- decide whether Playwright or another browser-level tool is needed;
- test Bootstrap dropdown behavior in a real browser;
- test keyboard navigation;
- test modal confirmations;
- test row selection and bulk actions;
- test column header filters;
- test export links;
- add basic accessibility checks where practical;
- document findings and limitations.

Main expected outcome:

```text
The most interactive Bootstrap/Stimulus behaviors are validated beyond jsdom unit tests.
```

---

## 0.29 - Hierarchical tables / expandable child datatables 🕒

Goal:

```text
Support expandable rows and child datatables for hierarchical business data.
```

Planned:

- design parent/child datatable API;
- support expandable detail rows;
- support lazy Ajax loading;
- propagate parent row context;
- define recursion/performance safeguards;
- document limitations;
- smoke test hierarchical UI.

Main expected outcome:

```text
Datatables can represent parent/child business structures without custom per-project table code.
```

---

# Later milestones

## 1.0 - First stable release ✅

Delivered stable scope:

- PHP-first datatable declarations.
- Symfony service discovery.
- Array and Doctrine providers.
- Doctrine provider with explicit joins.
- Global search.
- Typed filters.
- Header and toolbar filter layouts.
- Sorting.
- Pagination.
- Column visibility.
- Row/global actions.
- CSRF-aware non-GET actions.
- Action visibility extension point.
- Bootstrap modal confirmation.
- Twig/Bootstrap rendering.
- Stimulus Ajax refresh.
- CSV export.
- Optional XLSX export.
- Translation catalog.
- Documentation.
- CI and quality tooling.
- Fresh Symfony integration validated.
- Public 1.x compatibility contract.
- Release integrity validation before promotion and tagging.
- Real-project UI feedback fixes for toolbar slots, booleans and dropdowns.

Main outcome:

```text
The bundle has a documented stable API, a verified fresh-Symfony installation path and release gates suitable for the 1.0.0 tag.
```

---

## Later ideas 🔭

Potential future work:

- multi-column sorting;
- async exports;
- streaming export provider contracts;
- additional export formats;
- user preference persistence adapters;
- API/data-source providers;
- Elasticsearch provider;
- UX Icons integration;
- richer enum badge/icon rendering;
- Symfony Flex recipe if external demand justifies it;
- Tailwind or custom theme support;
- icon provider abstraction;
- frontend smoke test automation;
- export size limits and queued export jobs.

---

## Documentation maintenance

Documentation navigation is reviewed as part of release hardening.

Key entry points:

- [`../README.md`](../README.md);
- [`index.md`](index.md);
- [`quick-start.md`](quick-start.md);
- [`installation.md`](installation.md);
- [`configuration.md`](configuration.md);
- [`roadmap.md`](roadmap.md).

A dedicated documentation review checklist exists in [`documentation-review.md`](documentation-review.md).

---

## Public API review notes

The authoritative 1.x contract is documented in [`public-api.md`](public-api.md). The earlier prerelease review remains in [`archive/milestones/public-api-review.md`](archive/milestones/public-api-review.md).

The stable boundary keeps definition builders, extension interfaces, DTOs, enums and named integration contracts public. Renderer, registry, factory, controller and Doctrine helper implementations remain internal.

Future streaming exports, asynchronous jobs and provider capabilities must use additive contracts during the 1.x series.
