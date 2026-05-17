#!/usr/bin/env bash

set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI is required. Install it first: https://cli.github.com/"
  exit 1
fi

gh auth status >/dev/null

MILESTONE_TITLE="0.25 - Icon system and visual consistency"

ensure_milestone() {
  if gh api repos/:owner/:repo/milestones --jq ".[] | select(.title == \"${MILESTONE_TITLE}\") | .title" | grep -q "${MILESTONE_TITLE}"; then
    echo "Milestone already exists: ${MILESTONE_TITLE}"
  else
    echo "Creating milestone: ${MILESTONE_TITLE}"
    gh api repos/:owner/:repo/milestones \
      -f title="${MILESTONE_TITLE}" \
      -f description="Provide a consistent optional icon strategy across actions, booleans, sorting, filters, exports and bulk actions."
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

Design the icon strategy for the bundle.

## Context

The bundle already supports action icons and textual indicators. After bulk actions, the UI needs a consistent icon system across row actions, global actions, bulk actions, booleans, sorting, filters and exports.

## Scope

- Define icon strategy principles.
- Decide whether icons remain CSS-class based for now.
- Define icon keys.
- Define configuration shape.
- Decide default icon classes.
- Decide accessibility rules.
- Keep icon libraries optional.
- Document public API decisions.

## Out of scope

- Mandatory icon package dependency.
- SVG icon provider.
- Symfony UX Icons hard dependency.
- Icon-only actions without accessibility design.

## Acceptance criteria

- [ ] Icon strategy decision is documented.
- [ ] Icon keys are listed.
- [ ] Configuration shape is proposed.
- [ ] Accessibility rules are explicit.
- [ ] No runtime code is changed unless needed for tests/docs.
- [ ] QA passes.
BODY
)"
create_issue "Design icon strategy and configuration model" "type: architecture,area: ui,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Add a central icon configuration model and resolver.

## Scope

- Add bundle configuration for icons if appropriate.
- Add `IconSet` / `IconRegistry` / `IconResolver` or similar lightweight service.
- Support default icon keys.
- Support host application overrides.
- Keep icon values as CSS classes for the first implementation.
- Add tests.

## Expected icon keys

Initial candidates:

- `action_view`
- `action_edit`
- `action_delete`
- `action_create`
- `bulk_actions`
- `boolean_true`
- `boolean_false`
- `sort_neutral`
- `sort_asc`
- `sort_desc`
- `filter`
- `filter_active`
- `export`
- `export_csv`
- `export_xlsx`
- `confirmation_warning`

## Out of scope

- SVG rendering.
- Mandatory Bootstrap Icons/FontAwesome dependency.
- UX Icons integration.

## Acceptance criteria

- [ ] Icon resolver exists.
- [ ] Default icon keys exist.
- [ ] Config override works.
- [ ] Tests cover defaults and overrides.
- [ ] QA passes.
BODY
)"
create_issue "Implement icon configuration and resolver" "type: feature,area: configuration,area: ui,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Apply the icon resolver to row, global and bulk actions.

## Scope

- Preserve explicit action icons.
- Allow default icons by action name where possible.
- Apply icon position consistently.
- Support row actions.
- Support global actions.
- Support bulk actions.
- Keep accessible labels visible.
- Add renderer tests.

## Out of scope

- Icon-only actions.
- SVG provider.
- Changing action declaration API unless necessary.

## Acceptance criteria

- [ ] Explicit action icons still work.
- [ ] Default action icons can be resolved.
- [ ] Bulk action icons work.
- [ ] Accessibility remains correct.
- [ ] Tests cover row/global/bulk icon rendering.
- [ ] QA passes.
BODY
)"
create_issue "Apply icon resolver to actions and bulk actions" "type: feature,area: actions,area: twig,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Apply the icon strategy to boolean cell display modes.

## Scope

- Use icon resolver for boolean icon mode.
- Keep text/badge/switch modes unchanged.
- Support true/false icon keys.
- Keep visually hidden labels.
- Add tests.
- Update docs.

## Out of scope

- Editable boolean toggles.
- Icon-only table cells without accessible labels.

## Acceptance criteria

- [ ] Boolean icon mode uses configured icons.
- [ ] True/false icons can be overridden.
- [ ] Accessibility labels remain.
- [ ] Tests cover configured icons.
- [ ] QA passes.
BODY
)"
create_issue "Apply icon resolver to boolean cell icon mode" "type: feature,area: twig,area: ui,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Apply the icon strategy to sortable headers.

## Scope

- Replace hardcoded textual indicators with configured icon classes or keep text fallback.
- Support:
  - neutral sort icon;
  - ascending sort icon;
  - descending sort icon.
- Preserve `aria-sort`.
- Preserve accessible labels.
- Add tests.
- Update docs.

## Out of scope

- Multi-column sorting.
- SVG icon rendering.

## Acceptance criteria

- [ ] Sort neutral/asc/desc icons are configurable.
- [ ] Fallback remains available.
- [ ] Active sort state remains correct.
- [ ] Tests cover default and override behavior.
- [ ] QA passes.
BODY
)"
create_issue "Apply icon resolver to sortable headers" "type: feature,area: twig,area: ui,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Apply icon strategy to filters and export controls.

## Scope

- Header filter button icon.
- Active filter icon if applicable.
- Export dropdown button icon.
- CSV export icon.
- XLSX export icon.
- Keep text labels visible.
- Add renderer tests.
- Update docs.

## Out of scope

- Icon-only dropdown items.
- SVG provider.

## Acceptance criteria

- [ ] Header filter icon is configurable.
- [ ] Export icons are configurable.
- [ ] CSV/XLSX icons are configurable.
- [ ] Text labels remain visible.
- [ ] Tests cover rendering.
- [ ] QA passes.
BODY
)"
create_issue "Apply icon resolver to filters and exports" "type: feature,area: twig,area: export,area: ui,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Document icon configuration and visual consistency.

## Scope

- Add or update icon documentation.
- Explain default icon keys.
- Explain CSS-class based strategy.
- Explain Bootstrap Icons example.
- Explain FontAwesome example.
- Explain per-action explicit icons.
- Explain accessibility rules.
- Link from UI/UX docs and README/index if needed.

## Out of scope

- Documenting unimplemented SVG provider.
- Recommending one mandatory icon library.

## Acceptance criteria

- [ ] Icon documentation is clear.
- [ ] Default keys are documented.
- [ ] Override examples exist.
- [ ] Accessibility limitations are explicit.
- [ ] QA passes.
BODY
)"
create_issue "Document icon system and visual consistency" "type: docs,area: ui,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Smoke test the icon system in the fresh Symfony application.

## Scope

- Configure Bootstrap Icons or FontAwesome in the smoke app.
- Test row/global/bulk action icons.
- Test boolean icon mode.
- Test sort icons.
- Test filter icons.
- Test CSV/XLSX export icons.
- Record findings.

## Acceptance criteria

- [ ] Smoke test validates configured icon set.
- [ ] No missing icon classes in expected UI.
- [ ] Text labels and accessibility remain acceptable.
- [ ] Findings are recorded.
BODY
)"
create_issue "Smoke test icon system" "type: tests,area: ui,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Update roadmap after icon system milestone.

## Scope

- Mark 0.25 as completed.
- Clarify delivered icon system capabilities.
- Keep limitations explicit.
- Set next milestone to advanced filter expressions unless priorities change.

## Acceptance criteria

- [ ] Roadmap updated.
- [ ] QA passes.
BODY
)"
create_issue "Update roadmap after icon system" "type: docs,priority: medium,ai-ready" "$body"
rm -f "$body"

echo "Icon system milestone issues created successfully."
