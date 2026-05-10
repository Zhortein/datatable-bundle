# Changelog strategy

This project keeps a human-readable `CHANGELOG.md`.

The changelog is prepared for automation through small unreleased fragments.

## Goals

The changelog strategy should be:

- simple;
- dependency-free;
- friendly to pull requests;
- easy to review;
- compatible with future GitHub release workflows;
- usable before the first public release.

## File structure

Unreleased fragments live in:

```text
changelog/unreleased/
```

Each fragment is a Markdown file.

Recommended filename format:

```text
<type>-<short-description>.md
```

Examples:

```text
added-doctrine-provider.md
fixed-export-filename.md
changed-template-context.md
```

## Supported fragment types

Supported types:

```text
added
changed
deprecated
removed
fixed
security
```

They map to standard changelog sections:

```text
### Added
### Changed
### Deprecated
### Removed
### Fixed
### Security
```

## Fragment format

A fragment should contain a Markdown bullet list.

Example:

```md
- Added CSV export writer.
```

Another example:

```md
- Fixed Doctrine provider sorting on joined fields.
```

## Build command

Run:

```bash
php tools/changelog/build-unreleased.php
```

The script reads fragments from:

```text
changelog/unreleased/
```

and rebuilds the `## [Unreleased]` section in `CHANGELOG.md`.

## Composer script

The project exposes:

```bash
composer changelog
```

or:

```bash
make composer ARGS="changelog"
```

depending on the local workflow.

## Pull request workflow

When a pull request changes user-visible behavior, add a fragment.

Recommended mapping:

| Change | Fragment type |
|---|---|
| New feature | `added` |
| Behavior change | `changed` |
| Bug fix | `fixed` |
| Security improvement | `security` |
| Removed behavior | `removed` |
| Deprecation | `deprecated` |

Examples:

```text
changelog/unreleased/added-row-actions.md
changelog/unreleased/fixed-csv-escaping.md
changelog/unreleased/changed-bootstrap-defaults.md
```

## When no fragment is needed

A fragment is usually not needed for:

- internal refactoring with no user-visible effect;
- typo-only documentation fixes;
- CI-only changes;
- test-only changes;
- issue creation scripts.

## Release workflow

Before tagging a release:

1. Ensure all fragments are merged.
2. Run `composer changelog`.
3. Review the generated `CHANGELOG.md`.
4. Move entries from `## [Unreleased]` to a versioned section.
5. Commit the changelog update.
6. Tag the release.

Example future version section:

```md
## [0.1.0] - 2026-05-09
```

## Current limitation

The current script rebuilds the `Unreleased` section only.

It does not yet:

- create versioned release sections;
- delete fragments after release;
- generate GitHub releases;
- publish to Packagist.

Those tasks belong to the release workflow milestone.

## GitHub release workflow

GitHub Releases are created from tags by `.github/workflows/release.yaml`.

The workflow extracts release notes from `CHANGELOG.md`.

Before creating a tag, move relevant entries from `Unreleased` to a versioned section.

Example:

```md
## [0.1.0] - 2026-05-09
```

More details are available in [`release.md`](release.md).
