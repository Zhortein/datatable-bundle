# Go / no-go review for first alpha tag

## Decision

Recommendation:

```text
GO for preparing a first alpha tag: v0.1.0-alpha.1
```

This decision means the bundle is considered ready for a first **development preview / alpha release**, not for stable production usage.

The public API may still change before `1.0`.

## Date

```text
2026-05-11
```

## Reviewed scope

This review covers the state of the bundle after milestone:

```text
0.15 - First alpha preparation
```

## Completed preparation work

### Core bundle foundation

Validated:

- Symfony 8+ bundle structure.
- Composer package metadata.
- Bundle configuration.
- Service discovery.
- Datatable registry.
- Data provider registry.
- Export writer registry.
- Twig function.
- Routes.
- Translations.
- CI and QA tooling.

### Rendering

Validated:

- Bootstrap-first Twig rendering.
- Datatable shell.
- Header/body/pagination fragments.
- Empty state.
- Loading/error states.
- Typed cell templates.
- Custom column templates.
- Row/global actions.
- CSRF-aware forms.
- Confirmation metadata and behavior.
- Column visibility controls.
- Page size selector.
- Export controls.

### Providers

Validated:

- `ArrayDataProvider`.
- `DoctrineOrmDataProvider`.
- Doctrine joins.
- Permanent filters.
- User-facing filters.
- Search.
- Sorting.
- Pagination.
- Joined fields.
- Joined filters/search/sort.

### Frontend

Validated:

- Stimulus controller loads through the Symfony UX / AssetMapper package strategy.
- Controller connects in a fresh Symfony application.
- Initial auto-load works.
- Ajax fragments refresh works.
- Search/filter/page size/sort/column visibility interactions work.
- Header and body stay synchronized.
- Turbo prefetch is disabled on action links.

### Exports

Validated:

- CSV current view export.
- CSV full filtered dataset export.
- Current datatable state is propagated to export links.
- Runtime column visibility is respected in CSV exports.
- CSV delimiter and formatting are configurable.

### Documentation

Validated:

- README documentation links.
- Installation documentation.
- Configuration documentation.
- Basic usage documentation.
- Smoke test plan.
- Smoke test report.
- Doctrine provider documentation.
- Filters documentation.
- Actions/security documentation.
- Export documentation.
- Template and theming documentation.
- Public API review.
- Packagist readiness checklist.
- Release workflow documentation.
- Changelog prepared for `0.1.0-alpha.1`.

## Fresh Symfony smoke test status

### Array datatable smoke test

Status:

```text
PASSED
```

Validated:

- local path repository installation;
- bundle registration;
- route import;
- Twig rendering;
- Stimulus controller;
- Bootstrap host integration;
- array data loading;
- global search;
- user-facing filters;
- page size selector;
- column visibility;
- header/body synchronization;
- CSV current export;
- CSV full export;
- configurable CSV delimiter;
- translated summary.

### Doctrine datatable smoke test

Status:

```text
PASSED
```

Validated:

- Doctrine entities in fresh app;
- Doctrine schema generation;
- smoke data loading;
- Doctrine-backed datatable discovery;
- joined entity column display;
- permanent filters;
- user-facing filters;
- sorting;
- pagination;
- CSV export.

### Actions and security smoke test

Status:

```text
PASSED
```

Validated:

- row GET actions;
- global GET actions;
- non-GET row actions rendered as forms;
- CSRF token rendering;
- confirmation behavior;
- action visibility checker replacement;
- Turbo prefetch disabled on action links.

## Resolved alpha blockers

The following smoke-test blockers were found and resolved:

- Stimulus controller was not exposed as a UX-compatible package.
- Bootstrap CSS/JS requirements were unclear.
- Initial datatable fragments were not auto-loaded.
- Loading/error states were visible by default.
- `ArrayDataProvider` did not apply declared user-facing filters.
- Table header did not refresh when column visibility changed.
- Definition-hidden columns appeared as toggleable visibility controls.
- CSV export links did not include current datatable state.
- CSV exports did not respect runtime column visibility.
- CSV delimiter and formatting were not configurable.
- Result summary was hardcoded and unclear.
- Turbo prefetch triggered datatable action links on hover.

All known alpha-blocking smoke test issues are resolved or documented.

## Known limitations accepted for alpha

The following limitations are accepted for `v0.1.0-alpha.1`.

### Stability

- The package is alpha-quality.
- Public API may still change before stable `1.0`.

### Installation

- No Symfony Flex recipe yet.
- Routes must be imported manually.
- Host applications must enable the Stimulus controller in `assets/controllers.json`.
- Host applications must provide Bootstrap CSS and JavaScript.

### Doctrine provider

- Explicit joins are supported.
- Automatic deep association traversal is not supported.
- Collection-valued associations are not supported.
- ManyToMany / OneToMany aggregation is not supported.
- Custom non-mapped joins are not supported.
- Advanced query expressions are not supported.

### Filters

- User filters are explicit and typed.
- Nested AND/OR filter groups are not supported.
- SearchBuilder-style expressions are not supported.
- Saved filter presets are not supported.

### Exports

- CSV is the only implemented export format.
- XLSX export is not implemented.
- Async exports are not implemented.
- Very large export streaming is not optimized yet.

### Preferences

- Preference provider extension point exists.
- Built-in preference persistence is not implemented.
- Save/reset preference actions are not implemented.

### Frontend

- Stimulus controller is manually enabled through Symfony UX config.
- No frontend test suite yet.
- No persisted frontend state.
- No advanced column ordering.

### Security

- Action visibility extension point exists.
- Optional Symfony authorization adapter exists.
- Built-in voters are not provided.
- Target action controllers remain the host application's responsibility.

## Release readiness checklist

### Required before tagging

- [x] CI green on `develop`.
- [x] Highest dependency job green.
- [x] Lowest dependency job green.
- [x] `make qa` green locally.
- [x] Changelog prepared.
- [x] Fresh Symfony array smoke test passed.
- [x] Fresh Symfony Doctrine smoke test passed.
- [x] Actions/security smoke test passed.
- [x] Known blockers resolved.
- [x] Known limitations documented.
- [x] Release workflow prepared.
- [x] Packagist readiness documented.

### Optional after tagging

- [ ] Submit to Packagist.
- [ ] Run install test from Packagist package.
- [ ] Create follow-up issues for alpha feedback.
- [ ] Decide Symfony Flex recipe strategy.

## Recommended release tag

Recommended first alpha tag:

```text
v0.1.0-alpha.1
```

## Recommended release branch flow

```bash
git checkout main
git pull
git merge --ff-only develop
git tag v0.1.0-alpha.1
git push origin main
git push origin v0.1.0-alpha.1
```

The GitHub release workflow should create a release from the tag.

## Go / no-go conclusion

Decision:

```text
GO
```

Reason:

The bundle has a coherent feature set, a working first integration path, green quality gates, validated smoke tests, documented limitations and a prepared changelog.

The release should be clearly communicated as:

```text
development preview / alpha
```

and not as stable production-ready software.
