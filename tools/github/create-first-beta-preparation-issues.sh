#!/usr/bin/env bash

set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI is required. Install it first: https://cli.github.com/"
  exit 1
fi

gh auth status >/dev/null

MILESTONE_TITLE="0.27 - First beta preparation"

ensure_milestone() {
  if gh api repos/:owner/:repo/milestones --jq ".[] | select(.title == \"${MILESTONE_TITLE}\") | .title" | grep -q "${MILESTONE_TITLE}"; then
    echo "Milestone already exists: ${MILESTONE_TITLE}"
  else
    echo "Creating milestone: ${MILESTONE_TITLE}"
    gh api repos/:owner/:repo/milestones \
      -f title="${MILESTONE_TITLE}" \
      -f description="Prepare the first beta release after bulk actions, icon system and advanced filter expressions. Target release: v0.3.0-beta.1."
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

Run a full smoke test before the first beta release.

## Context

The bundle now includes several production-oriented features:

- Bootstrap/Twig datatable rendering;
- Stimulus Ajax refresh;
- Array provider;
- Doctrine provider;
- explicit joins;
- chained joins;
- custom joins;
- aggregate columns;
- simple filters;
- header filters;
- advanced filter expressions / search builder;
- row/global actions;
- bulk actions and row selection;
- modal confirmations;
- icon system;
- CSV and XLSX exports;
- frontend tests.

The first beta should only be tagged after validating these features in the fresh Symfony smoke application.

## Scope

Validate at least:

- installation from current `develop`;
- route import;
- Stimulus controller activation;
- Bootstrap CSS/JS integration;
- array provider datatable;
- Doctrine provider datatable;
- row actions;
- global actions;
- bulk actions;
- CSRF-aware non-GET actions;
- Bootstrap modal confirmation;
- inline/dropdown/list row action modes;
- split/default controls layout;
- toolbar filters;
- header filters;
- advanced filter expressions;
- sorting and sort indicators;
- column visibility;
- CSV export;
- XLSX export;
- icon configuration;
- documentation sanity.

## Out of scope

- Full browser automation.
- Performance benchmarking.
- Complete accessibility audit.

## Acceptance criteria

- [ ] Smoke app is updated against current `develop`.
- [ ] Array provider smoke path passes.
- [ ] Doctrine provider smoke path passes.
- [ ] Actions and bulk actions smoke path passes.
- [ ] Simple and advanced filters smoke path passes.
- [ ] CSV and XLSX exports work.
- [ ] Icon system renders as expected.
- [ ] Findings are recorded.
- [ ] Blockers are identified or ruled out.
BODY
)"
create_issue "Run first beta smoke test" "type: tests,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Resolve any blockers discovered during the first beta smoke test.

## Scope

- Review first beta smoke test findings.
- Fix release-blocking issues.
- Create follow-up issues for non-blocking findings.
- Ensure QA and frontend tests remain green.

## Release-blocking examples

- installation fails;
- routes fail;
- Stimulus controller fails;
- basic datatable rendering fails;
- Array provider fails;
- Doctrine provider fails;
- actions or bulk actions fail;
- CSRF behavior is broken;
- advanced filters do not apply;
- exports fail;
- modal confirmation prevents normal use;
- CI is not green.

## Acceptance criteria

- [ ] All first beta blockers are resolved.
- [ ] Non-blocking findings are tracked separately.
- [ ] `make frontendtest` passes.
- [ ] `make qa` passes.
- [ ] CI is green.
BODY
)"
create_issue "Resolve first beta blockers" "type: bug,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Review the public API before publishing the first beta.

## Context

A beta release sends the signal that the main feature set is now mostly in place and should begin stabilizing.

Before tagging `v0.3.0-beta.1`, review the public API surface and identify risks.

## Scope

Review public or semi-public APIs including:

- `#[AsDatatable]`;
- `DatatableInterface`;
- `DatatableDefinition`;
- column definitions;
- filter definitions;
- advanced filter expression APIs;
- action definitions;
- bulk action definitions;
- join/custom join definitions;
- aggregate column definitions;
- export writer interfaces;
- preference provider interfaces;
- action visibility/security interfaces;
- Twig function API;
- runtime options passed to `zhortein_datatable()`;
- Twig template override context;
- Stimulus data attributes that host apps may rely on.

## Tasks

- Update `docs/public-api-review.md`.
- Mark APIs as stable-ish, experimental or internal.
- List API risks before 1.0.
- Identify any immediate API rename/deprecation needed before beta.
- Avoid code changes unless a small naming fix is clearly necessary and safe.

## Acceptance criteria

- [ ] Public API review is updated.
- [ ] Experimental APIs are identified.
- [ ] Internal APIs are identified where possible.
- [ ] 1.0 API risks are documented.
- [ ] Any beta-blocking API issue is tracked.
- [ ] QA passes.
BODY
)"
create_issue "Review public API before first beta" "type: architecture,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Prepare `CHANGELOG.md` for the first beta release.

## Target version

```text
v0.3.0-beta.1
```

## Scope

- Review the `Unreleased` section.
- Ensure all changes since `v0.2.0-alpha.1` are listed.
- Include:
  - bulk actions and row selection;
  - icon system and visual consistency;
  - advanced filter expressions / search builder;
  - smoke-test fixes;
  - docs updates;
  - any dependency/CI changes.
- Ensure no unimplemented feature is listed as implemented.
- Prepare or verify release notes extraction.

## Acceptance criteria

- [ ] `CHANGELOG.md` is accurate.
- [ ] Target version is confirmed.
- [ ] Release notes can be extracted.
- [ ] QA passes.
BODY
)"
create_issue "Prepare changelog for first beta" "type: docs,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Record the go/no-go decision for the first beta tag.

## Scope

Review:

- smoke test status;
- blocker status;
- public API review;
- changelog status;
- Composer/Packagist metadata;
- CI status;
- QA status;
- frontend test status;
- documentation status;
- known limitations;
- final tag name.

## Proposed tag

```text
v0.3.0-beta.1
```

## Acceptance criteria

- [ ] Go/no-go decision is recorded.
- [ ] Known limitations are explicit.
- [ ] Release tag is confirmed.
- [ ] CI is green.
- [ ] QA is green.
BODY
)"
create_issue "Review go-no-go for first beta tag" "type: docs,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Tag and publish the first beta release.

## Target version

```text
v0.3.0-beta.1
```

## Scope

- Ensure `develop` is ready.
- Merge or fast-forward `main` to `develop`.
- Create the tag.
- Push the tag.
- Verify GitHub release workflow.
- Verify GitHub release notes.
- Verify Packagist update.
- Optionally test installation from Packagist.

## Acceptance criteria

- [ ] `main` contains the release state.
- [ ] Tag `v0.3.0-beta.1` is pushed.
- [ ] GitHub release exists.
- [ ] Packagist sees the new version.
- [ ] Installation test succeeds if performed.
BODY
)"
create_issue "Tag and publish first beta" "type: release,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Update the roadmap after the first beta release.

## Scope

- Mark `0.27 - First beta preparation` as completed.
- Mention published tag `v0.3.0-beta.1`.
- Clarify what beta means for the project.
- Update current limitations.
- Set next roadmap direction.
- Keep later ideas coherent.

## Acceptance criteria

- [ ] Roadmap reflects first beta release.
- [ ] Next milestone direction is clear.
- [ ] Known limitations are accurate.
- [ ] QA passes.
BODY
)"
create_issue "Update roadmap after first beta" "type: docs,priority: medium,ai-ready" "$body"
rm -f "$body"

echo "First beta preparation issues created successfully."
