# Release workflow

This document describes the GitHub release workflow.

The project does not publish automatically to Packagist yet.

The release workflow only creates a GitHub Release from a Git tag.

## Tag format

Release tags must use this format:

```text
vMAJOR.MINOR.PATCH
```

Examples:

```text
v0.1.0
v1.0.0
```

Pre-release tags are allowed:

```text
v0.1.0-alpha.1
v0.1.0-beta.1
v1.0.0-rc.1
```

## Workflow trigger

The workflow runs only when pushing a tag matching:

```yaml
v*
```

The workflow validates the tag format before creating a release.

## What the workflow does

The workflow:

1. checks out the repository;
2. validates the tag format;
3. extracts release notes from `CHANGELOG.md`;
4. creates a GitHub Release with the extracted notes.

## Release notes source

The workflow uses:

```bash
php tools/changelog/extract-release-notes.php "${GITHUB_REF_NAME}"
```

The script looks for a matching version section in `CHANGELOG.md`.

For a tag:

```text
v0.1.0
```

it looks for:

```md
## [0.1.0]
```

or:

```md
## [v0.1.0]
```

If no matching section exists, it falls back to the `Unreleased` section when it contains collected entries.

If no suitable notes are found, it generates a minimal fallback note.

## Recommended release process

Before creating a tag:

1. Ensure `develop` is green.
2. Merge `develop` into `main`.
3. Update `CHANGELOG.md`.
4. Move relevant entries from `Unreleased` to a version section.
5. Commit the changelog update.
6. Push `main`.
7. Create and push a tag.

Example:

```bash
git checkout main
git pull
git merge --ff-only develop

# update CHANGELOG.md
git add CHANGELOG.md
git commit -m "docs: prepare release v0.1.0"

git tag v0.1.0
git push origin main
git push origin v0.1.0
```

## Permissions

The workflow uses:

```yaml
permissions:
  contents: write
```

This is required to create the GitHub Release.

## What the workflow does not do

The workflow does not:

- publish to Packagist;
- create or update Composer packages;
- update the changelog automatically;
- create a release tag automatically;
- sign artifacts;
- upload build artifacts.

## Packagist

Packagist publication remains manual for now.

Future work may include:

- Packagist setup documentation;
- GitHub release to Packagist webhook documentation;
- first pre-release checklist;
- Symfony Flex recipe decision.
