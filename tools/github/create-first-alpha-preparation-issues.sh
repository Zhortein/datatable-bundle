#!/usr/bin/env bash

set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI is required. Install it first: https://cli.github.com/"
  exit 1
fi

gh auth status >/dev/null

MILESTONE_TITLE="0.15 - First alpha preparation"

ensure_milestone() {
  if gh api repos/:owner/:repo/milestones --jq ".[] | select(.title == \"${MILESTONE_TITLE}\") | .title" | grep -q "${MILESTONE_TITLE}"; then
    echo "Milestone already exists: ${MILESTONE_TITLE}"
  else
    echo "Creating milestone: ${MILESTONE_TITLE}"
    gh api repos/:owner/:repo/milestones \
      -f title="${MILESTONE_TITLE}" \
      -f description="Fresh Symfony application smoke test, alpha blockers, changelog preparation and first pre-release readiness."
  fi
}

issue_exists() {
  local title="$1"

  gh issue list \
    --state all \
    --search "$title in:title" \
    --json title \
    --jq ".[].title" \
    | grep -Fxq "$title"
}

create_issue() {
  local title="$1"
  local labels="$2"
  local body_file="$3"

  if issue_exists "$title"; then
    echo "Issue already exists: $title"
    return
  fi

  local label_args=()
  IFS=',' read -ra label_list <<< "$labels"

  for raw_label in "${label_list[@]}"; do
    local label
    label="$(printf "%s" "$raw_label" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')"

    if [ -n "$label" ]; then
      label_args+=(--label "$label")
    fi
  done

  echo "Creating issue: $title"

  gh issue create \
    --title "$title" \
    --body-file "$body_file" \
    --milestone "$MILESTONE_TITLE" \
    "${label_args[@]}"
}

make_body() {
  local tmpfile
  tmpfile="$(mktemp)"
  cat > "$tmpfile"
  echo "$tmpfile"
}

ensure_milestone

body="$(make_body <<'BODY'
## Objective

Create a documented smoke test plan for a fresh Symfony application.

## Context

Before tagging an alpha release, the bundle must be tested outside its own test suite in a clean Symfony application.

## Scope

- Add `docs/smoke-test.md`.
- Define test environment requirements.
- Document path repository setup.
- Document bundle registration.
- Document route import.
- Document Stimulus/AssetMapper setup.
- Document translation checks.
- Document array datatable smoke test.
- Document Doctrine datatable smoke test.
- Document export/action/filter checks.

## Out of scope

- Running the smoke test.
- Fixing smoke test failures.
- Creating a reusable demo app repository.

## Constraints

- Documentation must be executable by a Symfony developer.
- Keep the test app generic.
- No private/client-specific references.
- QA passes.

## Acceptance criteria

- [ ] Smoke test plan exists.
- [ ] Plan covers array and Doctrine datatables.
- [ ] Plan covers frontend integration.
- [ ] Plan covers exports/actions/filters.
- [ ] QA passes.
BODY
)"
create_issue "Document fresh Symfony smoke test plan" "type: docs,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Run the bundle in a fresh Symfony application through a local path repository.

## Context

This validates installation, autoloading, routes, translations and basic rendering outside the bundle repository.

## Scope

- Create a temporary Symfony 8 app locally.
- Add the bundle as a path repository.
- Register the bundle.
- Import routes.
- Configure translations.
- Expose the Stimulus controller manually.
- Record setup steps and issues found.

## Out of scope

- Committing the temporary Symfony app to this repository.
- Creating a permanent demo application.
- Publishing to Packagist.

## Constraints

- Use a clean Symfony application.
- Use local path repository.
- Keep notes generic and reusable.
- Create follow-up issues for blockers.

## Acceptance criteria

- [ ] Bundle installs in a fresh app.
- [ ] Routes can be imported.
- [ ] Twig function is available.
- [ ] Stimulus controller can be exposed.
- [ ] Findings are documented.
BODY
)"
create_issue "Run fresh Symfony path repository smoke test" "type: tests,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Validate the minimal array datatable example in the fresh Symfony smoke application.

## Context

The array provider is the simplest smoke test because it avoids Doctrine and database setup.

## Scope

- Implement the documented array datatable example in the smoke app.
- Render it through `zhortein_datatable()`.
- Verify initial table rendering.
- Verify Ajax fragments.
- Verify search.
- Verify page size selector.
- Verify column visibility controls.
- Verify CSV export.

## Out of scope

- Doctrine setup.
- Persisted preferences.
- Advanced filters.

## Constraints

- Follow `docs/examples/array-datatable.md`.
- Record deviations from documentation.
- Create issues for blockers.

## Acceptance criteria

- [ ] Array datatable renders.
- [ ] Ajax refresh works.
- [ ] Search works.
- [ ] Page size selector works.
- [ ] Column visibility works.
- [ ] CSV export works.
- [ ] Findings are documented.
BODY
)"
create_issue "Smoke test minimal array datatable example" "type: tests,area: twig,area: stimulus,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Validate the Doctrine datatable example in the fresh Symfony smoke application.

## Context

Doctrine is the first production-oriented provider and must be validated in a real app.

## Scope

- Install Doctrine ORM/DoctrineBundle in the smoke app.
- Create sample User and Organization entities.
- Create schema/migration or use SQLite/dev DB.
- Add sample data.
- Implement documented Doctrine datatable.
- Verify joined columns.
- Verify permanent filters.
- Verify user filters.
- Verify sorting.
- Verify CSV export.

## Out of scope

- Complex associations.
- ManyToMany/collections.
- Production database tuning.

## Constraints

- Follow `docs/examples/doctrine-datatable.md`.
- Record deviations from documentation.
- Create issues for blockers.

## Acceptance criteria

- [ ] Doctrine datatable renders.
- [ ] Joined columns render.
- [ ] Filters work.
- [ ] Sorting works.
- [ ] CSV export works.
- [ ] Findings are documented.
BODY
)"
create_issue "Smoke test Doctrine datatable example" "type: tests,area: doctrine,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Validate action rendering, CSRF behavior and action visibility extension points in the smoke application.

## Context

Actions are a key public feature and need to work in a real Symfony app.

## Scope

- Add row actions to smoke datatables.
- Add global actions.
- Verify GET actions as links.
- Verify non-GET actions as forms.
- Verify CSRF token output when CSRF is configured.
- Verify confirmation behavior.
- Test replacing `ActionVisibilityCheckerInterface`.

## Out of scope

- Full security voter implementation.
- Complex authorization matrix.
- Controller-side action business logic beyond smoke checks.

## Constraints

- Use generic sample routes/controllers.
- Do not rely on private project behavior.
- Record blockers as issues.

## Acceptance criteria

- [ ] Row actions render.
- [ ] Global actions render.
- [ ] Non-GET forms include CSRF token when available.
- [ ] Confirmation behavior works.
- [ ] Visibility checker can be replaced.
BODY
)"
create_issue "Smoke test actions and security integration" "type: tests,area: security,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Validate the current documentation against the fresh Symfony smoke test.

## Context

Docs may be accurate inside the bundle but still miss real-app integration details.

## Scope

- Compare smoke test steps with `docs/installation.md`.
- Compare array example with `docs/examples/array-datatable.md`.
- Compare Doctrine example with `docs/examples/doctrine-datatable.md`.
- Compare Stimulus setup with `docs/stimulus-assetmapper.md`.
- Compare route setup with `docs/routes.md`.
- Update docs for any missing steps.

## Out of scope

- New feature implementation.
- Demo application repository.

## Constraints

- Keep docs generic.
- Do not document temporary local paths as permanent rules.
- QA passes.

## Acceptance criteria

- [ ] Installation docs match smoke test.
- [ ] Examples match smoke test.
- [ ] Stimulus docs match smoke test.
- [ ] Routes docs match smoke test.
- [ ] QA passes.
BODY
)"
create_issue "Align documentation with smoke test findings" "type: docs,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Fix any blockers discovered during fresh-app smoke testing.

## Context

This issue acts as a parent/placeholder for smoke-test blockers that must be resolved before a first alpha tag.

## Scope

- Review issues discovered during smoke testing.
- Fix blocking installation/runtime bugs.
- Fix incorrect docs that prevent basic usage.
- Fix missing service wiring required by a real app.
- Keep changes focused.

## Out of scope

- Nice-to-have improvements.
- New major features.
- Non-blocking polish.

## Constraints

- Create separate issues for independent blockers when needed.
- Keep QA green.
- Do not hide limitations by over-documenting broken behavior.

## Acceptance criteria

- [ ] No known smoke-test blocker remains.
- [ ] Follow-up issues exist for non-blocking findings.
- [ ] QA passes.
BODY
)"
create_issue "Resolve alpha-blocking smoke test issues" "type: bug,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Prepare `CHANGELOG.md` for the first alpha release.

## Context

Before tagging `v0.1.0-alpha.1`, the changelog must have a versioned section.

## Scope

- Review current `CHANGELOG.md`.
- Move relevant entries from Unreleased to a version section.
- Add release date.
- Ensure known limitations are not hidden.
- Ensure changelog is readable.
- Test release notes extraction script.

## Out of scope

- Creating the tag.
- Publishing to Packagist.
- GitHub Release creation.

## Constraints

- Version target is likely `v0.1.0-alpha.1`.
- Keep changelog honest.
- QA passes.

## Acceptance criteria

- [ ] `CHANGELOG.md` has a `0.1.0-alpha.1` section.
- [ ] `Unreleased` section remains for future work.
- [ ] Release notes extraction works.
- [ ] QA passes.
BODY
)"
create_issue "Prepare changelog for first alpha" "type: docs,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Make the final go/no-go decision for tagging the first alpha.

## Context

After smoke testing and changelog preparation, the release checklist must be reviewed.

## Scope

- Review `docs/release-checklist.md`.
- Confirm CI state.
- Confirm smoke test state.
- Confirm docs state.
- Confirm changelog state.
- Confirm known limitations.
- Decide whether to tag `v0.1.0-alpha.1`.

## Out of scope

- Fixing blockers directly.
- Publishing to Packagist.

## Constraints

- Do not tag if blocking issues remain.
- Record decision in the issue.
- Create follow-up issues if needed.

## Acceptance criteria

- [ ] Release checklist reviewed.
- [ ] Go/no-go decision recorded.
- [ ] If go: tag plan is clear.
- [ ] If no-go: blockers are listed.
BODY
)"
create_issue "Review go-no-go for first alpha tag" "type: docs,type: ci,priority: high,ai-ready" "$body"
rm -f "$body"

echo "First alpha preparation issues created successfully."
