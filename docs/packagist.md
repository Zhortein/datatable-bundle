# Packagist readiness

This document lists the checks required before publishing `zhortein/datatable-bundle` to Packagist.

The package is not ready for a stable release yet, but it can be prepared for a first development/pre-release once CI, documentation and metadata are coherent.

## Package identity

Expected Composer package name:

```text
zhortein/datatable-bundle
```

Expected GitHub repository:

```text
https://github.com/Zhortein/datatable-bundle
```

Expected package type:

```text
symfony-bundle
```

## Composer metadata checklist

Before publishing, verify `composer.json`.

### Name

```json
"name": "zhortein/datatable-bundle"
```

### Type

```json
"type": "symfony-bundle"
```

### License

```json
"license": "MIT"
```

### Description

The description should be short and accurate.

Expected direction:

```json
"description": "A Symfony 8+ bundle for Bootstrap-first business datatables driven by PHP definitions."
```

### Keywords

Recommended keywords:

```json
"keywords": [
  "symfony",
  "bundle",
  "datatable",
  "datatables",
  "bootstrap",
  "twig",
  "stimulus",
  "doctrine",
  "php-attributes"
]
```

### Autoload

Expected runtime autoload:

```json
"autoload": {
  "psr-4": {
    "Zhortein\\DatatableBundle\\": "src/"
  }
}
```

Expected dev autoload:

```json
"autoload-dev": {
  "psr-4": {
    "Zhortein\\DatatableBundle\\Tests\\": "tests/"
  }
}
```

### Symfony extra metadata

Expected:

```json
"extra": {
  "symfony": {
    "require": "8.0.*"
  }
}
```

This is aligned with the bundle target of Symfony 8+.

## Runtime dependency checklist

Runtime dependencies should include only what the bundle actually needs in production.

Review runtime dependencies for:

- Symfony Config;
- Symfony DependencyInjection;
- Symfony HttpFoundation;
- Symfony HttpKernel;
- Symfony Routing;
- Symfony Security CSRF;
- Symfony Translation;
- Symfony Twig integration;
- Twig;
- YAML if translation loading requires it.

Doctrine should remain optional if possible and be documented as required only for Doctrine-backed datatables.

## Development dependency checklist

Development dependencies should include:

- PHPUnit;
- PHPStan;
- PHPStan Symfony extension;
- PHPStan Doctrine extension;
- PHPStan PHPUnit extension;
- PHPStan strict rules;
- PHP-CS-Fixer;
- twigcs;
- Doctrine ORM / DoctrineBundle for functional tests;
- Symfony FrameworkBundle for functional tests.

## Suggest section

The `suggest` section should document optional integrations.

Recommended suggestions:

```json
"suggest": {
  "doctrine/orm": "Required to use Doctrine ORM backed datatables.",
  "doctrine/doctrine-bundle": "Required to use Doctrine ORM backed datatables in Symfony applications.",
  "symfony/asset-mapper": "Recommended to expose the provided Stimulus controller with Symfony AssetMapper.",
  "symfony/stimulus-bundle": "Recommended to integrate the provided vanilla Stimulus controller.",
  "ext-intl": "Recommended for locale-aware datetime formatting through IntlDateFormatter."
}
```

## Support metadata

Recommended support section:

```json
"support": {
  "issues": "https://github.com/Zhortein/datatable-bundle/issues",
  "source": "https://github.com/Zhortein/datatable-bundle",
  "docs": "https://github.com/Zhortein/datatable-bundle/tree/main/docs"
}
```

## Repository checklist

Before publishing, verify GitHub repository settings.

### Repository description

Suggested description:

```text
A Symfony 8+ bundle for Bootstrap-first business datatables driven by PHP definitions, Twig rendering and Stimulus Ajax updates.
```

### Topics

Suggested topics:

```text
symfony
symfony-bundle
datatable
datatables
bootstrap
twig
stimulus
doctrine
php
php-attributes
```

### Default branch

The default branch should be `main` for releases.

Development may continue on `develop`.

### Branch protection

Recommended:

- protect `main`;
- require pull requests;
- require CI checks;
- prevent force pushes;
- prevent deletion.

### Security features

Recommended:

- Dependency graph enabled;
- Dependabot alerts enabled;
- Dependabot security updates enabled;
- Dependabot malware alerts enabled if available;
- secret scanning enabled if available;
- push protection enabled if available.

## Documentation checklist

Before publishing, the following docs should exist and be linked from README or docs index:

- installation;
- configuration;
- basic usage;
- routes;
- Stimulus/AssetMapper integration;
- Doctrine provider;
- filters;
- actions and cells;
- action security;
- exports;
- template override strategy;
- template context reference;
- cell template reference;
- preferences;
- theming;
- CI strategy;
- changelog strategy;
- release workflow;
- examples.

## README checklist

The README should include:

- project status;
- requirements;
- installation link;
- minimal usage example;
- documentation index links;
- development commands;
- quality requirements;
- license.

It should not claim the bundle is stable until a stable tag exists.

## CI checklist

Before publishing:

```bash
make qa
```

must pass locally.

GitHub Actions must pass on:

- highest dependencies;
- lowest dependencies.

CI should not contain temporary debug steps.

## Versioning checklist

Before the first tag:

- decide first version number;
- likely start with a pre-release such as `v0.1.0-alpha.1`;
- update `CHANGELOG.md`;
- ensure release notes exist;
- confirm roadmap limitations are explicit.

## Packagist publication checklist

When ready:

1. Ensure the repository is public.
2. Ensure `composer.json` validates strictly.
3. Ensure a tag exists.
4. Submit the repository to Packagist.
5. Configure Packagist auto-update webhook if desired.
6. Verify package metadata on Packagist.
7. Test installation in a fresh Symfony application.

Expected install command after publication:

```bash
composer require zhortein/datatable-bundle
```

For pre-release versions, the consuming application may need appropriate `minimum-stability`.

## Smoke test checklist

Before publishing, test the package in a fresh Symfony application:

1. Install the bundle.
2. Register the bundle.
3. Import routes.
4. Expose Stimulus controller.
5. Render a minimal array datatable.
6. Render a Doctrine datatable.
7. Test Ajax refresh.
8. Test filters.
9. Test row actions.
10. Test CSV export.
11. Run the host application tests if available.

## Current publication status

The package should not be presented as stable yet.

Recommended status:

```text
Development preview / alpha
```

Possible first tag direction:

```text
v0.1.0-alpha.1
```

## Current blockers before a first pre-release

Potential blockers to review:

- public API naming;
- Composer runtime dependencies;
- documentation navigation;
- installation instructions;
- manual Stimulus registration;
- no Flex recipe yet;
- no fresh-app smoke test yet.

## Optional XLSX dependency

The package supports XLSX export when OpenSpout is installed.

Packagist metadata should mention OpenSpout as an optional suggestion:

```json
"suggest": {
  "openspout/openspout": "Required to enable XLSX export support."
}
```

CSV export remains available without this dependency.

## First pre-release checklist

Before submitting the package to Packagist, review [`release-checklist.md`](release-checklist.md).

The checklist includes fresh Symfony application smoke tests and known limitations that should be reviewed before publication.

## Related documentation

- [`release.md`](release.md)
- [`release-checklist.md`](release-checklist.md)
- [`changelog.md`](changelog.md)
- [`ci.md`](ci.md)
- [`roadmap.md`](roadmap.md)
