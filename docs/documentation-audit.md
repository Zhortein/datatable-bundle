# Documentation Audit

This document provides a comprehensive inventory and classification of the existing documentation files in the `zhortein/datatable-bundle` repository.

## Documentation Inventory

| Path | Current Purpose | Target Status | Proposed Destination | Notes |
|------|-----------------|---------------|----------------------|-------|
| `README.md` | Main landing page | rewrite | `README.md` | Move detailed docs to dedicated pages. |
| `CHANGELOG.md` | Project changelog | keep | `CHANGELOG.md` | |
| `AGENTS.md` | AI Agent instructions | archive | `docs/development/agents.md` | Useful for AI, but internal. |
| `docs/index.md` | Documentation TOC | rewrite | `docs/index.md` | Restructure for better navigation. |
| `docs/installation.md` | Installation guide | rewrite/split | `docs/installation.md` | Consolidate installation details. |
| `docs/basic-usage.md` | Quick start | rewrite/merge | `docs/quick-start.md` | Combine with minimal examples. |
| `docs/configuration.md` | Config reference | rewrite | `docs/configuration.md` | Standardize as canonical reference. |
| `docs/architecture.md` | Monolithic architecture | split | `docs/architecture/` | Split into focused topics (too large). |
| `docs/features.md` | Feature roadmap | merge | `README.md` / `docs/index.md` | Redundant with roadmap/README. |
| `docs/roadmap.md` | Project goals/status | keep/update | `docs/roadmap.md` | |
| `docs/development.md` | Dev workflow | rewrite | `docs/development/index.md` | Hub for development docs. |
| `docs/release.md` | Release workflow | merge | `docs/development/release.md` | Consolidate release docs. |
| `docs/release-checklist.md`| Alpha checklist | merge | `docs/development/release.md` | Consolidate with release. |
| `docs/ci.md` | CI documentation | merge | `docs/development/ci.md` | |
| `docs/packagist.md` | Readiness checklist | archive | `docs/archive/milestones/` | Internal milestone doc. |
| `docs/documentation-review.md` | Docs checklist | merge | `docs/development/docs.md` | |
| `docs/documentation-overhaul-plan.md` | Overhaul plan | keep | `docs/decisions/` or `archive` | Record of this milestone. |
| `docs/public-api-review.md`| API design notes | archive | `docs/archive/milestones/` | Internal design session. |
| `docs/doctrine-provider.md`| Doctrine usage | move | `docs/usage/doctrine-provider.md` | |
| `docs/doctrine-performance.md`| Doctrine performance | merge | `docs/usage/doctrine-provider.md` | |
| `docs/filters.md` | Filter usage | move | `docs/usage/filters.md` | |
| `docs/actions-and-cells.md`| Actions/Cells | move/split | `docs/usage/actions.md` | Split into usage and reference. |
| `docs/action-security.md` | Action security | merge | `docs/usage/actions.md` | |
| `docs/exports.md` | Export usage | move | `docs/usage/exports.md` | |
| `docs/xlsx-export.md` | XLSX specifics | merge | `docs/usage/exports.md` | |
| `docs/xlsx-export-performance.md`| XLSX performance | merge | `docs/usage/exports.md` | |
| `docs/ui-ux-rendering.md` | UI/UX notes | merge | `docs/usage/theming.md` | |
| `docs/table-controls.md` | UI controls | move | `docs/usage/table-controls.md` | |
| `docs/theming.md` | Theming guide | move/rewrite | `docs/usage/theming.md` | |
| `docs/templates.md` | Twig overrides | merge | `docs/usage/theming.md` | |
| `docs/template-context.md` | Reference | move | `docs/reference/template-context.md` | |
| `docs/cell-templates.md` | Cell reference | merge | `docs/usage/theming.md` | |
| `docs/stimulus-assetmapper.md`| Frontend integration | merge | `docs/architecture/stimulus.md` | |
| `docs/preferences.md` | User preferences | move | `docs/usage/preferences.md` | |
| `docs/routes.md` | Route reference | move | `docs/reference/routes.md` | |
| `docs/icons.md` | Icon strategy | merge | `docs/usage/theming.md` | |
| `docs/smoke-test.md` | Test plan | move | `docs/development/smoke-testing.md` | |
| `docs/smoke-test-report-template.md`| Report template | move | `docs/development/smoke-testing.md` | |
| `docs/frontend-tests.md` | JS tests | move | `docs/development/frontend-tests.md` | |
| `docs/examples/array-datatable.md`| Example | move | `docs/examples/array-datatable.md` | |
| `docs/examples/doctrine-datatable.md`| Example | move | `docs/examples/doctrine-datatable.md` | |
| `docs/legacy-reference/anti-patterns.md`| Legacy notes | archive | `docs/archive/legacy/` | |
| `docs/legacy-reference/functional-lessons.md`| Legacy notes | archive | `docs/archive/legacy/` | |
| `docs/legacy-reference/sanitized-examples.md`| Legacy notes | archive | `docs/archive/legacy/` | |
| `docs/decisions/*.md` | ADRs | keep | `docs/decisions/` | |
| `docs/ai/legacy-analysis.md` | AI analysis | archive | `docs/archive/ai/` | |
| `docs/smoke-reports/*.md` | Past reports | archive | `docs/archive/smoke-reports/` | |
| `docs/releases/go-no-go-first-alpha.md`| Alpha prep | archive | `docs/archive/releases/` | |
| `docs/end-to-end-flow.md` | Flow diagram | move | `docs/architecture/overview.md` | |

## Duplicate Topics

- **Installation**: Detailed instructions are repeated in `README.md`, `docs/installation.md`, and `docs/architecture.md`.
- **Doctrine ORM**: Content is fragmented across `docs/doctrine-provider.md`, `docs/doctrine-performance.md`, and a huge section in `docs/architecture.md`.
- **Exports**: CSV and XLSX information is split between `docs/exports.md`, `docs/xlsx-export.md`, `docs/xlsx-export-performance.md`, and `docs/architecture.md`.
- **Stimulus/AssetMapper**: Spread across `docs/stimulus-assetmapper.md`, `docs/installation.md`, and `docs/architecture.md`.
- **Actions & Security**: Covered in `docs/actions-and-cells.md`, `docs/action-security.md`, and `docs/architecture.md`.

## Obsolete Snippets & Stale Notes

- **"Current Status" Sections**: Multiple files (e.g., `README.md`, `docs/index.md`, `docs/architecture.md`) have status notes that are likely to become stale. These should be consolidated in `docs/roadmap.md` or `docs/index.md`.
- **"Expected Direction"**: Documentation written before implementation often uses future tense. These should be updated to present tense if implemented, or moved to roadmap/decisions.
- **Internal Milestones**: Files like `docs/packagist.md` and `docs/public-api-review.md` served their purpose for specific development phases and are no longer active documentation.

## Missing Canonical Pages

- `docs/quick-start.md`: A 5-minute guide to get a first datatable running.
- `docs/usage/basic-datatable.md`: A user-focused guide on how to define and use a datatable.
- `docs/reference/attributes.md`: A dedicated reference for PHP attributes (e.g., `#[AsDatatable]`, `#[AsDatatableColumn]`).
- `docs/reference/datatable-definition.md`: A method-by-method reference for `DatatableDefinition`.

## Proposed Target Documentation Structure

```text
README.md
CHANGELOG.md
docs/
    index.md
    installation.md
    quick-start.md
    configuration.md
    roadmap.md

    usage/
        basic-datatable.md
        doctrine-provider.md
        array-provider.md
        filters.md
        actions.md
        exports.md
        preferences.md
        theming.md

    reference/
        attributes.md
        datatable-definition.md
        column-definition.md
        filter-types.md
        action-types.md
        template-context.md
        routes.md

    examples/
        array-datatable.md
        doctrine-datatable.md
        custom-rendering.md

    architecture/
        overview.md
        definitions.md
        providers.md
        doctrine.md
        rendering.md
        frontend.md

    development/
        index.md
        testing.md
        frontend-tests.md
        smoke-testing.md
        release-workflow.md
        ci.md
        documentation.md
        agents.md

    decisions/
        0001-legacy-code-as-functional-reference-only.md
        ...

    archive/
        milestones/
        smoke-reports/
        legacy/
        ai/
```
