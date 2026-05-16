# Documentation overhaul plan

This document proposes a dedicated documentation overhaul milestone.

The documentation grew quickly while features were implemented. It contains useful information, but it now needs a structured cleanup pass before the bundle moves closer to a stable release.

See the [Documentation Audit](documentation-audit.md) for a detailed inventory and classification of files.

## Problem statement

Current issues:

- duplicated content across multiple pages;
- snippets kept after being merged;
- roadmap sections that can become stale quickly;
- installation details spread across several documents;
- frontend and Stimulus information spread across install, architecture and frontend test docs;
- export information split across CSV, XLSX, performance and roadmap sections;
- Doctrine information split across provider docs, performance docs, architecture docs and examples;
- public API notes not always reflected in feature docs;
- alpha/smoke findings mixed with long-term documentation.

## Goals

The documentation overhaul should:

- make installation easy to follow;
- make first usage easy to reproduce;
- separate user documentation from internal architecture notes;
- keep examples current;
- remove obsolete implementation notes;
- reduce duplicated content;
- make feature discovery easier;
- keep roadmap and decisions useful;
- prepare the documentation for external users.

## Non-goals

This milestone should not:

- implement runtime features;
- redesign the public API;
- add a documentation website generator;
- convert documentation to another toolchain.

Markdown in the repository remains enough for now.

## Proposed target structure

```text
README.md
CHANGELOG.md
docs/
    index.md
    installation.md
    quick-start.md
    configuration.md

    usage/
        basic-datatable.md
        array-provider.md
        doctrine-provider.md
        filters.md
        actions.md
        exports.md
        preferences.md
        theming.md

    examples/
        array-datatable.md
        doctrine-datatable.md
        custom-template.md
        actions-security.md

    reference/
        attributes.md
        datatable-definition.md
        column-definition.md
        filters.md
        actions.md
        export-writers.md
        template-context.md

    architecture/
        overview.md
        providers.md
        rendering.md
        stimulus.md
        exports.md
        doctrine.md

    decisions/
        0001-...
        0007-xlsx-export-strategy.md

    development/
        ci.md
        frontend-tests.md
        release-workflow.md
        packagist.md
        documentation-review.md

    roadmap.md
```

## Proposed README role

`README.md` should become a landing page, not a full manual.

It should contain:

- what the bundle is;
- supported Symfony/PHP versions;
- installation summary;
- minimal example;
- feature overview;
- links to key docs;
- current alpha/stability warning.

## Documentation style rules

- Use English for all project documentation.
- Prefer short sections and current code examples.
- Avoid duplicating explanations.
- Link to the canonical page instead of repeating.
- Keep alpha limitations explicit.
- Keep historical notes in decisions or smoke reports, not main usage docs.

## Docs to audit first

Priority list:

- `README.md`
- `docs/index.md`
- `docs/installation.md`
- `docs/basic-usage.md`
- `docs/configuration.md`
- `docs/doctrine-provider.md`
- `docs/doctrine-performance.md`
- `docs/filters.md`
- `docs/actions-and-cells.md`
- `docs/action-security.md`
- `docs/exports.md`
- `docs/xlsx-export.md`
- `docs/xlsx-export-performance.md`
- `docs/ui-ux-rendering.md`
- `docs/table-controls.md`
- `docs/theming.md`
- `docs/frontend-tests.md`
- `docs/architecture.md`
- `docs/public-api-review.md`
- `docs/roadmap.md`

## Cleanup categories

Each document should be classified as:

```text
keep
merge
rewrite
split
archive
delete
```

## Suggested milestone issues

1. Audit documentation and classify files.
2. Define final documentation structure.
3. Rewrite README as project landing page.
4. Rewrite installation and quick-start documentation.
5. Consolidate provider documentation.
6. Consolidate feature documentation.
7. Split architecture documentation into focused pages.
8. Remove obsolete snippets and stale notes.
9. Run final documentation review.

## Using ZenCoder or another assistant

A documentation assistant such as ZenCoder can help to:

- inventory docs;
- detect duplicate sections;
- propose file moves;
- rewrite individual docs in small batches;
- verify examples against the current codebase;
- produce a documentation map.

Human review remains required for public API accuracy and Symfony bundle conventions.

## Proposed next milestone

```text
0.22 - Documentation overhaul
```

Suggested timing:

After the urgent UI/UX smoke test fixes milestone is complete.
