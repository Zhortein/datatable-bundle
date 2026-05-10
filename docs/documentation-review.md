# Documentation review checklist

This document provides a checklist for reviewing the documentation before a release or major milestone.

## Goals

Documentation should be:

- accurate;
- easy to navigate;
- aligned with implemented behavior;
- explicit about current limitations;
- free from private/client-specific references;
- useful for a Symfony developer discovering the bundle.

## Entry points

Review these first:

- `README.md`
- `docs/index.md`
- `docs/basic-usage.md`
- `docs/installation.md`
- `docs/configuration.md`
- `docs/roadmap.md`

## README checklist

The README should include:

- clear package description;
- current project status;
- requirements;
- installation link;
- minimal usage example;
- documentation links;
- development commands;
- quality requirements;
- license.

The README should not:

- claim the bundle is stable before a stable tag exists;
- mention private applications;
- contain outdated roadmap claims;
- duplicate all detailed documentation.

## Docs index checklist

`docs/index.md` should provide a navigable map of the documentation.

It should group links by topic:

- start here;
- examples;
- core concepts;
- providers;
- rendering/frontend;
- actions/security;
- preferences;
- release/maintenance;
- legacy reference;
- architecture decisions.

All important docs should be reachable from this page.

## Installation checklist

`docs/installation.md` should explain:

- requirements;
- bundle installation;
- bundle registration;
- route import;
- translation support;
- Stimulus/AssetMapper setup;
- Doctrine setup;
- current manual integration steps;
- current limitations.

## Configuration checklist

`docs/configuration.md` should describe:

- all current configuration keys;
- defaults;
- allowed values;
- runtime override behavior;
- translation domain;
- theme defaults;
- providers;
- routes;
- datetime formatting;
- current limitations.

## Basic usage checklist

`docs/basic-usage.md` should provide a learning path:

1. declare a datatable;
2. render it in Twig;
3. understand Ajax behavior;
4. use array provider;
5. use Doctrine provider;
6. add filters;
7. add actions;
8. use typed cells;
9. use column visibility/preferences;
10. understand limitations.

It should link to specialized docs instead of duplicating everything.

## Roadmap checklist

`docs/roadmap.md` should reflect the real implementation state.

For completed milestones, verify:

- delivered items are actually implemented;
- limitations are explicit;
- current status is realistic.

For future milestones, verify:

- items are not over-promised;
- dependencies between milestones make sense;
- 1.0 expectations remain conservative.

## Architecture checklist

`docs/architecture.md` should describe responsibility layers, not a chronological history.

Expected structure:

- high-level flow;
- datatable declaration;
- definition model;
- request/result objects;
- data providers;
- Doctrine provider;
- rendering;
- actions;
- Ajax;
- Stimulus;
- tests/quality;
- limitations;
- documentation map.

## Feature documentation checklist

Review these feature docs when corresponding code changes:

- `docs/doctrine-provider.md`
- `docs/filters.md`
- `docs/actions-and-cells.md`
- `docs/action-security.md`
- `docs/exports.md`
- `docs/preferences.md`
- `docs/table-controls.md`
- `docs/templates.md`
- `docs/template-context.md`
- `docs/cell-templates.md`
- `docs/theming.md`
- `docs/icons.md`

Each feature doc should include:

- status;
- examples;
- supported behavior;
- current limitations;
- related documentation links.

## Examples checklist

Review examples:

- `docs/examples/array-datatable.md`
- `docs/examples/doctrine-datatable.md`

Examples should:

- use current namespaces and classes;
- avoid private/client names;
- avoid unsupported features;
- include enough code to understand the feature;
- link to detailed docs.

## Release documentation checklist

Review:

- `CHANGELOG.md`
- `docs/changelog.md`
- `docs/release.md`
- `docs/packagist.md`

Before release, ensure:

- changelog reflects current work;
- release process is clear;
- Packagist readiness checklist is current;
- no release automation publishes unexpectedly.

## Link hygiene

Before a release, check for:

- broken relative links;
- renamed documents;
- stale issue references;
- references to files that do not exist;
- duplicate or contradictory sections.

A future CI step may automate Markdown link checking.

## Private information checklist

Documentation must not include:

- private client names;
- private project names;
- internal application routes;
- internal business rules;
- credentials;
- screenshots from private systems;
- unredacted legacy source code.

The legacy reference must remain sanitized.

## Current limitations checklist

Every major feature doc should clearly state current limitations.

This is especially important before the first public pre-release.

## Suggested manual review flow

Before tagging a release:

```text
README.md
→ docs/index.md
→ docs/installation.md
→ docs/basic-usage.md
→ docs/configuration.md
→ docs/roadmap.md
→ feature docs
→ release docs
```

Then run:

```bash
make qa
```

## Future improvements

Potential documentation tooling:

- Markdown link checker;
- docs spellcheck;
- examples smoke tests;
- automatic docs table of contents;
- Symfony app smoke test using the docs examples.

## Smoke test report review

Before a pre-release, review the latest smoke test report under:

```text
docs/smoke-reports/
```

The report should identify:

- setup issues;
- documentation gaps;
- blocking runtime errors;
- non-blocking improvements;
- go/no-go recommendation.
