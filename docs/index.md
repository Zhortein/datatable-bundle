# Documentation

Welcome to the `zhortein/datatable-bundle` documentation.

This bundle is a Symfony 8+ datatable bundle for Bootstrap-first business tables driven by PHP definitions, Twig rendering and vanilla Stimulus Ajax updates.

## Start here

- [Installation](installation.md)
- [Quick Start](quick-start.md)
- [Configuration](configuration.md)

## Core features

- [Providers](providers.md): Array and Doctrine data sources.
- [Filters](filters.md): Toolbar and header-based data filtering.
- [Advanced Filters](advanced-filters.md): Complex nested filtering with Search Builder.
- [Multi-column Sorting](sorting.md): Ordered sorting across providers, state, views, and exports.
- [Actions and Security](actions.md): Row-level and global table actions with CSRF and authorization.
- [Explicit Context](context.md): Signed locale, tenant and business-scope propagation.
- [Hierarchical Datatables](hierarchical-datatables.md): Lazy, signed and provider-independent parent/child tables.
- [URL State and Browser History](url-state.md): Shareable per-instance state, Back/Forward and Turbo restoration.
- [Named Saved Views](saved-views.md): Opt-in user views with generic storage and authorization contracts.
- [Bulk Actions and Selection](bulk-actions.md): Managing multiple rows at once.
- [UI/UX and Controls](ui-ux.md): Search, pagination, sorting, and UI customization.
- [Icon System](icons.md): Unified icon strategy and configuration.
- [Theming and Templates](theming.md): Customizing the look, icon strategies, and template overrides.
- [Cell Context and Computed Values](cell-context.md): Rich server-side cells, provider sources and export-safe resolvers.
- [Enum Presentation](enum-presentation.md): Localized labels, badges, filter choices and export-safe enum metadata.
- [Server-side Exports](exports.md): CSV and XLSX data exports.

## Technical Architecture

- [Architecture Overview](architecture.md)
- [Architecture Decisions](decisions/index.md)

## In-depth guides

- [Doctrine-backed datatables](doctrine-provider.md)
- [Doctrine performance](doctrine-performance.md)
- [Computed cell example](examples/computed-cell.md)

## Development and Reference

- [Public API and compatibility policy](public-api.md)
- [Roadmap](roadmap.md)
- [Changelog](changelog.md)
- [Routes](routes.md)
- [Frontend tests](frontend-tests.md)
- [Development docs](development.md)

## Historical and Archive

- [Documentation Audit](documentation-audit.md)
- [Archive index](./archive/milestones/go-no-go-first-alpha.md)

---

*The stable 1.x public API is documented in the [compatibility policy](public-api.md).*
