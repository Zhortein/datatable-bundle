#!/usr/bin/env bash

set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI is required. Install it first: https://cli.github.com/"
  exit 1
fi

gh auth status >/dev/null

MILESTONE_TITLE="0.22 - Documentation overhaul"

ensure_milestone() {
  if gh api repos/:owner/:repo/milestones --jq ".[] | select(.title == \"${MILESTONE_TITLE}\") | .title" | grep -q "${MILESTONE_TITLE}"; then
    echo "Milestone already exists: ${MILESTONE_TITLE}"
  else
    echo "Creating milestone: ${MILESTONE_TITLE}"
    gh api repos/:owner/:repo/milestones \
      -f title="${MILESTONE_TITLE}" \
      -f description="Audit, reorganize and rewrite documentation before moving closer to beta/stable releases."
  fi
}

issue_exists() {
  local title="$1"
  gh issue list --state all --search "$title in:title" --json title --jq ".[].title" | grep -Fxq "$title"
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

ensure_milestone

create_issue "Audit documentation and classify files" "type: docs,priority: high,ai-ready" "Inventory all documentation files, classify each as keep/merge/rewrite/split/archive/delete, identify duplicates and obsolete snippets."
create_issue "Rewrite README as project landing page" "type: docs,priority: high,ai-ready" "Rewrite README as a concise landing page with install summary, minimal example, feature overview, stability note and documentation links."
create_issue "Rewrite installation and quick-start documentation" "type: docs,priority: high,ai-ready" "Clarify Composer install, route import, Stimulus controller activation, Bootstrap requirement and first working examples."
create_issue "Consolidate provider documentation" "type: docs,area: doctrine,priority: high,ai-ready" "Consolidate Array and Doctrine provider docs, including joins, custom joins, aggregates and performance."
create_issue "Consolidate feature documentation" "type: docs,priority: high,ai-ready" "Consolidate filters, actions, security, UI/UX, exports, preferences and theming docs."
create_issue "Split architecture documentation into focused pages" "type: docs,priority: medium,ai-ready" "Split the large architecture document into overview, providers, rendering, Stimulus, exports and Doctrine pages."
create_issue "Remove obsolete documentation snippets and stale notes" "type: docs,priority: medium,ai-ready" "Remove or archive snippets already merged, stale smoke notes and obsolete documentation fragments."
create_issue "Run final documentation review" "type: docs,priority: high,ai-ready" "Check navigation, links, examples, roadmap, public API review and run QA after the overhaul."

echo "Documentation overhaul issues created successfully."
