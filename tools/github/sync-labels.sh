#!/usr/bin/env bash

set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI is required. Install it first: https://cli.github.com/"
  exit 1
fi

gh auth status >/dev/null

sync_label() {
  local name="$1"
  local color="$2"
  local description="$3"

  echo "Syncing label: $name"

  gh label create "$name" \
    --color "$color" \
    --description "$description" \
    --force
}

# Default labels normalization
sync_label "bug" "d73a4a" "Something is not working"
sync_label "documentation" "0075ca" "Documentation improvements"
sync_label "duplicate" "cfd3d7" "This issue or pull request already exists"
sync_label "enhancement" "a2eeef" "New feature or improvement"
sync_label "good first issue" "7057ff" "Good for a first contribution"
sync_label "help wanted" "008672" "Extra attention is needed"
sync_label "invalid" "e4e669" "This does not seem right"
sync_label "question" "d876e3" "Further information is requested"
sync_label "wontfix" "ffffff" "This will not be worked on"

# Types
sync_label "type: architecture" "5319e7" "Architecture or design decision"
sync_label "type: feature" "1d76db" "New feature"
sync_label "type: enhancement" "1d76db" "New feature"
sync_label "type: release" "0e8a16" "Release"
sync_label "type: security" "1d76db" "Release"
sync_label "type: chore" "fef2c0" "Chore"
sync_label "type: bug" "d73a4a" "Bug fix"
sync_label "type: docs" "0075ca" "Documentation task"
sync_label "type: tests" "0e8a16" "Tests or test infrastructure"
sync_label "type: ci" "fbca04" "Continuous integration task"
sync_label "type: refactor" "c2e0c6" "Refactoring without behavior change"
sync_label "type: legacy-analysis" "bfd4f2" "Analysis of legacy NC Manager implementation"

# Areas
sync_label "area: ui" "006b75" "UI"
sync_label "area: doctrine" "006b75" "Doctrine ORM provider, metadata or query logic"
sync_label "area: twig" "006b75" "Twig rendering and templates"
sync_label "area: stimulus" "006b75" "Stimulus controller and frontend behavior"
sync_label "area: bootstrap" "006b75" "Bootstrap-first UI"
sync_label "area: registry" "006b75" "Datatable service registry and discovery"
sync_label "area: configuration" "006b75" "Bundle configuration"
sync_label "area: export" "006b75" "CSV/XLSX or other export features"
sync_label "area: security" "006b75" "Security, CSRF, permissions or safe defaults"
sync_label "area: i18n" "006b75" "Translations and localization"
sync_label "area: actions" "006b75" "Actions"
sync_label "area: provider" "006b75" "PRoviders"
sync_label "area: filters" "006b75" "Filters"
sync_label "area: definition" "006b75" "Definitions"
sync_label "area: request" "006b75" "Request"

# Priorities
sync_label "priority: high" "b60205" "High priority"
sync_label "priority: medium" "fbca04" "Medium priority"
sync_label "priority: low" "0e8a16" "Low priority"

# Workflow
sync_label "ai-ready" "7057ff" "Issue is scoped enough to be handled by an AI coding agent"
sync_label "needs-review" "d876e3" "Needs human review"
sync_label "blocked" "000000" "Blocked by another task or decision"
sync_label "decision-needed" "fef2c0" "A design or product decision is needed"
sync_label "breaking-change" "d93f0b" "Introduces a breaking change"

echo "Labels synchronized successfully."

