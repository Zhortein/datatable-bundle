#!/usr/bin/env bash

set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI is required. Install it first: https://cli.github.com/"
  exit 1
fi

gh auth status >/dev/null

MILESTONE_TITLE="0.16 - UI/UX rendering polish"

ensure_milestone() {
  if gh api repos/:owner/:repo/milestones --jq ".[] | select(.title == \"${MILESTONE_TITLE}\") | .title" | grep -q "${MILESTONE_TITLE}"; then
    echo "Milestone already exists: ${MILESTONE_TITLE}"
  else
    echo "Creating milestone: ${MILESTONE_TITLE}"
    gh api repos/:owner/:repo/milestones \
      -f title="${MILESTONE_TITLE}" \
      -f description="Improve the visual and UX quality of Bootstrap-first datatables: actions, boolean cells, sortable headers, control layout and CSS customization."
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

Improve action icon rendering while keeping icon libraries optional.

## Context

Actions already support an optional icon CSS class, but the rendering strategy is still basic.

Host applications may use Bootstrap Icons, FontAwesome, custom CSS icons or no icons.

## Scope

- Review `ActionDefinition::getIcon()` usage.
- Add action icon display options if needed.
- Support icon + label rendering cleanly.
- Support icon position if simple enough.
- Keep icon spans decorative with `aria-hidden="true"`.
- Ensure actions remain accessible without icons.
- Add tests.
- Update documentation.

## Out of scope

- Mandatory Bootstrap Icons dependency.
- Mandatory FontAwesome dependency.
- Symfony UX Icons integration.
- SVG icon provider abstraction.
- Icon-only actions unless accessible labels are designed.

## Constraints

- No mandatory icon package.
- Bootstrap-compatible markup.
- Action labels must remain visible for now.
- TwigCS and PHPStan must pass.

## Acceptance criteria

- [ ] Icon rendering remains optional.
- [ ] Icon + label markup is visually cleaner.
- [ ] Accessibility is preserved.
- [ ] Tests cover icon and no-icon actions.
- [ ] Documentation is updated.
- [ ] QA passes.
BODY
)"
create_issue "Implement action icon rendering options" "type: feature,area: twig,area: bootstrap,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Support multiple display modes for row actions.

## Context

Inline action buttons work for a small number of actions but become visually noisy when a datatable has many row actions.

## Scope

- Introduce an action display mode concept.
- Support at least:
  - inline;
  - dropdown;
  - list if simple enough.
- Apply display mode to row actions.
- Keep current inline behavior as default.
- Add tests.
- Update documentation.

## Out of scope

- Batch actions.
- Permission UI.
- Icon provider abstraction.
- Complex responsive action menus.

## Constraints

- Bootstrap-first markup.
- No JavaScript dependency beyond Bootstrap dropdown where needed.
- Preserve current default behavior.
- TwigCS and PHPStan must pass.

## Acceptance criteria

- [ ] Inline row action mode remains default.
- [ ] Dropdown row action mode is available.
- [ ] Action rendering remains accessible.
- [ ] Tests cover modes.
- [ ] Documentation is updated.
- [ ] QA passes.
BODY
)"
create_issue "Implement action display modes" "type: feature,area: twig,area: bootstrap,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Support multiple display modes for boolean cells.

## Context

Boolean cells currently render simple translated badges. Other visual styles may be better depending on the application context.

## Scope

- Introduce boolean display mode options.
- Support at least:
  - badge;
  - icon;
  - switch/toggle;
  - text if practical.
- Keep current badge behavior as default.
- Preserve translation support.
- Add tests.
- Update documentation.

## Out of scope

- Editable boolean toggles.
- Ajax state changes.
- Mandatory icon library dependency.
- Complex icon provider.

## Constraints

- Display-only behavior.
- Accessible markup.
- Bootstrap-first.
- TwigCS and PHPStan must pass.

## Acceptance criteria

- [ ] Badge boolean rendering remains default.
- [ ] Icon boolean rendering is available.
- [ ] Switch/toggle-style display is available if feasible.
- [ ] Tests cover true and false states.
- [ ] Documentation is updated.
- [ ] QA passes.
BODY
)"
create_issue "Implement boolean cell display modes" "type: feature,area: twig,area: bootstrap,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Improve visual rendering of sortable table headers.

## Context

Sortable headers currently work functionally, but the visual alignment and current sort indicator are not polished enough.

## Scope

- Improve sortable header layout.
- Add neutral sort indicator.
- Add ascending sort indicator.
- Add descending sort indicator.
- Keep `aria-sort` support.
- Align header content with cell alignment where appropriate.
- Avoid mandatory icon dependencies.
- Add tests.
- Update documentation.

## Out of scope

- Multi-column sorting.
- Icon provider abstraction.
- Complex header filter popovers.

## Constraints

- Bootstrap-first.
- Accessible labels must remain.
- No mandatory icon package.
- TwigCS and PHPStan must pass.

## Acceptance criteria

- [ ] Sortable headers look visually cleaner.
- [ ] Current sort direction is visually indicated.
- [ ] Non-sorted sortable headers have a neutral indicator.
- [ ] Accessibility remains correct.
- [ ] Tests are updated.
- [ ] QA passes.
BODY
)"
create_issue "Polish sortable header rendering" "type: feature,area: twig,area: bootstrap,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Reorganize datatable controls to reduce toolbar clutter.

## Context

The smoke test showed that the top toolbar can become crowded with search, filters, column visibility, export and page size controls.

Some controls are better placed under the table.

## Scope

- Introduce a configurable control layout.
- Move or allow moving these controls below the table:
  - column visibility;
  - page size selector;
  - summary.
- Keep search, filters and global actions near the top.
- Keep current behavior available if needed.
- Add tests.
- Update documentation.

## Out of scope

- Full layout builder.
- Drag-and-drop controls.
- User-specific layout preferences.

## Constraints

- Bootstrap-first layout.
- Responsive-friendly markup.
- Runtime options should be simple.
- TwigCS and PHPStan must pass.

## Acceptance criteria

- [ ] A cleaner default or configurable layout exists.
- [ ] Page size and column visibility can appear below the table.
- [ ] Summary can be aligned to the right.
- [ ] Existing tests are updated.
- [ ] Documentation is updated.
- [ ] QA passes.
BODY
)"
create_issue "Implement configurable datatable control layout" "type: feature,area: twig,area: bootstrap,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Allow developers to add custom CSS classes to the rendered table and wrapper.

## Context

Applications may need project-specific classes for spacing, borders, themes, responsive behavior or custom styles.

## Scope

- Add runtime options for additional CSS classes.
- Support at least:
  - root wrapper class;
  - table wrapper class;
  - table class.
- Preserve existing classes.
- Avoid overwriting Bootstrap defaults.
- Add tests.
- Update documentation.

## Out of scope

- Full CSS asset system.
- Theme registry.
- Tailwind support.

## Constraints

- Runtime options must be simple.
- Developer-provided classes are appended, not replacing core classes unless explicitly designed.
- TwigCS and PHPStan must pass.

## Acceptance criteria

- [ ] Additional root wrapper classes can be rendered.
- [ ] Additional table wrapper classes can be rendered.
- [ ] Additional table classes can be rendered.
- [ ] Defaults remain unchanged.
- [ ] Tests cover class merging.
- [ ] Documentation is updated.
- [ ] QA passes.
BODY
)"
create_issue "Allow additional table and wrapper CSS classes" "type: feature,area: twig,area: bootstrap,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Create a design proposal for column header filter popovers.

## Context

Column filters currently render in the toolbar. The desired UX is to show a small filter icon in the column header and open a compact filter popover/dropdown when clicked.

This is more modern and saves toolbar space, but it impacts header rendering, filtering state, accessibility and Stimulus behavior.

## Scope

- Document the proposed UX.
- Decide between Bootstrap dropdown and Bootstrap popover.
- Define markup.
- Define Stimulus behavior.
- Define accessibility requirements.
- Define request parameter behavior.
- Define how active filter state is shown in headers.
- Create follow-up implementation issues.

## Out of scope

- Implementation.
- New JavaScript dependencies.
- Complex SearchBuilder expressions.

## Constraints

- Bootstrap-first.
- Vanilla JavaScript only.
- No jQuery.
- Keep filters explicit and typed.
- Must remain accessible.

## Acceptance criteria

- [ ] Proposal document exists.
- [ ] UX approach is chosen.
- [ ] Implementation issues are created.
- [ ] Current limitations are explicit.
- [ ] QA passes.
BODY
)"
create_issue "Design column header filter popovers" "type: architecture,area: twig,area: stimulus,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Document the UI/UX customization features introduced in this milestone.

## Context

After action display modes, boolean display modes, sortable header polish, layout options and custom CSS classes are implemented, documentation must be updated.

## Scope

- Update or create UI/UX rendering customization documentation.
- Document action icons.
- Document action display modes.
- Document boolean cell display modes.
- Document sortable header indicators.
- Document layout options.
- Document custom CSS class options.
- Update README/docs index if needed.
- Update roadmap for 0.16 completion.

## Out of scope

- Column header filter popover implementation docs if only designed.
- Full theme registry documentation.
- Tailwind support.

## Constraints

- Documentation must match implemented behavior.
- Examples must be generic.
- QA passes.

## Acceptance criteria

- [ ] UI/UX customization docs are updated.
- [ ] README/docs index links are accurate.
- [ ] Roadmap is updated for 0.16 completion.
- [ ] QA passes.
BODY
)"
create_issue "Document UI/UX rendering customization" "type: docs,area: twig,area: bootstrap,priority: medium,ai-ready" "$body"
rm -f "$body"

echo "UI/UX rendering polish issues created successfully."
