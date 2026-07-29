# CI matrix and dependency strategy

This document describes the current CI and dependency testing strategy for `zhortein/datatable-bundle`.

The goal is to keep the bundle compatible with its declared dependency constraints while avoiding false confidence from testing only the newest versions.

## Quality gates

Every pull request must pass:

- Composer validation;
- PHPUnit;
- PHPStan at maximum level;
- PHP-CS-Fixer in dry-run mode;
- twigcs;
- frontend tests;
- installation and fragment rendering in a fresh Symfony application;
- highest dependency set;
- lowest dependency set.

## Current PHP target

The bundle targets:

```text
PHP >= 8.4
```

The CI currently tests PHP 8.4 and PHP 8.5.

Future PHP versions can be added when they are available and stable in the project environment.

The fresh-application smoke job uses PHP 8.4, the minimum supported version.

## Symfony target

The bundle targets:

```text
Symfony 8+
```

Composer metadata constrains Symfony packages to Symfony 8 where the bundle directly depends on them.

## Dependency matrix

The CI uses a dependency strategy with two modes:

```text
highest
lowest
```

### Highest dependencies

The `highest` job runs Composer with the newest compatible dependency versions.

Purpose:

- catch issues with newly released dependencies;
- validate the package against the current ecosystem;
- keep the bundle forward-friendly.

### Lowest dependencies

The `lowest` job runs Composer with the lowest compatible dependency versions.

Purpose:

- ensure declared lower bounds are actually valid;
- catch missing minimum constraints;
- prevent Composer metadata from claiming unsupported versions.

The lowest job has already caught useful issues such as dependency lower bounds that were technically installable but not practically compatible with Symfony 8 / PHP 8.4.

## Why lowest matters

If a package constraint says:

```json
"some/package": "^1.0"
```

then Composer may install `1.0.0` in `--prefer-lowest`.

If the code only works with `1.4+`, the constraint is wrong.

The CI must catch this before a release.

## Current workflow shape

The workflow should roughly follow this order:

```text
checkout
setup PHP
composer validate --strict
composer update --prefer-lowest or composer update
PHPUnit
PHPStan
PHP-CS-Fixer
twigcs
```

Using `composer update` in CI is intentional: it validates the dependency constraints rather than only validating the committed lock file.

## Composer validate

The workflow should run:

```bash
composer validate --strict
```

This ensures package metadata remains coherent.

## PHPUnit

The workflow should run:

```bash
vendor/bin/phpunit
```

Debug flags such as `--debug` or `--display-errors` should only be used temporarily while diagnosing CI failures.

## PHPStan

The workflow should run:

```bash
composer phpstan
```

The Composer script currently includes:

```bash
phpstan analyse --configuration=phpstan.neon.dist --memory-limit=1G
```

The memory limit is intentional because Symfony/Doctrine functional tests and static analysis can exceed PHP's default memory limit.

## PHP-CS-Fixer

The workflow should run:

```bash
vendor/bin/php-cs-fixer fix --dry-run --diff --config=.php-cs-fixer.dist.php
```

The fixer must not modify files in CI.

## twigcs

The workflow should run:

```bash
vendor/bin/twigcs templates --config=.twig-cs-fixer.php
```

The Composer script may skip twigcs when no Twig templates exist, but this bundle now contains templates, so twigcs should run.

## Required PHP extensions

The local Docker tooling and GitHub Actions should provide at least:

```text
mbstring
intl
pdo_sqlite
dom
xml
zip
```

Rationale:

- `mbstring`: string handling;
- `intl`: locale-aware datetime formatting where available;
- `pdo_sqlite`: Doctrine functional tests;
- `dom`, `xml`: PHPUnit/Twig/Symfony tooling;
- `zip`: Composer/package tooling.

## Doctrine test dependencies

The bundle supports Doctrine integration through optional/provider-specific code.

The test environment uses:

- `doctrine/doctrine-bundle`;
- `doctrine/orm`;
- `doctrine/dbal`;
- `symfony/doctrine-bridge`.

Important lower-bound lessons:

- Doctrine ORM lower bound must remain high enough for DoctrineBundle/Symfony 8 functional tests.
- Symfony Doctrine Bridge must remain compatible with Symfony DependencyInjection 8 signatures.

## Symfony Config and PHPStan

Symfony Config builder generics differ across dependency versions.

Some targeted PHPStan ignores may exist for `src/ZhorteinDatatableBundle.php`.

These ignores must remain narrow and documented.

They should not be used to hide general project errors.

## Node.js / GitHub Actions

GitHub-hosted runners may deprecate Node.js versions used internally by actions.

The workflow should keep official actions reasonably up to date, for example:

```text
actions/checkout
shivammathur/setup-php
```

When GitHub emits deprecation warnings, the action version should be reviewed.

The maintained workflows currently use the Node.js 24 action generations:

```text
actions/setup-node@v7
actions/upload-artifact@v7
actions/download-artifact@v8
```

The configured test runtime remains Node.js 22; this is independent from the
runtime embedded in the GitHub Actions themselves.

## Local parity

The local `make qa` command should run the same quality gates as CI.

Expected:

```bash
make qa
```

The Composer script behind it should remain the source of truth where practical:

```bash
composer qa
```

## Fresh Symfony application smoke test

CI also creates a standalone Symfony 8 application and installs the current bundle through a Composer path repository:

```bash
tools/smoke-test/fresh-symfony-app.sh
```

The test verifies:

- manual bundle registration and route import;
- the StimulusBundle recipe entrypoint;
- the documented lazy controller configuration;
- Bootstrap and Bootstrap Icons import-map entries;
- bundle asset discovery and production asset compilation;
- datatable service autoconfiguration;
- Twig shell rendering;
- default CSS icon rendering without Symfony UX Icons;
- real server-side SVG rendering in a second host with Symfony UX Icons;
- a real fragments request using the array provider.

This job deliberately installs the bundle as a copied Composer path dependency. It therefore exercises the package as a host application receives it instead of relying on the bundle test kernel.

The optional adapter variant is equivalent to:

```bash
SMOKE_UX_ICONS=1 tools/smoke-test/fresh-symfony-app.sh
```

## Frontend tests

The CI matrix runs frontend tests in addition to PHP quality gates.

Frontend tests validate the vanilla Stimulus datatable controller.

Expected CI steps:

```yaml
- name: Setup Node.js
  uses: actions/setup-node@v7
  with:
    node-version: '22'
    cache: 'npm'

- name: Install frontend dependencies
  run: npm ci

- name: Run frontend tests
  run: npm run test:frontend
```

The committed `package-lock.json` is required for reproducible frontend dependency installation.

Local equivalent:

```bash
make frontenddeps
make frontendtest
```

The frontend test strategy is documented in [`frontend-tests.md`](frontend-tests.md).

## What should not be in CI permanently

Avoid keeping temporary debug steps such as:

```bash
composer show ...
vendor/bin/phpunit --debug --display-errors
```

These are useful while diagnosing issues but should be removed once the failure is fixed.

## Pull request requirements

Before merging:

- all CI jobs must be green;
- no debug CI output should remain;
- local `make qa` should pass;
- dependency updates should be intentional;
- docs should be updated when behavior changes.

## Future improvements

Potential future CI additions:

- PHP 8.5 matrix when stable;
- mutation testing;
- Rector dry-run;
- Composer outdated audit on schedule;
- frontend tests for Stimulus controller;
- markdown link checker;
- package smoke test in a minimal Symfony app.
