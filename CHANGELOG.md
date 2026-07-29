# Changelog

All notable changes to this project will be documented in this file.

This project follows [Semantic Versioning](https://semver.org/).

## [Unreleased]

_No unreleased changes have been collected yet._

## [1.16.2] - 2026-07-29

### Changed

- Updated GitHub Actions to Node.js 24 runtimes and configured ordinary Composer, npm and GitHub Actions Dependabot version updates to target `develop`.

## [1.16.1] - 2026-07-28

### Fixed

- Prevented HTTP payloads from disabling pagination, allowlisted declared filters, sorts and columns before provider execution, bounded search/filter/Search Builder inputs, and neutralized spreadsheet formulas in CSV string cells.

## [1.16.0] - 2026-07-28

### Added

- Added a dependency-free icon renderer abstraction with safe CSS-class output, consistent control and cell rendering, and an optional Symfony UX Icons adapter with deterministic fallback behavior.

## [1.15.0] - 2026-07-28

### Added

- Added optional PSR-6-backed user preference persistence with opaque identity and collision-free table, route, locale, context and schema scopes.
- Added CSRF-protected save/reset controls and events for page size, ordered sorts, column visibility and explicitly preference-safe filters.

## [1.14.0] - 2026-07-28

### Added

- Added a generic HTTP/API data provider foundation with explicit capabilities, page/offset/cursor pagination, replaceable transport and mapping contracts, an optional Symfony HttpClient adapter, normalized remote failures and guarded streaming exports.

## [1.13.0] - 2026-07-28

### Added

- Added storage-agnostic asynchronous export jobs with immutable lifecycle contracts, owner isolation, idempotent submission, expiration, bounded CSV/XLSX artifacts and optional Symfony Messenger dispatch.

## [1.12.0] - 2026-07-28

### Added

- Added additive streaming provider and writer contracts, bounded Doctrine batches, direct CSV output, incremental OpenSpout XLSX generation, cancellation support and configurable export batch sizes.

## [1.11.0] - 2026-07-27

### Added

- Added configurable synchronous export row limits, preflight provider counting and a replaceable authorization context for CSV/XLSX, current/full and child datatable exports.

## [1.10.0] - 2026-07-27

### Added

- Added extensible, translatable enum presentations shared by cells, filters and CSV/XLSX exports, with optional Bootstrap badges, colors and icons.

## [1.9.0] - 2026-07-27

### Added

- Applied Doctrine metadata type enrichment in the normal datatable definition flow, including explicitly declared mapped, chained and custom joins.

## [1.8.1] - 2026-07-27

### Fixed

- Fixed default fragments, export and saved-view URLs when bundle routes use localized or custom import prefixes.

## [1.8.0] - 2026-07-27

### Added

- Added backward-compatible multi-column sorting across Array and Doctrine providers, URL state, saved views, exports and accessible Stimulus controls.

## [1.7.0] - 2026-07-27

### Added

- Added an explicit child datatable declaration and typed parent-row context mapping contract as the foundation for hierarchical tables.
- Added a signed lazy-loading endpoint and accessible Bootstrap row markup for hierarchical datatables.
- Added Array and Doctrine child scoping, recursive Symfony/Chromium coverage, and complete hierarchical datatable documentation.
- Added lazy Stimulus loading, retry and accessible focus management for hierarchical child datatables.
- Added signed child datatable instances, typed context resolution, recursion safeguards and an authorization extension point.

### Fixed

- Fixed Bootstrap confirmation modals in ESM applications by importing the modal component explicitly instead of relying on a global `window.bootstrap` object.

## [1.6.1] - 2026-07-27

### Fixed

- Applied each definition translation domain and current Symfony locale to CSV and XLSX column headers.
- Fixed named views with no regular or advanced filters by emitting stable JSON objects for empty map fields while accepting legacy empty arrays.

### Security

- Updated transitive frontend tooling dependencies with the current PostCSS source-map hardening and ws memory-exhaustion protections.

## [1.6.0] - 2026-07-27

### Added

- Added a stable server-side cell context, provider-aligned source values and reusable computed-column resolvers shared by Twig, CSV and XLSX exports.

## [1.5.0] - 2026-07-27

### Added

- Added opt-in named datatable views with canonical state reuse, collision-free scopes, replaceable ownership/authorization/storage contracts, optimistic concurrency, CSRF-protected JSON routes and Bootstrap/Stimulus controls.

## [1.4.0] - 2026-07-27

### Added

- Added versioned, per-instance URL state with browser history and Turbo restoration for search, filters, advanced expressions, sorting, pagination, page size and column visibility.

## [1.3.0] - 2026-07-27

### Added

- Added signed, per-instance propagation of explicitly browser-safe datatable context across fragments, exports and opt-in Ajax actions.

## [1.2.0] - 2026-07-26

### Added

- Added opt-in Ajax execution for row, global and bulk actions with a versioned response helper, CSRF-aware progressive fallback, confirmations, duplicate prevention, accessible feedback, lifecycle events and refresh-table, refresh-row, remove-row, no-op or redirect success strategies.

## [1.1.0] - 2026-07-26

### Added

- Added explicit row, literal and allowlisted context sources for action route parameters, including optional/default semantics, nested row paths, backed-enum and `Stringable` normalization, localized-route support and backward-compatible 1.x string declarations.

### Fixed

- Validated the documented minimal and complete bundle configuration in fresh Symfony 8 hosts against both `v1.0.0` and the current development version, including effective configuration, container parameters, rendered HTML and CSV output.

## [1.0.1] - 2026-07-26

### Fixed

- Applied each definition translation domain consistently to declarative column, filter, choice, Search Builder, action, confirmation, column visibility and derived accessibility labels on initial and Ajax rendering.

## [1.0.0] - 2026-07-23

### Added

- Added per-column `negate` support for rendering inverse boolean values.
- Added a nullable per-column `exportable` policy to include hidden columns or exclude visible columns from CSV and XLSX exports explicitly.
- Added a CI smoke test that installs the bundle in a fresh Symfony application and validates the current AssetMapper and StimulusBundle integration.
- Documented the supported PHP, Twig, routing, configuration and frontend compatibility surface for the 1.x series.

### Changed

- Added a dedicated action `permission` option and kept legacy permission attributes as non-rendered compatibility metadata.
- Reworked installation, quick-start, provider and route documentation into a verified end-to-end Symfony setup.
- Hardened release validation so tags must belong to `main`, match package metadata, consume changelog fragments and pass the complete QA matrix.

### Removed

- Removed the unused `DatatableExportResult` prototype; export writers return Symfony responses directly.

### Fixed

- Fixed provider selection so `#[AsDatatable(provider: ...)]` and `default_provider` are applied by the provider registry.
- Replaced ambiguous route-debug commands in the installation and quick-start guides with exact route lookups.
- Hid empty toolbar slots without removing their stable DOM targets.
- Centered boolean column headers and vertically aligned boolean cells and switches.
- Vertically centered Bootstrap dropdown carets.
- Loaded the bundled Stimulus controller lazily by default.

## [0.3.0-beta.1] - 2026-06-06

### Added
- Added Advanced filter expressions
- Added Icon system and visual consistency
- Added Bulk actions and row selection

### Documentation
- Added new features documentation
- Fixed few mistakes

## [0.2.0-alpha.1] - 2026-05-16

### Added

- Added action icon rendering improvements and icon positioning.
- Added row action display modes: inline, dropdown and list.
- Added boolean cell display modes: badge, icon, switch and text.
- Added configurable control layout and additional CSS class options.
- Added column header filter dropdown support.
- Added frontend tests with Vitest and jsdom.
- Added advanced Doctrine provider capabilities, including chained joins, safe custom joins, aggregate columns and count strategy review.
- Added optional XLSX export support based on OpenSpout.
- Added conditional XLSX export controls.
- Added Bootstrap modal action confirmation with native confirmation fallback.
- Added documentation overhaul and updated feature documentation.

### Changed

- Improved export URL generation to support per-format export links.
- Improved export filename generation to use the selected export format extension.
- Improved split control layout rendering.
- Improved row action dropdown overflow handling.
- Improved row action list rendering for non-GET actions.
- Improved sortable header state rendering after Ajax refresh.
- Improved documentation structure and navigation.

### Fixed

- Fixed XLSX export filename/format propagation.
- Fixed duplicated controls in split layout.
- Fixed row action dropdown overflow in short tables.
- Fixed non-GET action width in list display mode.
- Fixed sortable header indicator state after sorting.
- Fixed header filter dropdown rendering and behavior.
- Fixed documentation gaps found during smoke testing.

### Documentation

- Reworked README and documentation navigation.
- Reworked installation and quick-start documentation.
- Consolidated provider documentation.
- Consolidated feature documentation.
- Split architecture documentation into focused pages.
- Added XLSX usage and performance documentation.
- Added Doctrine provider performance guidance.
- Added frontend test strategy documentation.

### Known limitations

- The package remains alpha-quality.
- Public APIs may still change before stable 1.0.
- Async exports are not implemented.
- Streaming export provider contracts are not implemented.
- Bulk actions are not implemented yet.
- Hierarchical tables are not implemented yet.
- Advanced Doctrine collection-valued association support remains out of scope.


## [0.1.0-alpha.1] - 2026-05-11

### Added

#### Bundle foundation

- Symfony 8+ bundle skeleton.
- Composer package metadata for `zhortein/datatable-bundle`.
- `ZhorteinDatatableBundle`.
- Bundle configuration under the `zhortein_datatable` root key.
- GitHub Actions CI.
- PHPUnit, PHPStan max level, PHP-CS-Fixer and twigcs quality tooling.
- Local Docker/Makefile tooling for quality checks.
- GitHub labels and issue creation helper scripts.
- `AGENTS.md`.
- Sanitized legacy functional reference.

#### Datatable declaration API

- `#[AsDatatable]` PHP attribute.
- `DatatableInterface`.
- Datatable service discovery through Symfony autoconfiguration and service tags.
- `DatatableRegistry`.
- `DatatableDefinitionFactory`.
- `DatatableDefinition`.
- `ColumnDefinition`.
- `ActionDefinition`.
- Permanent filter definitions.
- User-facing filter definitions.
- Explicit Doctrine join definitions.
- Datatable options support.

#### Request and result model

- `DatatableRequest`.
- `DatatableRequestFactory`.
- `DatatableResult`.
- Pagination, search, sorting, filters, column visibility and runtime option state handling.
- Support for disabling pagination for full exports.

#### Providers

- `DataProviderInterface`.
- `DataProviderRegistry`.
- `ArrayDataProvider` for tests, demos and small in-memory datasets.
- User-facing filters, global search, sorting and pagination in `ArrayDataProvider`.

#### Doctrine ORM provider

- Doctrine ORM functional test foundation with SQLite.
- Doctrine test fixtures.
- `DoctrineFieldTypeGuesser`.
- `DoctrineFieldType`.
- `DoctrineDatatableDefinitionEnricher`.
- `DoctrineOrmDataProvider`.
- Doctrine provider service registration.
- Doctrine pagination.
- Doctrine permanent filters.
- Doctrine global search.
- Doctrine single-column sorting.
- Explicit Doctrine joins.
- Joined entity column support.
- Sorting on joined Doctrine fields.
- Search on joined Doctrine fields.
- Permanent filters on joined Doctrine fields.
- User-facing filters on Doctrine fields and joined Doctrine fields.

#### Rendering

- `DatatableRenderer`.
- Twig-first rendering strategy.
- Bootstrap-first template set.
- `zhortein_datatable()` Twig function.
- Datatable shell rendering.
- Table header rendering.
- Row and cell rendering.
- Empty state rendering.
- Pagination rendering.
- Typed cell templates.
- `CellType` enum.
- Custom column template rendering.
- Default column alignment by cell type.
- Bootstrap table display variants.
- Configurable Bootstrap rendering defaults.
- Template context documentation.

#### Ajax and frontend

- Ajax fragments endpoint.
- Vanilla Stimulus controller.
- Automatic initial datatable loading on Stimulus connect.
- Search, filter, page size, pagination, sorting and column visibility refresh.
- Table header refresh when column visibility changes.
- Loading and error state handling.
- Bootstrap-compatible `d-none` / `d-flex` state toggling.
- Symfony UX / AssetMapper-compatible Stimulus controller package exposure.
- Documentation for Stimulus and AssetMapper integration.

#### Actions and security

- Row action rendering.
- Global action rendering.
- `RowActionRouteParameterResolver`.
- CSRF-aware rendering for non-GET actions.
- Action confirmation metadata.
- Vanilla Stimulus confirmation behavior.
- `ActionVisibilityCheckerInterface`.
- `ActionVisibilityContext`.
- Default `AllowAllActionVisibilityChecker`.
- Row and global action visibility filtering.
- Optional Symfony authorization visibility adapter.
- Turbo prefetch prevention on datatable action links.

#### Filters

- `FilterType` enum.
- User-facing filter declarations.
- Filter request normalization.
- Filter toolbar rendering.
- Stimulus refresh on filter changes.
- Active filter summary.
- Clear filters action.
- Doctrine provider support for declared user filters.
- User filters on explicitly joined Doctrine fields.

#### Column visibility and preferences

- Runtime column visibility options.
- Column visibility toolbar controls.
- Column visibility request normalization.
- `DatatablePreference`.
- `DatatablePreferenceProviderInterface`.
- `NullDatatablePreferenceProvider`.
- Preferences applied to rendering defaults.

#### Exports

- `ExportFormat` enum.
- `ExportMode` enum.
- `DatatableExportRequest`.
- `DatatableExportResult`.
- `ExportWriterInterface`.
- `ExportWriterRegistry`.
- `CsvExportWriter`.
- CSV export endpoint.
- Current-view and full-dataset export modes.
- CSV export toolbar controls.
- Current datatable state propagation to CSV export links.
- Runtime column visibility support in CSV exports.
- Configurable CSV delimiter, enclosure, escape and UTF-8 BOM support.

#### Internationalization

- Built-in translation catalog.
- English translations.
- French translations.
- Translated labels for search, page size, filters, columns, exports, loading state, empty state, actions, sorting, pagination, boolean cells and result summaries.
- `DateTimeFormatterInterface`.
- Default datetime formatter.
- Locale-aware datetime formatting strategy.

#### Documentation

- Installation documentation.
- Configuration documentation.
- Basic usage documentation.
- Architecture documentation.
- Feature roadmap.
- Public API decisions.
- Doctrine provider documentation.
- Filters documentation.
- Actions and typed cell rendering documentation.
- Action security documentation.
- Exports documentation.
- Preferences documentation.
- Table controls documentation.
- Template override documentation.
- Template context reference.
- Cell template reference.
- Theming documentation.
- Icon strategy documentation.
- CI matrix and dependency strategy documentation.
- Changelog strategy documentation.
- Release workflow documentation.
- Packagist readiness checklist.
- Documentation review checklist.
- First pre-release checklist.
- Fresh Symfony smoke test plan.
- Smoke test report template.
- Smoke test findings report.
- Minimal array datatable example.
- Doctrine datatable example.

#### Release tooling

- Changelog fragment strategy.
- Changelog build script.
- GitHub release workflow.
- Release notes extraction script.
- First pre-release checklist.
- Packagist readiness documentation.

### Changed

- Reorganized architecture documentation by responsibility layers.
- Reorganized basic usage documentation as a progressive learning path.
- Reorganized documentation index by topic.
- Updated roadmap to reflect completed milestones.
- Changed result summary generation from hardcoded English strings to translated summary rendering.
- Changed transient loading/error UI state handling to use Bootstrap-compatible classes instead of relying on the `hidden` attribute with `d-flex`.
- Changed CSV export behavior so `full` means full filtered dataset without pagination, rather than unfiltered raw dataset.
- Changed datatable Stimulus controller integration from manual copying to UX-compatible package exposure.
- Changed column visibility controls so definition-hidden columns are no longer offered as toggleable UI controls.
- Changed export links to include the current datatable state.

### Fixed

- Fixed missing initial datatable data loading by adding Stimulus auto-load on connect.
- Fixed visible empty error alert and spinner in smoke application.
- Fixed user-facing filters not applying in `ArrayDataProvider`.
- Fixed table header not refreshing when column visibility changed.
- Fixed definition-hidden columns appearing in column visibility controls.
- Fixed CSV export links ignoring current frontend state.
- Fixed CSV exports not respecting runtime column visibility.
- Fixed CSV delimiter not being configurable.
- Fixed result summary wording and translation.
- Fixed Turbo prefetch triggering datatable action links on hover.
- Fixed several PHPStan issues around Symfony Config generics, PHPUnit mocks, enum single-case assertions and typed arrays.
- Fixed Doctrine functional test setup for SQLite and Symfony 8 dependency constraints.
- Fixed AssetMapper exposure for the bundle Stimulus controller.
- Fixed smoke-test documentation after real fresh Symfony integration.

### Security

- Added CSRF-aware form rendering for non-GET actions.
- Added optional Symfony authorization adapter for action visibility.
- Added action visibility extension point.
- Added safe default behavior for hidden actions: URLs are not generated for hidden actions.
- Added Turbo prefetch prevention for datatable action links.
- Documented that server-side routes must still enforce authorization even when actions are hidden in the UI.

### Known limitations

- The package is still an alpha-quality development preview.
- The public API may still change before a stable 1.0 release.
- Bootstrap is the only maintained theme.
- No Symfony Flex recipe exists yet.
- Host applications must still import routes manually.
- Host applications must enable the Stimulus controller through `assets/controllers.json`.
- Host applications must provide Bootstrap CSS and JavaScript.
- Doctrine provider supports explicit joins but not automatic deep association traversal.
- Collection-valued associations are not supported yet.
- ManyToMany and OneToMany aggregation are not supported yet.
- CSV is the only implemented export format.
- XLSX export is not implemented yet.
- Asynchronous exports are not implemented yet.
- Built-in preference persistence is not implemented.
- Frontend automated tests are not implemented yet.
- No built-in voters or action controllers are provided.
