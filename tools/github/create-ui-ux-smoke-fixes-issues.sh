#!/usr/bin/env bash

set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI is required. Install it first: https://cli.github.com/"
  exit 1
fi

gh auth status >/dev/null

MILESTONE_TITLE="0.21 - UI/UX smoke test fixes"

ensure_milestone() {
  if gh api repos/:owner/:repo/milestones --jq ".[] | select(.title == \"${MILESTONE_TITLE}\") | .title" | grep -q "${MILESTONE_TITLE}"; then
    echo "Milestone already exists: ${MILESTONE_TITLE}"
  else
    echo "Creating milestone: ${MILESTONE_TITLE}"
    gh api repos/:owner/:repo/milestones \
      -f title="${MILESTONE_TITLE}" \
      -f description="Fix UI/UX regressions discovered during the post-0.20 smoke test: XLSX naming, split controls layout, action dropdown/list behavior, sort icons, header filters and confirmation UX."
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

Fix XLSX export file naming.

## Smoke test finding

The XLSX export control triggers a download that proposes a filename ending with `.csv`.

This is confusing and incorrect.

## Expected behavior

When exporting XLSX, the suggested filename must end with:

```text
.xlsx
```

Examples:

```text
users.xlsx
admin-users-list.xlsx
```

CSV exports must keep `.csv`.

## Scope

- Verify `DatatableExportRequest::getFilename()` uses `ExportFormat::getExtension()`.
- Verify the XLSX writer sets `Content-Disposition` with `.xlsx`.
- Verify export controls pass the correct format URL/route.
- Add/adjust tests for XLSX filename.
- Validate in smoke app.

## Out of scope

- XLSX writer styling.
- Async export.
- Browser download automation.

## Acceptance criteria

- [ ] XLSX export suggests `.xlsx` filename.
- [ ] CSV export still suggests `.csv`.
- [ ] Tests cover XLSX filename.
- [ ] Smoke test validates browser behavior.
- [ ] QA passes.
BODY
)"
create_issue "Fix XLSX export filename extension" "type: bug,area: export,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Fix duplicated controls when `controlsLayout: split` is used.

## Smoke test finding

With:

```twig
{{ zhortein_datatable('users', {
    controlsLayout: 'split'
}) }}
```

column visibility and page size controls are correctly rendered below the table, but they also remain in the header toolbar.

## Expected behavior

In split layout:

Top toolbar should keep:

- global search;
- filters or filter layout area;
- export controls;
- global actions.

Bottom controls should contain:

- column visibility;
- page size selector;
- summary.

The duplicated header controls must be removed.

## Scope

- Review `_toolbar.html.twig`.
- Review `_bottom_controls.html.twig`.
- Ensure `controlsLayout` is passed consistently.
- Add renderer tests to assert no duplicate page size/column visibility controls.
- Validate in smoke app.

## Out of scope

- Full layout builder.
- Persisted user layout preferences.

## Acceptance criteria

- [ ] Column visibility appears only once in split layout.
- [ ] Page size selector appears only once in split layout.
- [ ] Summary remains in bottom-right area.
- [ ] Default layout remains unchanged.
- [ ] Tests cover split layout duplication.
- [ ] QA passes.
BODY
)"
create_issue "Fix duplicated controls in split layout" "type: bug,area: twig,area: bootstrap,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Fix row action dropdown overflow when the datatable is not tall enough.

## Smoke test finding

`rowActionDisplayMode: dropdown` works, but when the table has too few rows, the dropdown overflows inside the table container and a vertical scrollbar appears.

## Expected behavior

The dropdown should be usable and visually clean even for small tables.

## Possible directions

- Review table wrapper overflow behavior.
- Review Bootstrap dropdown placement classes.
- Consider `dropup` or `dropdown-menu-end` placement rules.
- Consider rendering actions dropdown outside clipped container only if needed.
- Avoid breaking normal Bootstrap behavior.

## Scope

- Reproduce in smoke app.
- Fix template/CSS/layout behavior.
- Add docs note if the host app table wrapper can clip dropdowns.
- Add tests where possible, although this may require smoke/browser validation.

## Out of scope

- Full Popper customization.
- Browser E2E test unless already available.
- Replacing Bootstrap dropdown.

## Acceptance criteria

- [ ] Dropdown actions no longer create an unpleasant table scrollbar in common small-table cases.
- [ ] Default inline action mode is unaffected.
- [ ] Smoke test validates behavior.
- [ ] QA passes.
BODY
)"
create_issue "Fix row action dropdown overflow in short tables" "type: bug,area: twig,area: bootstrap,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Improve confirmation UX by replacing native `window.confirm()` with a Bootstrap modal-based confirmation flow.

## Smoke test finding

Native JavaScript confirmation works but feels rough compared to the rest of the Bootstrap UI.

## Expected behavior

Actions with confirmation metadata should be confirmed through a Bootstrap-compatible modal.

The current native `window.confirm()` behavior can remain as fallback.

## Scope

- Design a simple confirmation modal.
- Keep no mandatory extra dependency beyond Bootstrap JS.
- Support GET action links.
- Support non-GET action forms.
- Keep accessibility in mind.
- Preserve existing confirmation metadata API.
- Add tests where practical.
- Validate in smoke app.

## Out of scope

- Complex custom modal API.
- Async confirmation providers.
- SweetAlert/third-party dependency.
- Per-action modal templates unless simple.

## Acceptance criteria

- [ ] Confirmation uses Bootstrap modal when available.
- [ ] Native confirm remains fallback if modal is unavailable.
- [ ] Cancel prevents action.
- [ ] Confirm executes action.
- [ ] GET links and non-GET forms are supported.
- [ ] Smoke test validates behavior.
- [ ] QA passes.
BODY
)"
create_issue "Implement Bootstrap modal action confirmation" "type: feature,area: stimulus,area: bootstrap,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Fix list action mode rendering for non-GET actions.

## Smoke test finding

`rowActionDisplayMode: list` works for links, but non-GET form actions do not stretch to full width, making the list visually inconsistent.

## Expected behavior

In list mode, all actions should have a consistent width and alignment, including non-GET form actions.

## Scope

- Update list action rendering templates.
- Ensure forms use full-width layout where appropriate.
- Ensure buttons inside forms can stretch to full width.
- Keep inline and dropdown modes unaffected.
- Add renderer tests.
- Validate in smoke app.

## Out of scope

- New action display modes.
- Modal confirmation work.

## Acceptance criteria

- [ ] Non-GET action buttons stretch consistently in list mode.
- [ ] GET and non-GET actions align visually in list mode.
- [ ] Inline/dropdown action modes remain unchanged.
- [ ] Tests cover non-GET list actions.
- [ ] QA passes.
BODY
)"
create_issue "Fix non-GET action width in list display mode" "type: bug,area: twig,area: bootstrap,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Fix sort indicator state after sorting.

## Smoke test finding

Sorting by clicking a column header works, but the visual icon remains the neutral up/down indicator instead of changing to ascending/descending.

## Expected behavior

After sorting:

- active ascending column shows ascending indicator;
- active descending column shows descending indicator;
- inactive sortable columns show neutral indicator.

## Scope

- Verify Ajax fragment response includes `sortField` and `sortDirection` state or header render options receive current state.
- Ensure Stimulus updates sort values before refresh.
- Ensure backend renders header with current sort options.
- Add/adjust tests.
- Validate in smoke app.

## Out of scope

- Multi-column sorting.
- Icon provider abstraction.

## Acceptance criteria

- [ ] Active sorted column shows ascending/descending indicator.
- [ ] Neutral indicators remain on unsorted columns.
- [ ] Ajax refresh keeps header indicator in sync.
- [ ] Tests cover state after sort.
- [ ] QA passes.
BODY
)"
create_issue "Fix sortable header indicator state after sorting" "type: bug,area: twig,area: stimulus,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Fix header filter dropdown rendering for `filterLayout: header`.

## Smoke test finding

`filterLayout: 'header'` does not display the expected filter dropdowns in table headers.

## Expected behavior

When `filterLayout: header` is enabled:

- toolbar filters are hidden;
- columns with matching declared filters render a filter button in the header;
- clicking the button opens a Bootstrap dropdown;
- the dropdown contains the corresponding filter control.

## Scope

- Reproduce in smoke app.
- Verify filter-to-column matching (`filter.field === column.name`).
- Verify header receives definition, filters and htmlId context.
- Verify `_column_filter.html.twig` is included.
- Verify Bootstrap dropdown markup.
- Add/adjust renderer tests.
- Validate in smoke app.

## Out of scope

- Active filter state.
- Clear column filter action.
- Custom filter widgets.

## Acceptance criteria

- [ ] Header filter dropdowns render in smoke app.
- [ ] Text filter dropdown works.
- [ ] Boolean/choice filter dropdown works where declared.
- [ ] Toolbar filters are hidden in header mode.
- [ ] Tests cover rendered header filters.
- [ ] QA passes.
BODY
)"
create_issue "Fix header filter dropdown rendering" "type: bug,area: twig,area: bootstrap,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Update roadmap later ideas with two previously discussed features.

## Missing roadmap items

The roadmap is missing:

- bulk actions with row selector;
- hierarchical/tree datatables.

## Scope

- Add bulk actions with selector column to Later ideas.
- Add hierarchical/tree datatable support to Later ideas.
- Optionally add a short note that both are larger features requiring design decisions before implementation.

## Out of scope

- Implementing these features.
- Creating full milestones for them now.

## Acceptance criteria

- [ ] Roadmap includes bulk actions with row selector.
- [ ] Roadmap includes hierarchical/tree datatables.
- [ ] QA passes.
BODY
)"
create_issue "Add bulk actions and hierarchical tables to roadmap ideas" "type: docs,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Create a dedicated documentation overhaul milestone proposal.

## Context

Documentation has grown quickly and is becoming difficult to navigate.

The docs need a dedicated cleanup effort.

Potential tool assistance:

- ZenCoder or similar code/documentation assistant;
- human review;
- structured documentation map.

## Scope

- Create a future milestone proposal for documentation overhaul.
- Identify documentation pain points:
  - duplicated content;
  - obsolete content;
  - scattered snippets;
  - inconsistent entry points;
  - unclear installation path;
  - stale roadmap references.
- Define documentation target structure.
- Decide what should be kept, merged, deleted or rewritten.
- Prepare an issue list for the documentation overhaul milestone.

## Out of scope

- Rewriting all docs in this issue.
- Changing runtime code.

## Acceptance criteria

- [ ] Documentation overhaul milestone proposal exists.
- [ ] Pain points are listed.
- [ ] Target documentation structure is proposed.
- [ ] Follow-up issues are created.
BODY
)"
create_issue "Plan documentation overhaul milestone" "type: docs,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Record post-0.20 smoke test findings.

## Context

After updating the smoke test against the current `develop` branch following milestone 0.20, several UI/UX issues were identified.

## Scope

- Create a smoke report document.
- List each UI/UX issue found.
- Link created issues.
- Record what still works correctly.
- Record current go/no-go status.

## Out of scope

- Fixing issues directly.
- Full browser test automation.

## Acceptance criteria

- [ ] Smoke report exists.
- [ ] Issues are linked.
- [ ] Current status is documented.
- [ ] QA passes.
BODY
)"
create_issue "Record post-0.20 UI UX smoke test findings" "type: docs,type: tests,priority: medium,ai-ready" "$body"
rm -f "$body"

echo "UI/UX smoke test fix issues created successfully."
