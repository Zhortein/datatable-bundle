# Documentation Review

This document records documentation reviews for `zhortein/datatable-bundle`. It is a review log, not a guarantee that documentation can no longer drift from the implementation.

## V1 getting-started review (2026-07-23)

The installation and first-use path was replayed against the current bundle implementation after feedback from a real host application.

### Issues found and fixed

- Replaced the invalid `asset-mapper:compile` command with `asset-map:compile`.
- Documented Bootstrap Icons as a required frontend dependency for the default icon set.
- Documented the complete AssetMapper, StimulusBundle and lazy controller setup.
- Added the missing export route to the route reference.
- Replaced the partial quick start with a complete datatable, controller and Twig page.
- Fixed the broken release-checklist link to the array example.
- Clarified explicit selection of built-in and custom providers.

### Automated safeguards

- Active local Markdown links are checked by the frontend test suite.
- Critical installation commands and frontend dependencies are asserted by tests.
- The route reference is checked for both fragments and export routes.
- The documented array-provider path is covered by a functional test from definition creation through fragments rendering.

### Remaining manual validation

- Run the release smoke test in a fresh Symfony application before tagging `1.0.0`.
- Capture any host-application-specific assumptions not reproducible in the bundle test kernel.

## Initial quality pass (2026-05-16)

The documentation was reviewed for accuracy, consistency, and completeness.

### Key Entry Points
- [x] **README.md**: Clear landing page with status, requirements, installation summary, and minimal example.
- [x] **docs/index.md**: Comprehensive navigation map grouping links by topic.
- [x] **docs/roadmap.md**: Accurately reflects implementation state (0.22 marked as complete).

### Core Guides
- [x] **Installation**: Covers Flex (no recipe), registration, routes, Stimulus, and Bootstrap requirements.
- [x] **Quick Start**: Practical example using `ArrayDataProvider`.
- [x] **Providers**: Clear overview of Array and Doctrine providers.
- [x] **Feature Hubs**: Centralized docs for Filters, Actions/Security, Exports, UI/UX, and Theming.

### Architecture
- [x] **Modular Structure**: Split into Overview, Providers, Rendering, Stimulus, Exports, and Doctrine.
- [x] **Decisions**: ADRs are preserved and indexed.

### Consistency and API Accuracy
- [x] **Attributes**: `#[AsDatatable]` usage is consistent.
- [x] **Interfaces**: `DatatableInterface` and `DatatableDefinition` are correctly referenced.
- [x] **Twig**: `zhortein_datatable()` function calls match implementation.
- [x] **Doctrine**: Alias `e` usage is clearly explained.

### Required Notices
- [x] **Alpha Status**: Explicitly stated in README, index, and multiple feature pages.
- [x] **Symfony Flex**: Manual steps are documented; no recipe notice included.
- [x] **Bootstrap**: Host application requirement is clear.
- [x] **Stimulus**: Controller activation requirement is documented.
- [x] **XLSX**: `openspout/openspout` optional dependency is mentioned.
- [x] **Large Exports**: Synchronous/Memory limitations are explicitly stated.

## Code/documentation consistency review (2026-05-16)

Performed a final consistency check between the current codebase and the documentation.

### Summary of checks
- Verified all public APIs (`AsDatatable`, `DatatableInterface`, `DatatableDefinition`) against source code.
- Verified all Twig options and Bootstrap templates against implementation.
- Verified Doctrine provider capabilities (joins, filters, search, sorting, aggregates).
- Verified Export formats and modes.
- Verified Frontend test coverage against actual Vitest suites.
- Verified `CHANGELOG.md` reflects major additions since `alpha.1`.

### Documentation updates made
- Updated `CHANGELOG.md` with unreleased changes from milestones 0.16 to 0.22.
- Verified `docs/roadmap.md` reflects current completion status.
- Verified `README.md` and `docs/index.md` links and examples.

### Remaining code/doc mismatches

None were identified during that pass. The later v1 review above found additional installation and route-reference gaps.

## Remaining Known Documentation Limitations

- No hosted demo site (planned for future).
- No search functionality within documentation files.
- No automated execution of the installation guide in a generated external Symfony application.

## Recommended Next Documentation Tasks

- [ ] Add visual screenshots/GIFs to feature pages.
- [ ] Create a "Common Recipes" section for complex Doctrine joins or custom filters.
- [x] Implement automated local Markdown link checking in the existing frontend CI job.

## Summary

The documentation provides a tested installation and first-use path while maintaining transparency about the current prerelease status. A fresh-application smoke test remains mandatory before the stable tag.
