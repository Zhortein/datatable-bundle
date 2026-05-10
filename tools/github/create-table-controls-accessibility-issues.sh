#!/usr/bin/env bash

set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI is required. Install it first: https://cli.github.com/"
  exit 1
fi

gh auth status >/dev/null

MILESTONE_TITLE="0.7 - Table controls and accessibility foundation"

ensure_milestone() {
  if gh api repos/:owner/:repo/milestones --jq ".[] | select(.title == \"${MILESTONE_TITLE}\") | .title" | grep -q "${MILESTONE_TITLE}"; then
    echo "Milestone already exists: ${MILESTONE_TITLE}"
  else
    echo "Creating milestone: ${MILESTONE_TITLE}"
    gh api repos/:owner/:repo/milestones \
      -f title="${MILESTONE_TITLE}" \
      -f description="Sortable headers, page size controls, loading/error states, accessibility improvements and UI documentation."
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

Render sortable column headers that trigger the Stimulus `sort` action.

## Context

The backend supports single-column sorting and the Stimulus controller already exposes a `sort()` method, but table headers do not yet render sortable controls.

## Scope

- Update header template.
- Render sortable columns as buttons.
- Add `data-action="zhortein--datatable-bundle--datatable#sort"`.
- Add `data-zhortein--datatable-bundle--datatable-field-param`.
- Add visual state hooks for current sort field/direction.
- Add tests.

## Out of scope

- Multi-column sorting.
- Persisted user preferences.
- Advanced icons.

## Constraints

- Bootstrap-first markup.
- Accessible button labels.
- Vanilla Stimulus only.
- TwigCS must pass.

## Acceptance criteria

- [ ] Sortable headers render as interactive controls.
- [ ] Non-sortable headers remain static.
- [ ] Stimulus params match the existing controller.
- [ ] Tests cover sortable and non-sortable headers.
- [ ] QA passes.
BODY
)"
create_issue "Implement sortable header rendering" "type: feature,area: twig,area: stimulus,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Expose current sorting state in the datatable shell and rendered headers.

## Context

Headers need to know the current sort field and direction to display active sort state and ARIA attributes.

## Scope

- Pass current sort field/direction from render options.
- Add sort state data attributes or CSS classes.
- Add `aria-sort` where appropriate.
- Add tests.

## Out of scope

- Multi-column sorting.
- Persisted sorting preferences.
- Icon provider abstraction.

## Constraints

- Keep accessibility in mind.
- Keep renderer server-side.
- Do not add frontend dependencies.
- QA passes.

## Acceptance criteria

- [ ] Active sorted column is detectable in markup.
- [ ] `aria-sort` is rendered for active sortable column.
- [ ] Tests cover asc/desc/no-sort states.
- [ ] QA passes.
BODY
)"
create_issue "Render current sorting state" "type: feature,area: twig,area: bootstrap,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Add a page size selector to the datatable toolbar.

## Context

The request and renderer already support page size defaults, but the UI does not expose a page size selector.

## Scope

- Render a page size selector in the toolbar.
- Add allowed page size options.
- Wire change event to Stimulus.
- Reset page to 1 on page size change.
- Add tests.

## Out of scope

- Persisting user preferences.
- Per-user settings.
- Admin configuration UI.

## Constraints

- Bootstrap-compatible form control.
- Vanilla Stimulus only.
- Accessible label.
- QA passes.

## Acceptance criteria

- [ ] Page size selector renders when enabled.
- [ ] Selector has accessible label.
- [ ] Stimulus handles page size changes.
- [ ] Tests cover rendering and controller parameter names.
- [ ] QA passes.
BODY
)"
create_issue "Implement page size selector" "type: feature,area: twig,area: stimulus,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Improve the loading and error states rendered by the datatable shell and controlled by Stimulus.

## Context

The datatable shell already contains loading and error targets, but the UX can be improved.

## Scope

- Improve Bootstrap loading markup.
- Improve Bootstrap error alert markup.
- Ensure `aria-busy` is toggled correctly.
- Ensure errors are cleared on successful refresh.
- Add tests where practical.
- Update documentation.

## Out of scope

- Toast notifications.
- Retry button.
- Custom error renderer.

## Constraints

- Accessibility-friendly markup.
- No frontend dependencies.
- Vanilla Stimulus only.
- QA passes.

## Acceptance criteria

- [ ] Loading state is visible and accessible.
- [ ] Error state is visible and accessible.
- [ ] Stimulus clears stale errors.
- [ ] Tests/docs are updated.
- [ ] QA passes.
BODY
)"
create_issue "Improve loading and error states" "type: feature,area: twig,area: stimulus,area: bootstrap,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Add default alignment behavior for common cell types.

## Context

Business tables benefit from predictable alignment:
numeric values right-aligned, booleans centered, actions right-aligned.

## Scope

- Add default CSS classes when column class is not explicitly provided.
- Numeric cells default to `text-end`.
- Boolean/enum cells default to `text-center` if appropriate.
- Actions stay `text-end`.
- Preserve explicit `className`.
- Add tests.

## Out of scope

- Full theming system.
- Per-theme alignment configuration.
- CSS file generation.

## Constraints

- Bootstrap-first.
- Do not override explicit user classes.
- QA passes.

## Acceptance criteria

- [ ] Default alignment is applied by cell type.
- [ ] Explicit className is preserved.
- [ ] Tests cover default and explicit classes.
- [ ] QA passes.
BODY
)"
create_issue "Implement default column alignment by cell type" "type: feature,area: twig,area: bootstrap,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Improve accessibility of the rendered datatable markup.

## Context

The bundle should generate professional back-office tables with accessible controls.

## Scope

- Review table semantics.
- Review toolbar labels.
- Review pagination labels.
- Review sort controls.
- Review loading/error ARIA attributes.
- Add missing visually-hidden labels.
- Add tests where practical.
- Document accessibility choices.

## Out of scope

- Full WCAG audit.
- Browser-based accessibility testing.
- Screen-reader test automation.

## Constraints

- Bootstrap-compatible markup.
- No frontend dependencies.
- QA passes.

## Acceptance criteria

- [ ] Sort controls have accessible labels.
- [ ] Pagination controls have accessible labels.
- [ ] Loading/error states have appropriate ARIA attributes.
- [ ] Documentation mentions accessibility basics.
- [ ] QA passes.
BODY
)"
create_issue "Improve datatable accessibility markup" "type: feature,area: twig,area: bootstrap,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Document table controls and frontend interaction behavior.

## Context

After sort headers and page size controls are implemented, users need clear documentation.

## Scope

- Document search behavior.
- Document sorting behavior.
- Document page size behavior.
- Document pagination behavior.
- Document loading/error states.
- Link from README and docs index if needed.

## Out of scope

- Doctrine provider documentation.
- Actions documentation.
- Export documentation.

## Constraints

- Documentation must match implemented behavior.
- No private/client-specific examples.
- QA passes.

## Acceptance criteria

- [ ] Table controls documentation exists.
- [ ] README/docs index links are accurate.
- [ ] Current limitations are documented.
- [ ] QA passes.
BODY
)"
create_issue "Document table controls and interactions" "type: docs,area: stimulus,area: twig,priority: medium,ai-ready" "$body"
rm -f "$body"

echo "Table controls and accessibility issues created successfully."
