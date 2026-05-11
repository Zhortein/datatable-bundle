# AI Agent Instructions

This repository contains the Symfony bundle `zhortein/datatable-bundle`.

## Project goal

Build a Symfony 8+ reusable bundle for business-oriented datatables.

The bundle provides Bootstrap-first datatables driven by PHP definitions, rendered with Twig, updated through Ajax calls handled by a Stimulus controller, with declarative actions, automatic Doctrine type detection, native Symfony translations, and future extensibility to non-Doctrine data sources.

## Hard rules

- Code and comments must be written in English.
- Follow Symfony bundle best practices strictly.
- Target Symfony 8+.
- Target PHP 8.4+.
- Keep compatibility with Doctrine ORM 3 and 4.
- JavaScript must be vanilla.
- Stimulus is allowed only as the Symfony UX integration layer.
- Bootstrap is the first supported UI framework.
- Do not introduce Tailwind support for now.
- Do not introduce jQuery.
- Do not introduce DataTables.net.
- Do not introduce BazingaJsTranslationBundle.
- Do not introduce unnecessary runtime dependencies.
- Do not instantiate datatable classes manually.
- Use Symfony services, service tags, autoconfiguration and dependency injection.
- Every non-trivial feature must include PHPUnit tests.
- PHPStan must pass at maximum level.
- PHP-CS-Fixer must pass with Symfony-oriented rules.
- twigcs must pass when Twig templates are added.

## Legacy warning

The files in `docs/legacy-ncmanager` come from an existing application implementation.

They show:
- expected developer experience;
- useful business features;
- examples of datatable definitions;
- Doctrine-driven field detection;
- persistent filters;
- custom joins;
- column metadata generation.

They must not be copied directly.

Do not reproduce:
- DataTables.net integration;
- jQuery usage;
- Select2 usage;
- BazingaJsTranslationBundle usage;
- a monolithic DatatableNet-like class;
- manual service instantiation;
- application-specific services;
- application-specific routes.

## Workflow rules

- Work in small steps.
- Do not rewrite unrelated files.
- Do not introduce a new architecture without explaining the reason.
- Before adding a new abstraction, explain what concrete problem it solves.
- Every task must end with tests or static analysis updates when relevant.
- Prefer boring, maintainable code over clever code.

