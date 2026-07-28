# Architecture

This document serves as the index for the technical architecture of `zhortein/datatable-bundle`.

## Core Architecture

- [**Overview**](architecture/overview.md): High-level flow, configuration, declaration layer, and quality gates.
- [**Providers**](architecture/providers.md): Data provider abstraction, registry, and the Array provider.
- [**Rendering**](architecture/rendering.md): Twig-first renderer, component structure, Bootstrap theme, and accessibility.
- [**Stimulus**](architecture/stimulus.md): Frontend interaction model, controller responsibilities, and AssetMapper integration.
- [**Exports**](architecture/exports.md): Server-side export model, writer contract, and format implementations.
- [**Asynchronous exports**](async-exports.md): Background lifecycle, Messenger, persistence, security and cleanup.
- [**Doctrine Provider**](architecture/doctrine.md): DQL query building, metadata resolution, joins, and aggregates.
- [**Icons**](decisions/0008-icon-strategy-and-configuration-model.md): CSS-class based icon strategy and configuration model.

## Related Documentation

- [**Architecture Decisions**](decisions/index.md): Historical record of significant design choices.
- [**Roadmap**](roadmap.md): Planned features and future direction.
