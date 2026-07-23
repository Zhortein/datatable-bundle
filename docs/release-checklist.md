# Stable 1.0.0 release checklist

This checklist is the go/no-go reference for promoting `zhortein/datatable-bundle` from `develop` to `main` and publishing `v1.0.0`.

## 1. Release content

- [x] Real-project feedback items selected for 1.0 are resolved.
- [x] Boolean headers/cells and dropdown carets are aligned.
- [x] Empty toolbar slots keep their DOM targets and remain visually hidden.
- [x] Boolean columns support `negate`.
- [x] Hidden-column export behavior is controlled by `exportable`.
- [x] Provider selection honors both `#[AsDatatable(provider: ...)]` and `default_provider`.
- [x] Action permissions use dedicated metadata.
- [x] The bundled Stimulus controller is lazy by default.
- [x] Unused prerelease API has been removed.

## 2. Documentation and public API

- [x] `README.md` presents the stable 1.x status.
- [x] The [documentation index](index.md) is current.
- [x] The [installation guide](installation.md) is replayable.
- [x] The [quick start](quick-start.md) is complete.
- [x] Provider, filter, action, bulk-action and export guides match the implementation.
- [x] Bootstrap Icons and lazy Stimulus loading are documented.
- [x] Exact route diagnostic commands are documented.
- [x] The [public API policy](public-api.md) defines the 1.x compatibility surface.
- [x] Internal implementation services are excluded from the compatibility promise.
- [x] Active local documentation links are tested.

## 3. Package and release metadata

- [x] `composer.json` identifies a Symfony bundle requiring PHP 8.4+ and Symfony 8.
- [x] Optional Doctrine, XLSX, AssetMapper and Stimulus dependencies are documented through `suggest`.
- [x] `assets/package.json` contains version `1.0.0`.
- [x] `CHANGELOG.md` contains a dated, non-empty `1.0.0` section.
- [x] `CHANGELOG.md` keeps an `Unreleased` section.
- [x] All release fragments have been consumed.
- [x] The roadmap records the stable milestone.

## 4. Automated quality gates

The CI workflow must pass on the release branch and again on the promotion pull request:

- [x] Composer strict validation.
- [x] PHPUnit.
- [x] Frontend Vitest suite.
- [x] PHPStan.
- [x] PHP-CS-Fixer dry run.
- [x] twigcs.
- [x] PHP 8.4 with lowest dependencies.
- [x] PHP 8.4 with highest dependencies.
- [x] PHP 8.5 with lowest dependencies.
- [x] PHP 8.5 with highest dependencies.
- [x] Fresh Symfony 8 application smoke test.

The promotion pull request to `main` additionally verifies:

- [ ] no changelog fragments remain;
- [ ] `assets/package.json` contains a valid semantic version;
- [ ] release notes can be extracted for `v1.0.0`.

## 5. Fresh application contract

The automated smoke application verifies:

- [x] Composer installation through a clean path repository.
- [x] Bundle registration.
- [x] Fragments and export routes.
- [x] Datatable service autoconfiguration and provider selection.
- [x] Current StimulusBundle bootstrap file.
- [x] Lazy bundle controller metadata.
- [x] Bootstrap and Bootstrap Icons import-map installation.
- [x] AssetMapper discovery and production compilation.
- [x] Rendering through Symfony's HTTP runtime.
- [x] A real fragments response from the array provider.

Browser-level interaction automation is planned after 1.0. The current frontend unit tests and feedback from a real host application cover the release fixes without claiming a full browser E2E suite.

## 6. Known 1.0 limitations

- [x] Bootstrap 5 is the maintained theme.
- [x] Bundle routes and Stimulus controller activation remain manual because there is no dedicated Flex recipe.
- [x] Doctrine collection-valued association aggregation is out of scope.
- [x] Exports are synchronous; async and streaming export contracts are future work.
- [x] XLSX requires the optional OpenSpout dependency.
- [x] Preference persistence is supplied by host applications through an interface.
- [x] Applications remain responsible for action controllers and backend authorization.
- [x] Browser-level E2E and accessibility automation remain roadmap items.

## 7. Promotion to main

Before merging `develop` into `main`:

- [ ] The release preparation pull request is merged into `develop`.
- [ ] The latest `develop` commit is green.
- [ ] The `develop` to `main` pull request contains only the intended release history.
- [ ] `Validate release candidate` passes.
- [ ] The complete QA matrix and fresh-application smoke test pass.
- [ ] Branch protection and required checks allow only a reviewed green merge.

## 8. Tag and publication

After `main` is green:

- [ ] Create annotated tag `v1.0.0` on the promoted `main` commit.
- [ ] Push the tag without moving or recreating an existing published tag.
- [ ] `Validate release integrity` passes.
- [ ] The tag QA matrix passes.
- [ ] The GitHub Release is created from the `1.0.0` changelog section.
- [ ] Packagist exposes `1.0.0`.
- [ ] `composer require zhortein/datatable-bundle:^1.0` succeeds in a clean Symfony application.

Use the [release workflow](release.md) for commands and failure policy.

## Go/no-go

`v1.0.0` is a go only when sections 1 through 6 remain satisfied and every pending item in sections 7 and 8 is completed in order. A failure before tagging returns to a release/fix branch. A failure observed after publication is handled by a corrective patch release; a published stable tag is never moved.
