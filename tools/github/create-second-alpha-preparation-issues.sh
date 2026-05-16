#!/usr/bin/env bash

set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI is required. Install it first: https://cli.github.com/"
  exit 1
fi

gh auth status >/dev/null

MILESTONE_TITLE="0.23 - Second alpha preparation"

ensure_milestone() {
  if gh api repos/:owner/:repo/milestones --jq ".[] | select(.title == \"${MILESTONE_TITLE}\") | .title" | grep -q "${MILESTONE_TITLE}"; then
    echo "Milestone already exists: ${MILESTONE_TITLE}"
  else
    echo "Creating milestone: ${MILESTONE_TITLE}"
    gh api repos/:owner/:repo/milestones \
      -f title="${MILESTONE_TITLE}" \
      -f description="Prepare the next public alpha release after UI/UX fixes, Doctrine improvements, XLSX exports, frontend tests and documentation overhaul."
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

create_issue "Run second alpha smoke test" "type: tests,priority: high,ai-ready" "Validate current develop in the fresh Symfony smoke app: array provider, Doctrine provider, UI/UX, row/global actions, modal confirmation, header filters, split layout, CSV/XLSX exports, frontend assets and Bootstrap/Stimulus integration. Record findings."
create_issue "Resolve second alpha blockers" "type: bug,priority: high,ai-ready" "Resolve release-blocking issues discovered during the second alpha smoke test. Track non-blocking findings separately. QA must remain green."
create_issue "Review Composer and Packagist metadata" "type: chore,priority: medium,ai-ready" "Review composer.json and Packagist-facing metadata: package description, keywords, PHP/Symfony/Doctrine constraints, OpenSpout suggestion, dev dependencies, autoload and package display information."
create_issue "Prepare changelog for second alpha" "type: docs,priority: high,ai-ready" "Review CHANGELOG.md Unreleased section, ensure all post-0.1 changes are listed accurately, decide next release tag, and verify release notes extraction."
create_issue "Review go-no-go for second alpha tag" "type: docs,priority: high,ai-ready" "Record go/no-go decision for second alpha: QA, frontend tests, CI matrix, smoke test, docs, changelog, known limitations and final tag name."
create_issue "Tag and publish second alpha" "type: release,priority: high,ai-ready" "If go/no-go is approved, merge develop into main, tag the release, push tag, verify GitHub Release, verify Packagist update, and optionally test installation from Packagist."
create_issue "Update roadmap after second alpha" "type: docs,priority: medium,ai-ready" "Update roadmap after second alpha release: mark milestone complete, clarify next development direction, and ensure completed UI/UX, Doctrine, XLSX and documentation work is represented."

echo "Second alpha preparation issues created successfully."
