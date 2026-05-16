#!/usr/bin/env bash
set -euo pipefail

MILESTONE_TITLE="0.23 - Second alpha preparation"

gh auth status >/dev/null

if ! gh api repos/:owner/:repo/milestones --jq ".[] | select(.title == \"${MILESTONE_TITLE}\") | .title" | grep -q "${MILESTONE_TITLE}"; then
  gh api repos/:owner/:repo/milestones -f title="${MILESTONE_TITLE}" -f description="Prepare the next public alpha release."
fi

issue_exists() {
  gh issue list --state all --search "$1 in:title" --json title --jq ".[].title" | grep -Fxq "$1"
}

create_issue() {
  local title="$1"
  local labels="$2"
  local body="$3"

  if issue_exists "$title"; then
    echo "Issue already exists: $title"
    return
  fi

  local tmpfile
  tmpfile="$(mktemp)"
  printf "%s\n" "$body" > "$tmpfile"

  local label_args=()
  IFS=',' read -ra label_list <<< "$labels"
  for raw_label in "${label_list[@]}"; do
    local label
    label="$(printf "%s" "$raw_label" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')"
    if [ -n "$label" ]; then
      label_args+=(--label "$label")
    fi
  done

  gh issue create --title "$title" --body-file "$tmpfile" --milestone "$MILESTONE_TITLE" "${label_args[@]}"
  rm -f "$tmpfile"
}

create_issue "Improve pagination placement and size options" "type: enhancement,area: twig,area: bootstrap,priority: medium,ai-ready" "Smoke finding: pagination appears below the table in standard Bootstrap format. Expected: default layout places pagination cleanly in the table bottom/footer area aligned right; split layout centers pagination; tableSmall should use small pagination or expose a developer option. Add renderer tests and smoke validation."

create_issue "Fix sortable header indicator regression after Ajax sorting" "type: bug,area: twig,area: stimulus,priority: high,ai-ready" "Smoke finding: sorting works but icons remain neutral. Trace header button params, Stimulus sort(), request factory, DatatableRequest, getColumnVisibilityOptions(), renderHeader() and _header.html.twig. Ensure active sort direction renders with correct icon and aria-sort after Ajax header refresh. Add tests using the actual current API names."

create_issue "Fix cumulative header filters and clear controls" "type: bug,area: stimulus,area: twig,priority: high,ai-ready" "Smoke finding: with filterLayout: header, filters appear but reset/clear controls are not always apparent and filters do not accumulate with AND like toolbar filters. Ensure header filter controls have correct data-filter-control attributes, filters[...] names, actions, and Stimulus serializes all active header filters together. Add frontend and renderer tests."

create_issue "Expose and document boolean display mode option" "type: docs,area: twig,priority: high,ai-ready" "Smoke finding: boolean display modes exist but usage is unclear and may not be propagated consistently. Verify booleanDisplayMode render option works for badge, icon, switch and text. Add tests and documentation showing {{ zhortein_datatable('users', { booleanDisplayMode: 'switch' }) }} and limitations."

create_issue "Update second alpha smoke report with UI findings" "type: docs,type: tests,priority: medium,ai-ready" "Update docs/smoke-reports/second-alpha-smoke-test-report.md with the latest findings: pagination UX, sort indicator regression, cumulative header filters/clear controls, boolean display mode documentation/config clarity. Link follow-up issues and mark the recommendation clearly."

echo "Second alpha smoke findings issues created successfully."
