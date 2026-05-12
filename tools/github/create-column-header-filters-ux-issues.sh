#!/usr/bin/env bash

set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI is required. Install it first: https://cli.github.com/"
  exit 1
fi

gh auth status >/dev/null

MILESTONE_TITLE="0.17 - Column header filters UX"

ensure_milestone() {
  if gh api repos/:owner/:repo/milestones --jq ".[] | select(.title == \"${MILESTONE_TITLE}\") | .title" | grep -q "${MILESTONE_TITLE}"; then
    echo "Milestone already exists: ${MILESTONE_TITLE}"
  else
    echo "Creating milestone: ${MILESTONE_TITLE}"
    gh api repos/:owner/:repo/milestones \
      -f title="${MILESTONE_TITLE}" \
      -f description="Column header filter dropdowns: filter layout option, per-column dropdown rendering, active state, clear filter action, Stimulus integration and documentation."
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

Add a filter layout option to choose where user-facing filters are rendered.

## Context

User-facing filters currently render in the toolbar. A design decision exists for column header filter dropdowns.

Before rendering header filters, the renderer needs a clear layout option.

## Scope

- Add support for a `filterLayout` runtime option.
- Supported values:
  - `toolbar`;
  - `header`;
  - `none`.
- Keep `toolbar` as the default.
- Ensure `none` hides filter controls but does not disable backend filter parsing.
- Add tests for each layout mode.
- Update docs.

## Out of scope

- Header filter dropdown implementation.
- Active filter state.
- Clear column filter action.
- Persisted filter preferences.

## Constraints

- Preserve current default behavior.
- Runtime options should remain simple.
- TwigCS and PHPStan must pass.

## Acceptance criteria

- [ ] `filterLayout: toolbar` keeps current toolbar filters.
- [ ] `filterLayout: header` hides toolbar filters and prepares header rendering.
- [ ] `filterLayout: none` hides filter controls.
- [ ] Existing filter backend behavior is unchanged.
- [ ] Tests cover all modes.
- [ ] QA passes.
BODY
)"
create_issue "Implement filter layout option" "type: feature,area: twig,area: configuration,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Render user-facing filters as Bootstrap dropdowns in column headers when `filterLayout` is set to `header`.

## Context

The architecture decision chose Bootstrap dropdowns for column header filters.

A column header should show a small filter control when a declared user-facing filter targets that column field.

## Scope

- Match filters to columns by `filter.field === column.name`.
- Render a filter button in matching column headers.
- Render a Bootstrap dropdown containing the filter control.
- Reuse existing `UserFilterDefinition` and filter templates where possible.
- Keep non-filtered columns unchanged.
- Add tests for text, choice and boolean header filters.

## Out of scope

- Active filter state.
- Clear column filter action.
- Multiple filters per column.
- SearchBuilder expressions.
- Custom JS widgets.

## Constraints

- Bootstrap-first.
- Vanilla JavaScript only.
- No jQuery.
- Accessible labels.
- TwigCS and PHPStan must pass.

## Acceptance criteria

- [ ] Header filter button renders for columns with matching filters.
- [ ] Header filter dropdown contains the filter control.
- [ ] Toolbar filters are hidden when `filterLayout: header`.
- [ ] Existing filter names remain `filters[...]`.
- [ ] Tests cover header filter rendering.
- [ ] QA passes.
BODY
)"
create_issue "Render column header filter dropdowns" "type: feature,area: twig,area: bootstrap,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Show active filter state in column headers when header filters are used.

## Context

Users must be able to see which columns are currently filtered.

The design decision requires active state to be visible and not conveyed by color only.

## Scope

- Detect active filters from render options/request state.
- Mark active column filter buttons.
- Add an active class or data attribute.
- Add visually hidden active state text.
- Add translation keys.
- Add tests.

## Out of scope

- Persisted filter state.
- Filter chips outside headers.
- Complex active filter summaries.

## Constraints

- Accessible active state.
- Bootstrap-compatible markup.
- Header and body refresh should keep active state synchronized.
- QA passes.

## Acceptance criteria

- [ ] Active header filters are visually marked.
- [ ] Active state includes accessible text.
- [ ] Inactive filters remain visually neutral.
- [ ] Tests cover active and inactive states.
- [ ] QA passes.
BODY
)"
create_issue "Render active state for column header filters" "type: feature,area: twig,area: bootstrap,area: accessibility,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Add a clear action for individual column header filters.

## Context

When a filter is active in a column header dropdown, users need an obvious way to clear that specific filter.

## Scope

- Render a clear button in column header filter dropdowns.
- Add a Stimulus action `clearColumnFilter`.
- Pass filter name through Stimulus params.
- Clear all controls for that filter name.
- Reset page to 1.
- Refresh fragments.
- Add tests where practical.
- Update docs.

## Out of scope

- Clear all filters action.
- Saved filter presets.
- Complex filter chips UI.

## Constraints

- Vanilla JavaScript only.
- Accessible button label.
- Keep existing clear-all filters behavior.
- QA passes.

## Acceptance criteria

- [ ] Clear button renders in header filter dropdown.
- [ ] Clear button clears the targeted filter.
- [ ] Page resets to 1.
- [ ] Datatable refreshes.
- [ ] Tests/docs are updated.
- [ ] QA passes.
BODY
)"
create_issue "Implement clear column filter action" "type: feature,area: stimulus,area: twig,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Ensure column header filters integrate correctly with the existing Stimulus refresh flow.

## Context

Header filters should reuse existing filter request parameters and Ajax refresh behavior.

## Scope

- Verify header filter controls expose the same filter-control data attribute.
- Ensure `changeFilter` works from header dropdowns.
- Ensure the current page resets to 1.
- Ensure header and body fragments refresh together.
- Add/update tests.
- Validate in smoke app.

## Out of scope

- Frontend automated test suite.
- New filter expression model.
- Custom widgets.

## Constraints

- Reuse existing request format.
- Do not duplicate filter serialization logic.
- Vanilla JavaScript only.
- QA passes.

## Acceptance criteria

- [ ] Header filter changes trigger Ajax refresh.
- [ ] Filter values are sent as `filters[...]`.
- [ ] Header active state updates after refresh.
- [ ] Body rows update correctly.
- [ ] Smoke test validates behavior.
BODY
)"
create_issue "Connect column header filters to Stimulus refresh" "type: feature,area: stimulus,area: twig,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Update smoke test notes and verify column header filter UX in the fresh Symfony application.

## Context

Header filters are a UI-heavy feature. They must be validated in a real browser with Bootstrap dropdowns and Stimulus.

## Scope

- Update smoke app to use `filterLayout: header`.
- Validate text filter in header.
- Validate boolean/choice filter in header.
- Validate active filter state.
- Validate clear column filter action.
- Validate header/body synchronization.
- Record findings in smoke report or docs.
- Create follow-up issues for blockers.

## Out of scope

- Doctrine-specific advanced filter smoke beyond basic checks.
- Frontend automated tests.

## Constraints

- Use fresh Symfony smoke application.
- Keep findings generic.
- QA passes if docs are updated.

## Acceptance criteria

- [ ] Header filters work in smoke app.
- [ ] Bootstrap dropdowns behave correctly.
- [ ] Active state is visible.
- [ ] Clear column action works.
- [ ] Findings are recorded.
BODY
)"
create_issue "Smoke test column header filters UX" "type: tests,area: stimulus,area: twig,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Document column header filter UX.

## Context

Once header filter dropdowns are implemented and smoke-tested, users need clear documentation.

## Scope

- Update `docs/filters.md`.
- Update `docs/table-controls.md`.
- Update `docs/ui-ux-rendering.md` if needed.
- Document `filterLayout` values.
- Document header filter dropdown behavior.
- Document active state.
- Document clear column filter behavior.
- Update roadmap for 0.17 completion.

## Out of scope

- SearchBuilder documentation.
- Saved filter presets.
- Custom widget documentation.

## Constraints

- Documentation must match implemented behavior.
- Examples must be generic.
- QA passes.

## Acceptance criteria

- [ ] Header filters are documented.
- [ ] `filterLayout` is documented.
- [ ] Current limitations are explicit.
- [ ] Roadmap is updated.
- [ ] QA passes.
BODY
)"
create_issue "Document column header filters UX" "type: docs,area: twig,area: stimulus,priority: medium,ai-ready" "$body"
rm -f "$body"

echo "Column header filters UX issues created successfully."
