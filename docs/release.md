# Release workflow

This document describes how a tested commit becomes a tagged GitHub and Packagist release.

## Branch flow

Normal changes follow:

```text
feature/* or fix/* -> develop
```

A release is prepared on a dedicated branch and reviewed before promotion:

```text
release/<version> -> develop -> main -> v<version>
```

Release-specific changes must reach `develop` before the promotion pull request to `main`. This keeps both long-lived branches consistent.

## Dependabot branch synchronization

Ordinary Composer, npm and GitHub Actions version updates are configured in
`.github/dependabot.yml` to target `develop`.

Dependabot security updates are different: GitHub always opens them against the
default branch, which is `main` for this repository. After merging one of these
security pull requests, synchronize it back into `develop` before preparing
another release:

```bash
git fetch origin
git switch -c chore/sync-main-after-dependabot origin/develop
git merge --no-ff origin/main
# Resolve conflicts if needed, then run the complete QA suite.
git push -u origin chore/sync-main-after-dependabot
```

Open a pull request from `chore/sync-main-after-dependabot` to `develop` and
merge it only after CI is green. Do not bypass this synchronization by promoting
a divergent `develop` directly to `main`.

## Version format

Tags must use:

```text
vMAJOR.MINOR.PATCH
```

Examples:

```text
v1.0.0
v1.1.0
v2.0.0
```

Prerelease suffixes are supported:

```text
v1.0.0-rc.1
v1.1.0-beta.1
```

`assets/package.json` contains the same version without the `v` prefix.

## Preparing a release

Create a branch from the latest green `develop`:

```bash
git switch develop
git pull --ff-only
git switch -c release/<version>
```

Then:

1. run `composer changelog`;
2. review the generated `Unreleased` entries;
3. move those entries to `## [<version>] - YYYY-MM-DD`;
4. leave an empty `## [Unreleased]` section;
5. remove every consumed file from `changelog/unreleased/`;
6. set `assets/package.json` to `<version>`;
7. update README, documentation status and roadmap;
8. run the full QA chain and the fresh-application smoke test;
9. commit with `chore(release): prepare v<version>`;
10. open and merge a pull request into `develop`.

After that merge, open the promotion pull request from `develop` to `main`.

## Promotion validation

Every pull request targeting `main` runs the `Validate release candidate` CI job. It rejects the promotion when:

- `assets/package.json` does not contain a valid semantic version;
- changelog fragments remain unconsumed;
- `CHANGELOG.md` has no non-empty section matching that version.

The complete PHP 8.4/8.5, lowest/highest dependency matrix also runs on the pull request.

Do not merge the promotion pull request while any required check is missing, skipped unexpectedly or failing.

## Creating the tag

After the promotion pull request is merged and `main` is green:

```bash
git switch main
git pull --ff-only
git tag -a v<version> -m "Release v<version>"
git push origin v<version>
```

Never create a release tag from `develop` or a feature branch.

## Tag workflow

Pushing a `v*` tag starts `.github/workflows/release.yaml`.

The workflow:

1. validates the tag format;
2. verifies that the tagged commit belongs to `main`;
3. verifies that the tag matches `assets/package.json`;
4. rejects unconsumed changelog fragments;
5. requires a non-empty matching changelog section;
6. reruns the full PHP and frontend QA matrix on the tagged source;
7. creates the GitHub Release only after every matrix job succeeds;
8. marks suffix versions such as `-beta.1` or `-rc.1` as prereleases.

The workflow never falls back to `Unreleased` and never fabricates release notes.

## GitHub Release and Packagist

The workflow creates the GitHub Release from the matching `CHANGELOG.md` section.

The package is already registered on [Packagist](https://packagist.org/packages/zhortein/datatable-bundle) and is automatically synchronized with repository tags. The GitHub workflow does not upload an archive or call Packagist directly.

After publication, verify:

- the GitHub Release title and notes;
- the tag commit;
- the stable version on Packagist;
- `composer require zhortein/datatable-bundle` in a clean application.

## What remains manual

The workflow does not:

- choose the release version;
- update the changelog or package metadata;
- merge branches;
- create the tag;
- publish signed build artifacts.

These steps stay explicit so the stable release remains a deliberate decision.

## Failure policy

If validation fails before the tag exists, fix the release branch and repeat the pull-request flow.

If a pushed tag fails before a GitHub Release is created, investigate before deleting or moving it: Packagist may already have observed that tag. Never move a published stable tag. Prefer a corrective patch release when consumers may have received it.

Use the [release checklist](release-checklist.md) for the final go/no-go review.
