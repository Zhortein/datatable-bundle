# Release checklist

This is the go/no-go checklist for every stable or prerelease version of
`zhortein/datatable-bundle`. Replace `<version>` with the version without the
`v` prefix and `<date>` with the release date.

## 1. Scope and compatibility

- [ ] Every issue and pull request selected for the release is resolved.
- [ ] The release follows Semantic Versioning.
- [ ] Public 1.x contracts remain backward compatible, or an approved
  deprecation path is documented.
- [ ] Security, authorization, CSRF and tenant-scope implications were reviewed.
- [ ] Doctrine ORM 3 and 4 compatibility is preserved.
- [ ] JavaScript remains vanilla, with Stimulus used only as the Symfony UX
  integration layer.

## 2. Tests and quality

- [ ] PHPUnit covers each non-trivial behavior and regression.
- [ ] Frontend Vitest covers changed controller behavior.
- [ ] `composer validate --strict` passes.
- [ ] PHPStan passes at the configured maximum level.
- [ ] PHP-CS-Fixer passes with the Symfony-oriented rules.
- [ ] twigcs passes when Twig templates are affected.
- [ ] The PHP 8.4/8.5 lowest/highest dependency matrix passes.
- [ ] Fresh Symfony 8 host smoke tests pass.

## 3. Documentation

- [ ] `README.md` still presents the current stable feature set.
- [ ] The [installation guide](installation.md) and
  [quick start](quick-start.md) remain replayable.
- [ ] Every changed feature is covered by its reference guide and a practical
  example when configuration or extension code is involved.
- [ ] The [documentation index](index.md), [public API policy](public-api.md)
  and [roadmap](roadmap.md) remain current.
- [ ] Active local documentation links pass the frontend documentation test.

## 4. Release metadata

- [ ] All intended files under `changelog/unreleased/` contain concise,
  user-facing entries with a supported prefix.
- [ ] `composer changelog` builds the expected `Unreleased` section.
- [ ] The release preparation moves those entries to
  `## [<version>] - <date>`.
- [ ] Every consumed fragment is removed.
- [ ] `assets/package.json` contains `<version>`.
- [ ] The release preparation commit is
  `chore(release): prepare v<version>`.

## 5. Pull-request flow

- [ ] The feature or fix pull request targets `develop` and is fully green.
- [ ] The release preparation pull request
  `release/<version> -> develop` is fully green.
- [ ] The promotion pull request `develop -> main` contains only the intended
  release history.
- [ ] `Validate release candidate` passes on the promotion pull request.
- [ ] The complete QA matrix and fresh-host smokes pass again.
- [ ] The exact validated head commit is merged without accepting a moved branch.

## 6. Tag and publication

- [ ] `main` is green after promotion.
- [ ] Annotated tag `v<version>` points to the promoted `main` commit.
- [ ] The tag has never been moved or recreated after publication.
- [ ] `Validate release integrity` passes.
- [ ] The tag QA matrix passes.
- [ ] GitHub creates the Release from the matching changelog section.
- [ ] Packagist exposes `v<version>` on the exact tag commit.
- [ ] `composer require zhortein/datatable-bundle:^<major>.<minor>` succeeds in
  a clean compatible Symfony application.

## 7. Repository housekeeping

- [ ] Merged feature, fix and release branches are deleted.
- [ ] Closed automation branches with superseding changes are deleted.
- [ ] Only deliberate long-lived branches remain.
- [ ] Open issues and superseded pull requests have a closing explanation.

## Failure policy

A failure before tagging returns to a dedicated feature, fix or release branch.
After publication, never move a stable tag that Packagist may have observed.
Publish a new corrective patch version instead.

Use the [release workflow](release.md) for the exact branch, promotion, tag and
failure-handling sequence.
