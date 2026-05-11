# 0001 - Legacy code as functional reference only

## Status

Accepted

## Context

A previous application-specific datatable implementation exists.

It validated several useful concepts:

- PHP classes to declare datatables;
- PHP attributes;
- fluent column definition;
- Doctrine metadata inspection;
- persistent filters;
- custom joins;
- Twig cell templates;
- Ajax endpoints;
- export support.

However, that implementation is tightly coupled to a specific application and to frontend dependencies that are explicitly out of scope for this bundle.

## Decision

The legacy code must not be copied into this public repository.

Only anonymized documentation and sanitized examples may be committed.

The final bundle will be implemented from scratch using the legacy implementation as functional inspiration only.

## Consequences

The public repository will not expose application-specific code, routes, entities, services or business logic.

AI coding agents must rely on:

- `AGENTS.md`;
- `docs/legacy-reference/functional-lessons.md`;
- `docs/legacy-reference/anti-patterns.md`;
- `docs/legacy-reference/sanitized-examples.md`;
- project issues;
- architecture documents.

The bundle architecture remains free to be cleaner, smaller and more maintainable than the legacy implementation.