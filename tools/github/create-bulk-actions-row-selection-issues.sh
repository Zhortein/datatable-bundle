#!/usr/bin/env bash

set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI is required. Install it first: https://cli.github.com/"
  exit 1
fi

gh auth status >/dev/null

MILESTONE_TITLE="0.24 - Bulk actions and row selection"

ensure_milestone() {
  if gh api repos/:owner/:repo/milestones --jq ".[] | select(.title == \"${MILESTONE_TITLE}\") | .title" | grep -q "${MILESTONE_TITLE}"; then
    echo "Milestone already exists: ${MILESTONE_TITLE}"
  else
    echo "Creating milestone: ${MILESTONE_TITLE}"
    gh api repos/:owner/:repo/milestones \
      -f title="${MILESTONE_TITLE}" \
      -f description="Add production-oriented row selection and bulk actions for business datatables."
  fi
}

issue_exists() {
  local title="$1"
  gh issue list --state all --search "$title in:title" --json title --jq ".[].title" | grep -Fxq "$title"
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

Design the backend and public API for bulk actions.

## Context

Bulk actions are required for production back-office datatables. The bundle already supports row/global actions, CSRF-aware non-GET actions, confirmation metadata and action visibility.

Bulk actions must reuse the same philosophy:

- backend-defined;
- explicit;
- CSRF-aware;
- authorization-aware through extension points;
- Bootstrap-first rendering;
- Stimulus-enhanced.

## Scope

- Define `BulkActionDefinition` or equivalent.
- Define API on `DatatableDefinition`, for example:
  - `addBulkAction(...)`
  - `getBulkActions()`
- Decide supported metadata:
  - name;
  - route;
  - label;
  - icon;
  - HTTP method;
  - confirmation message;
  - CSS class;
  - attributes;
  - route parameters if needed.
- Decide payload format for selected row identifiers.
- Decide whether first version supports selected rows only or all filtered rows.
- Document accepted limitations.

## Out of scope

- UI implementation.
- Stimulus selection state.
- Backend action controllers.
- All-filtered-rows selection.
- Async bulk jobs.

## Acceptance criteria

- [ ] Bulk action API is designed.
- [ ] Public API decision is documented.
- [ ] Tests cover definition object and datatable definition storage.
- [ ] Current row/global action API remains unchanged.
- [ ] QA passes.
BODY
)"
create_issue "Design bulk action declaration API" "type: architecture,area: actions,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Render a row selection column when bulk actions are enabled.

## Scope

- Add selector column rendering when datatable has bulk actions.
- Render a header checkbox for current visible rows.
- Render a row checkbox per row.
- Use stable row identifiers.
- Use accessible labels.
- Add renderer tests.
- Keep layout Bootstrap-first.

## Out of scope

- Selection state persistence across pages.
- Backend bulk action execution.
- All-filtered-rows selection.

## Acceptance criteria

- [ ] Header checkbox renders when bulk actions exist.
- [ ] Row checkboxes render for each row.
- [ ] Checkbox values contain row identifiers.
- [ ] No selector column renders when no bulk actions exist.
- [ ] Tests cover selector rendering.
- [ ] QA passes.
BODY
)"
create_issue "Render row selection column for bulk actions" "type: feature,area: twig,area: bootstrap,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Add Stimulus state management for selected rows.

## Scope

- Track selected row identifiers in the datatable controller.
- Support selecting/unselecting one row.
- Support selecting/unselecting all visible rows.
- Update header checkbox state.
- Expose selected count in the DOM.
- Reset selection when data refreshes if needed.
- Add frontend tests.

## Out of scope

- Persisting selection across pages.
- Selecting all filtered rows.
- Backend execution.

## Acceptance criteria

- [ ] Single row selection works.
- [ ] Select all visible rows works.
- [ ] Unselect all visible rows works.
- [ ] Header checkbox state updates.
- [ ] Selected count updates.
- [ ] Tests cover state behavior.
- [ ] Frontend tests pass.
BODY
)"
create_issue "Implement Stimulus row selection state" "type: feature,area: stimulus,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Render a bulk action toolbar when bulk actions are available.

## Scope

- Add a bulk action area to the datatable UI.
- Render bulk action buttons/forms.
- Show selected count.
- Disable bulk actions when no rows are selected.
- Keep layout compatible with default and split controls layout.
- Add renderer tests.

## Out of scope

- Actual backend execution.
- Async jobs.
- Advanced responsive toolbar.

## Acceptance criteria

- [ ] Bulk action toolbar renders only when bulk actions exist.
- [ ] Selected count target exists.
- [ ] Bulk action controls are disabled with no selection.
- [ ] UI works in default and split layouts.
- [ ] Tests cover rendering.
- [ ] QA passes.
BODY
)"
create_issue "Render bulk action toolbar" "type: feature,area: twig,area: bootstrap,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Submit selected row identifiers with bulk actions.

## Scope

- Serialize selected row IDs into bulk action form payload.
- Support non-GET bulk actions as CSRF-aware forms.
- Support GET bulk actions only if explicitly useful.
- Use existing confirmation metadata if possible.
- Add frontend tests for payload generation.
- Add renderer/controller tests where useful.

## Out of scope

- Implementing host application action controllers.
- Async bulk action processing.
- All-filtered-rows mode.

## Acceptance criteria

- [ ] Selected identifiers are submitted.
- [ ] Non-GET bulk actions include CSRF token.
- [ ] Confirmation works for bulk actions.
- [ ] Empty selection prevents submit or keeps action disabled.
- [ ] Tests cover payload generation.
- [ ] QA passes.
BODY
)"
create_issue "Submit selected row identifiers for bulk actions" "type: feature,area: actions,area: stimulus,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Integrate bulk action visibility and security extension points.

## Scope

- Decide whether existing `ActionVisibilityCheckerInterface` can handle bulk actions or whether a dedicated interface is needed.
- Ensure hidden bulk actions do not render URLs/forms.
- Ensure CSRF behavior is preserved.
- Document that backend routes must enforce authorization.
- Add tests.

## Out of scope

- Built-in voters.
- Security expression language.
- Backend action controller implementation.

## Acceptance criteria

- [ ] Bulk action visibility is controllable.
- [ ] Hidden bulk actions are not rendered.
- [ ] CSRF-aware behavior is tested.
- [ ] Documentation explains backend authorization responsibility.
- [ ] QA passes.
BODY
)"
create_issue "Integrate bulk action visibility and security" "type: security,area: actions,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Smoke test bulk actions in the fresh Symfony application.

## Scope

- Add one or more bulk actions to the smoke datatable.
- Validate selector column.
- Validate select one row.
- Validate select all visible rows.
- Validate selected count.
- Validate disabled/enabled bulk action controls.
- Validate bulk action payload.
- Validate CSRF and confirmation.
- Record findings.

## Acceptance criteria

- [ ] Bulk action smoke path passes.
- [ ] Findings are recorded.
- [ ] Non-blocking issues are tracked.
BODY
)"
create_issue "Smoke test bulk actions and row selection" "type: tests,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Document bulk actions and row selection.

## Scope

- Add user-facing documentation.
- Explain selector column.
- Explain selected row payload.
- Explain CSRF and confirmation.
- Explain security responsibilities.
- Explain limitations:
  - no all-filtered-rows selection yet;
  - no persisted selection across pages;
  - no async bulk jobs.
- Add examples.

## Acceptance criteria

- [ ] Bulk actions are documented.
- [ ] Limitations are clear.
- [ ] README/docs index link is updated if needed.
- [ ] QA passes.
BODY
)"
create_issue "Document bulk actions and row selection" "type: docs,area: actions,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Update roadmap after bulk actions milestone.

## Scope

- Mark 0.24 as completed.
- Clarify remaining limitations.
- Move next milestone to icon system and visual consistency unless priorities change.

## Acceptance criteria

- [ ] Roadmap updated.
- [ ] QA passes.
BODY
)"
create_issue "Update roadmap after bulk actions" "type: docs,priority: medium,ai-ready" "$body"
rm -f "$body"

echo "Bulk actions milestone issues created successfully."
