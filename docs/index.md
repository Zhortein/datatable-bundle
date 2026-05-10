# Documentation

Welcome to the `zhortein/datatable-bundle` documentation.

This bundle is a Symfony 8+ datatable bundle for Bootstrap-first business tables driven by PHP definitions, Twig rendering and vanilla Stimulus Ajax updates.

## Start here

- [Installation](installation.md)
- [Configuration](configuration.md)
- [Basic usage](basic-usage.md)
- [First end-to-end flow](end-to-end-flow.md)

## Examples

- [Minimal array datatable example](examples/array-datatable.md)
- [Doctrine datatable example](examples/doctrine-datatable.md)

## Core concepts

- [Architecture](architecture.md)
- [Features](features.md)
- [Public API review](public-api-review.md)
- [Roadmap](roadmap.md)
- [Routes](routes.md)
- [CI matrix and dependency strategy](ci.md)

## Providers

- [Doctrine-backed datatables](doctrine-provider.md)
- [User-facing filters](filters.md)
- [Server-side exports](exports.md)

## Rendering and frontend

- [Stimulus and AssetMapper integration](stimulus-assetmapper.md)
- [Table controls and interactions](table-controls.md)
- [Twig templates and overrides](templates.md)
- [Template context reference](template-context.md)
- [Cell template reference](cell-templates.md)
- [Theming and rendering customization](theming.md)
- [Optional icon rendering strategy](icons.md)

## Actions and security

- [Actions and typed cell rendering](actions-and-cells.md)
- [Action security and visibility](action-security.md)

## Preferences and customization

- [Column visibility and preferences](preferences.md)

## Release and maintenance

- [Changelog strategy](changelog.md)
- [Release workflow](release.md)
- [Packagist readiness](packagist.md)
- [Documentation review checklist](documentation-review.md)

## Legacy reference

The bundle is inspired by a previous application-specific datatable implementation, but no private source code is included in this repository.

- [Functional lessons](legacy-reference/functional-lessons.md)
- [Anti-patterns](legacy-reference/anti-patterns.md)
- [Sanitized examples](legacy-reference/sanitized-examples.md)

## Architecture decisions

Architecture decisions are stored in `docs/decisions`.

Current decisions:

- [0001 - Legacy code as functional reference only](decisions/0001-legacy-code-as-functional-reference-only.md)
- [0002 - Initial public datatable API](decisions/0002-initial-public-api.md)
- [0003 - Bootstrap rendering strategy](decisions/0003-bootstrap-rendering-strategy.md)
- [0004 - Vanilla Stimulus interaction model](decisions/0004-vanilla-stimulus-interaction-model.md)
- [0005 - Doctrine ORM provider architecture](decisions/0005-doctrine-orm-provider-architecture.md)

## Current status

The bundle is still under active development.

It is not stable yet.

Current implemented areas include:

- PHP datatable declarations;
- service discovery;
- data providers;
- Doctrine ORM provider;
- Twig/Bootstrap rendering;
- Stimulus Ajax fragments;
- actions;
- filters;
- column visibility;
- server-side CSV export;
- translations;
- documentation and CI tooling.

See [Roadmap](roadmap.md) for the detailed milestone status.
