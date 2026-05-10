#!/usr/bin/env bash

set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI is required. Install it first: https://cli.github.com/"
  exit 1
fi

gh auth status >/dev/null

MILESTONE_TITLE="0.14 - Developer experience and release hardening"

ensure_milestone() {
  if gh api repos/:owner/:repo/milestones --jq ".[] | select(.title == \"${MILESTONE_TITLE}\") | .title" | grep -q "${MILESTONE_TITLE}"; then
    echo "Milestone already exists: ${MILESTONE_TITLE}"
  else
    echo "Creating milestone: ${MILESTONE_TITLE}"
    gh api repos/:owner/:repo/milestones \
      -f title="${MILESTONE_TITLE}" \
      -f description="Developer experience, examples, CI matrix, changelog automation, release workflow and first pre-release readiness."
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

Create a small public example showing a minimal array-backed datatable.

## Context

The bundle has many features, but new users need a simple starting point that works without Doctrine.

## Scope

- Add an `examples/array` or `docs/examples/array-datatable.md`.
- Show a minimal datatable class using `ArrayDataProvider`.
- Show Twig rendering.
- Show expected behavior.
- Link from README/docs index.

## Out of scope

- Full Symfony demo application.
- Docker demo environment.
- Doctrine example.

## Constraints

- Example must be generic.
- No private/client-specific names.
- Documentation must match current API.
- QA passes.

## Acceptance criteria

- [ ] Array datatable example exists.
- [ ] README or docs index links to it.
- [ ] Example uses current API.
- [ ] QA passes.
BODY
)"
create_issue "Add minimal array datatable example" "type: docs,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Create a public Doctrine-backed datatable example.

## Context

Doctrine is the first production-oriented provider. Users need a realistic example with entity fields, joins, filters, actions and rendering.

## Scope

- Add `docs/examples/doctrine-datatable.md`.
- Show an entity-backed datatable.
- Include columns, permanent filters, user filters and actions.
- Include a joined entity column if useful.
- Include Twig rendering.
- Link from README/docs index.

## Out of scope

- Full runnable Symfony demo app.
- Database migrations.
- Fixtures.

## Constraints

- Example must be generic.
- Avoid private/client-specific context.
- Documentation must match implemented behavior.
- QA passes.

## Acceptance criteria

- [ ] Doctrine datatable example exists.
- [ ] Example uses current API.
- [ ] README or docs index links to it.
- [ ] QA passes.
BODY
)"
create_issue "Add Doctrine datatable example" "type: docs,area: doctrine,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Review the CI matrix and dependency strategy before the first pre-release.

## Context

The bundle currently runs highest/lowest dependencies and PHP 8.4. The matrix should be explicit, useful and not overly slow.

## Scope

- Review GitHub Actions matrix.
- Ensure highest and lowest dependency jobs are stable.
- Ensure PHP extensions are explicit.
- Review PHPUnit/PHPStan/CS Fixer/twigcs commands.
- Decide whether to add PHP 8.5 if available/stable.
- Document CI strategy.

## Out of scope

- Release workflow.
- Changelog automation.
- Mutation testing.

## Constraints

- Keep CI runtime reasonable.
- Do not weaken quality gates.
- Symfony 8+ target remains.
- QA passes.

## Acceptance criteria

- [ ] CI matrix is reviewed and documented.
- [ ] CI remains green.
- [ ] No unnecessary debug output remains.
- [ ] QA passes.
BODY
)"
create_issue "Review CI matrix and dependency strategy" "type: ci,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Prepare automated changelog management.

## Context

The project requires a CHANGELOG and should eventually update it automatically.

## Scope

- Decide changelog automation strategy.
- Add release/changelog tooling if appropriate.
- Document how changelog entries are produced.
- Ensure `CHANGELOG.md` is accurate for current development state.
- Add CI/release notes if needed.

## Out of scope

- Publishing a release.
- Packagist configuration.
- Semantic version tag creation.

## Constraints

- Keep tooling lightweight.
- Avoid forcing a complex release process too early.
- QA passes.

## Acceptance criteria

- [ ] Changelog strategy is documented.
- [ ] CHANGELOG.md is updated.
- [ ] Automation tooling exists or is explicitly postponed with rationale.
- [ ] QA passes.
BODY
)"
create_issue "Prepare changelog automation strategy" "type: ci,type: docs,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Prepare a GitHub release workflow for future tags.

## Context

The bundle will eventually need tagged releases and downloadable artifacts/notes.

## Scope

- Add or document a GitHub release workflow.
- Ensure workflow triggers on tags only.
- Generate release notes from changelog or GitHub.
- Avoid accidental release on regular pushes.
- Document release process.

## Out of scope

- Creating the first release tag.
- Packagist publication.
- Signing artifacts.

## Constraints

- Do not publish automatically before manual tag.
- Keep workflow simple and safe.
- QA passes.

## Acceptance criteria

- [ ] Release workflow or documented release process exists.
- [ ] Workflow is tag-based.
- [ ] Release process is documented.
- [ ] QA passes.
BODY
)"
create_issue "Prepare GitHub release workflow" "type: ci,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Review repository metadata and Packagist readiness.

## Context

Before a first pre-release, the repository and Composer metadata should be coherent.

## Scope

- Review composer name, type, license, description, keywords.
- Review `support` metadata.
- Review README badges if any.
- Review GitHub topics.
- Verify package can be discovered by Packagist later.
- Document Packagist publication checklist.

## Out of scope

- Actual Packagist publication.
- Release tag creation.
- Flex recipe.

## Constraints

- Keep metadata accurate.
- Avoid claiming unsupported features.
- QA passes.

## Acceptance criteria

- [ ] Composer metadata is reviewed.
- [ ] Repository metadata checklist exists.
- [ ] Packagist readiness checklist exists.
- [ ] QA passes.
BODY
)"
create_issue "Review Packagist readiness" "type: docs,type: ci,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Review all documentation entry points before the first pre-release.

## Context

The docs have grown across many milestones. Entry points should be easy to navigate.

## Scope

- Review README documentation links.
- Review docs/index.md.
- Ensure docs are not duplicated confusingly.
- Ensure roadmap matches current state.
- Ensure current limitations are clear.
- Fix broken links if any.

## Out of scope

- Rewriting every doc page.
- Changing public API.
- Release workflow.

## Constraints

- Documentation must remain accurate.
- No private/client-specific references.
- QA passes.

## Acceptance criteria

- [ ] README links are coherent.
- [ ] docs/index.md is coherent.
- [ ] Roadmap is current.
- [ ] Broken links are fixed.
- [ ] QA passes.
BODY
)"
create_issue "Review documentation navigation and roadmap" "type: docs,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Run a final public API review before first pre-release.

## Context

The bundle now exposes many public classes and interfaces. We should identify naming/API issues before tagging anything.

## Scope

- Review public namespaces.
- Review interfaces.
- Review value objects.
- Review enums.
- Review service names and tags.
- Review Twig function names.
- Review route names.
- Document any planned breaking changes before pre-release.

## Out of scope

- Large refactor unless clearly necessary.
- Feature additions.
- Release tag.

## Constraints

- Be conservative.
- Avoid churn unless it improves long-term API quality.
- QA passes.

## Acceptance criteria

- [ ] Public API review notes exist.
- [ ] Any needed API cleanup issues are created.
- [ ] No undocumented breaking concerns remain.
- [ ] QA passes.
BODY
)"
create_issue "Review public API before pre-release" "type: architecture,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Prepare a first pre-release readiness checklist.

## Context

After documentation, CI, examples and metadata review, the project should have a clear checklist for tagging a first development release.

## Scope

- Add `docs/release-checklist.md`.
- Include CI requirements.
- Include docs requirements.
- Include changelog requirements.
- Include Packagist checklist.
- Include manual smoke-test checklist.
- Include known limitations.

## Out of scope

- Creating the release.
- Publishing to Packagist.
- Writing Flex recipe.

## Constraints

- Checklist must be realistic.
- No unsupported claims.
- QA passes.

## Acceptance criteria

- [ ] Release checklist exists.
- [ ] Known limitations are listed.
- [ ] Manual smoke tests are listed.
- [ ] QA passes.
BODY
)"
create_issue "Prepare first pre-release checklist" "type: docs,type: ci,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Document milestone 0.14 completion and next roadmap direction.

## Context

At the end of this milestone, roadmap should reflect developer experience and release hardening work.

## Scope

- Update docs/roadmap.md.
- Mark 0.14 as completed.
- Clarify next milestone direction.
- Ensure 1.0 expectations remain realistic.

## Out of scope

- Creating a release.
- Adding new feature work.

## Constraints

- Roadmap must match delivered work.
- QA passes.

## Acceptance criteria

- [ ] Roadmap updated.
- [ ] Next milestone direction is clear.
- [ ] QA passes.
BODY
)"
create_issue "Update roadmap after release hardening milestone" "type: docs,priority: medium,ai-ready" "$body"
rm -f "$body"

echo "Developer experience and release hardening issues created successfully."
