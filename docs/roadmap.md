# Roadmap

This roadmap reflects the current implementation state of `zhortein/datatable-bundle`.

The project follows an incremental strategy: each milestone must keep the bundle usable, tested and documented.

---

## Legend

- ✅ Completed
- 🚧 In progress / next
- 🕒 Planned
- 🔭 Later

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

Doctrine ORM as the first production-oriented data provider.

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
- `DatatableExportResult`.
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

# Next milestones

## 0.12 - Theming and template override polish 🚧

Improve rendering customization.

Planned:

- Formal template override documentation.
- Template context documentation.
- Theme configuration cleanup.
- Bootstrap template review.
- Optional compact table variant.
- Optional bordered/striped/hover options.
- First CSS utility documentation.
- Better icon strategy decision.

Target outcome:

```text
Host applications can customize rendering predictably without forking bundle templates.
```

Out of scope:

- Tailwind support;
- multiple fully maintained themes;
- complex design system abstraction.

---

## 0.13 - Security and action visibility 🕒

Make actions safer and context-aware.

Planned:

- Action visibility callbacks or strategy interface.
- Optional Symfony authorization checker integration.
- Per-action permission metadata.
- Conditional row actions.
- Safer confirmation handling.
- CSRF documentation hardening.
- Tests.

Target outcome:

```text
Actions can be rendered conditionally and securely according to application rules.
```

Out of scope:

- built-in role system;
- mandatory voters;
- application-specific security assumptions.

---

## 0.14 - Developer experience and release hardening 🕒

Prepare first public pre-release.

Planned:

- README final pass.
- Full documentation review.
- Changelog automation.
- Release workflow.
- Packagist readiness check.
- Composer metadata review.
- CI matrix review.
- Lowest/highest dependency stability.
- Symfony recipe decision.
- Public examples.

Target outcome:

```text
The bundle is ready for a first tagged development release.
```

---

# Later milestones

## 1.0 - First stable release 🔭

Expected stable scope:

- PHP-first datatable declarations.
- Symfony service discovery.
- Doctrine provider with simple joins.
- Global search.
- Typed filters.
- Sorting.
- Pagination.
- Row/global actions.
- CSRF-aware non-GET actions.
- Twig/Bootstrap rendering.
- Stimulus Ajax refresh.
- Translation catalog.
- Documentation.
- CI and quality tooling.

1.0 should not be tagged until the public API feels stable enough for real projects.

---

## Later ideas 🔭

Potential future work:

- multi-column sorting;
- SearchBuilder-like advanced expressions;
- async exports;
- XLSX export;
- user preference persistence adapters;
- API/data-source providers;
- Elasticsearch provider;
- UX Icons integration;
- richer enum badge/icon rendering;
- accessibility audit;
- frontend test suite;
- Symfony Flex recipe.
