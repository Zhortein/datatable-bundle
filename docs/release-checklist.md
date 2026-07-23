# First pre-release checklist

This checklist must be reviewed before creating the first public pre-release of `zhortein/datatable-bundle`.

Recommended first pre-release direction:

```text
v0.1.0-alpha.1
```

The package must not be presented as stable before the public API and integration story are proven in real projects.

## 1. Branch and repository state

Before tagging:

- [ ] `develop` is green.
- [ ] `main` is green.
- [ ] `develop` has been merged into `main`.
- [ ] No temporary debug CI step remains.
- [ ] No local-only generated files are committed.
- [ ] No private/client-specific references exist in docs or examples.
- [ ] GitHub repository topics are accurate.
- [ ] `main` branch protection is active.
- [ ] GitHub security settings are enabled where available.

## 2. CI requirements

Required checks:

- [ ] Composer validation passes.
- [ ] PHPUnit passes.
- [ ] PHPStan max level passes.
- [ ] PHP-CS-Fixer dry-run passes.
- [ ] twigcs passes.
- [ ] Highest dependency job passes.
- [ ] Lowest dependency job passes.
- [ ] CI uses PHP 8.4.
- [ ] Required PHP extensions are present in CI:
  - [ ] mbstring;
  - [ ] intl;
  - [ ] pdo_sqlite;
  - [ ] dom;
  - [ ] xml;
  - [ ] zip.

Local command:

```bash
make qa
```

CI documentation:

- [CI matrix and dependency strategy](ci.md)

## 3. Composer package metadata

Review `composer.json`.

- [ ] Package name is `zhortein/datatable-bundle`.
- [ ] Package type is `symfony-bundle`.
- [ ] License is MIT.
- [ ] PHP requirement is `>=8.4`.
- [ ] Symfony requirement is Symfony 8+.
- [ ] Runtime dependencies are justified.
- [ ] Development dependencies are justified.
- [ ] Doctrine is optional from a feature perspective and documented correctly.
- [ ] `suggest` section is accurate.
- [ ] `support` section points to GitHub issues/source/docs.
- [ ] Composer scripts are current.
- [ ] `composer validate --strict` passes.

Packagist checklist:

- [Packagist readiness](archive/milestones/packagist.md)

## 4. Documentation requirements

Entry points:

- [ ] `README.md` is current.
- [ ] `docs/index.md` is current.
- [ ] `docs/installation.md` is current.
- [ ] `docs/configuration.md` is current.
- [ ] `docs/quick-start.md` is current.
- [ ] `docs/roadmap.md` is current.

Feature documentation:

- [ ] Doctrine provider documentation is current.
- [ ] Filters documentation is current.
- [ ] Actions and Security documentation is current.
- [ ] Exports documentation is current.
- [ ] Preferences documentation is current.
- [ ] UI/UX and Controls documentation is current.
- [ ] Theming and Templates documentation is current.

Maintenance documentation:

- [ ] Changelog strategy is documented.
- [ ] Release workflow is documented.
- [ ] Packagist readiness is documented.
- [ ] Public API review is documented.
- [ ] Documentation review checklist is documented.

Documentation checklist:

- [Documentation review checklist](documentation-review.md)

## 5. Changelog requirements

Before tagging:

- [ ] `CHANGELOG.md` contains the release version section.
- [ ] `Unreleased` section is reviewed.
- [ ] Relevant entries are moved to the release section.
- [ ] Release date is added.
- [ ] Changelog fragments are either consumed or intentionally kept.
- [ ] `composer changelog` behavior is understood.

Expected version heading format:

```md
## [0.1.0-alpha.1] - YYYY-MM-DD
```

Changelog documentation:

- [Changelog strategy](changelog.md)

## 6. Release workflow requirements

Before pushing a tag:

- [ ] `.github/workflows/release.yaml` exists.
- [ ] Workflow triggers only on tags.
- [ ] Workflow validates tag format.
- [ ] Workflow creates GitHub Release.
- [ ] Workflow does not publish to Packagist automatically.
- [ ] Release notes extraction works.

Test release notes extraction locally:

```bash
php tools/changelog/extract-release-notes.php v0.1.0-alpha.1
```

Release documentation:

- [Release workflow](release.md)

## 7. Examples smoke review

Examples:

- [ ] Minimal array datatable example is current.
- [ ] Doctrine datatable example is current.
- [ ] Examples use current namespaces.
- [ ] Examples do not include private/client-specific names.
- [ ] Examples do not use unsupported features.

Example docs:

- [Minimal array datatable example](quick-start.md)
- [Doctrine datatable example](examples/doctrine-datatable.md)

## 8. Fresh Symfony application smoke test

Before publishing, test installation in a fresh Symfony 8 app.

### Create test app

```bash
symfony new datatable-bundle-smoke --webapp
cd datatable-bundle-smoke
```

### Add local path repository

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../datatable-bundle",
      "options": {
        "symlink": true
      }
    }
  ]
}
```

### Require bundle

```bash
composer require zhortein/datatable-bundle:*
```

### Manual integration checks

- [ ] Bundle is registered.
- [ ] Routes are imported.
- [ ] Translations load.
- [ ] Stimulus controller is exposed.
- [ ] Minimal array datatable renders.
- [ ] Ajax fragments endpoint responds.
- [ ] Search refresh works.
- [ ] Page size selector works.
- [ ] Column visibility controls render.
- [ ] CSV export works.
- [ ] Doctrine datatable renders if Doctrine is installed.
- [ ] Non-GET action form renders with CSRF token if CSRF is configured.

## 9. Public API review

Before tagging:

- [x] `docs/public-api.md` defines the supported 1.x surface.
- [x] Critical API concerns are resolved or explicitly reserved for additive contracts.
- [x] The implementation boundary is documented.
- [x] Autoloadable implementation classes are not presented as supported extension points.

API review:

- [Public API and compatibility policy](public-api.md)
- [Historical prerelease review](archive/milestones/public-api-review.md)

## 10. Known limitations to mention

The first pre-release should clearly state limitations:

- [ ] API is not stable.
- [ ] Bootstrap is the only maintained theme.
- [ ] No Flex recipe yet.
- [ ] Stimulus controller import is manual.
- [ ] Doctrine provider supports explicit joins but not deep traversal.
- [ ] No ManyToMany/collection aggregation.
- [ ] CSV and XLSX exports only.
- [ ] No async export.
- [ ] No built-in preference persistence.
- [x] Frontend test suite implemented.
- [ ] No built-in action controllers.
- [ ] No built-in voters/security rules.

## 11. Tagging checklist

When ready:

```bash
git checkout main
git pull
git merge --ff-only develop
```

Update changelog:

```bash
# edit CHANGELOG.md
git add CHANGELOG.md
git commit -m "docs: prepare release v0.1.0-alpha.1"
```

Create and push tag:

```bash
git tag v0.1.0-alpha.1
git push origin main
git push origin v0.1.0-alpha.1
```

Then verify:

- [ ] GitHub Release was created.
- [ ] Release notes are correct.
- [ ] Tag points to expected commit.
- [ ] CI is green for the tag if applicable.

## 12. After release

After the GitHub release:

- [ ] Review GitHub release page.
- [ ] Decide whether to submit to Packagist.
- [ ] If submitted, verify package metadata.
- [ ] Test `composer require zhortein/datatable-bundle`.
- [ ] Create follow-up issues for any smoke-test findings.
- [ ] Update roadmap if needed.

## 13. Go / no-go decision

A pre-release can be tagged only if:

- [ ] CI is green.
- [ ] Documentation is coherent.
- [ ] Composer metadata is valid.
- [ ] Changelog is ready.
- [ ] Release workflow is ready.
- [ ] Smoke test has no blocking issue.
- [ ] Known limitations are explicit.

If any item is not true, postpone the tag.

## 14. Smoke test report

When running the fresh Symfony smoke test, copy:

```text
docs/archive/smoke-reports/smoke-test-report-template.md
```

to:

```text
docs/archive/smoke-reports/YYYY-MM-DD-local-path-repository.md
```

The report should record:

- environment metadata;
- installation result;
- array datatable checks;
- Doctrine datatable checks;
- actions/security checks;
- export checks;
- blockers;
- documentation gaps;
- go/no-go recommendation.

## First alpha go/no-go

The go/no-go decision for the first alpha is documented in:

- [Go/no-go review for first alpha](archive/milestones/go-no-go-first-alpha.md)
