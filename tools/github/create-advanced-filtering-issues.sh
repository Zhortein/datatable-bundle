#!/usr/bin/env bash

set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI is required. Install it first: https://cli.github.com/"
  exit 1
fi

gh auth status >/dev/null

MILESTONE_TITLE="0.9 - Advanced filtering foundation"

ensure_milestone() {
  if gh api repos/:owner/:repo/milestones --jq ".[] | select(.title == \"${MILESTONE_TITLE}\") | .title" | grep -q "${MILESTONE_TITLE}"; then
    echo "Milestone already exists: ${MILESTONE_TITLE}"
  else
    echo "Creating milestone: ${MILESTONE_TITLE}"
    gh api repos/:owner/:repo/milestones \
      -f title="${MILESTONE_TITLE}" \
      -f description="Explicit user-facing filters: definitions, request normalization, Twig rendering, Stimulus refresh and Doctrine application."
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

Add explicit value objects and enums to declare user-facing datatable filters.

## Context

Permanent filters already exist, but they are backend-only and not controlled by the frontend.

This milestone introduces explicit user-facing filters that can be rendered in the toolbar and sent to providers.

## Scope

- Add a `FilterType` enum.
- Add a `FilterDefinition`-like value object dedicated to user filters if needed.
- Add `DatatableDefinition::addFilter()`.
- Add `DatatableDefinition::getFilters()`.
- Support initial filter metadata:
  - name;
  - field;
  - label;
  - type;
  - options/choices;
  - placeholder;
  - required flag.
- Add unit tests.

## Out of scope

- Doctrine filter application.
- Twig rendering.
- Stimulus behavior.
- SearchBuilder-style nested expressions.

## Constraints

- Follow `AGENTS.md`.
- Keep filters explicit and typed.
- Do not allow arbitrary frontend DQL.
- PHPStan max must pass.

## Acceptance criteria

- [ ] User-facing filter definition object exists.
- [ ] Datatable definitions can declare filters.
- [ ] Filter types are represented by an enum.
- [ ] Tests cover filter declaration.
- [ ] QA passes.
BODY
)"
create_issue "Implement user filter definition objects" "type: feature,area: configuration,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Normalize user-facing filter values from Symfony HTTP requests into a typed request object.

## Context

`DatatableRequest` currently stores pagination, search and sorting. It needs a typed representation of user filter values.

## Scope

- Extend `DatatableRequest` to expose filter values.
- Extend `DatatableRequestFactory` to parse filter parameters.
- Define a stable query parameter structure.
- Normalize empty filter values.
- Add tests.

## Out of scope

- Doctrine filter application.
- Twig filter rendering.
- Stimulus behavior.

## Constraints

- Keep request parsing independent from providers.
- Do not trust arbitrary frontend data.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Request object exposes filter values.
- [ ] Request factory parses filters.
- [ ] Empty values are normalized.
- [ ] Tests cover GET and POST payloads.
- [ ] QA passes.
BODY
)"
create_issue "Implement filter request normalization" "type: feature,area: configuration,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Render declared filters in the datatable toolbar.

## Context

Filters should be visible and usable from the generated Bootstrap UI.

## Scope

- Render text filters.
- Render choice filters.
- Render boolean filters.
- Render date filters if the filter type exists.
- Use Bootstrap form controls.
- Add accessible labels.
- Add tests.

## Out of scope

- Doctrine filter application.
- Advanced nested filters.
- Select2 or third-party widgets.
- Datepicker JS dependency.

## Constraints

- Bootstrap-first.
- Twig-first.
- No jQuery.
- No DataTables.net.
- TwigCS must pass.

## Acceptance criteria

- [ ] Declared filters render in the toolbar.
- [ ] Controls use stable names/ids.
- [ ] Labels are accessible.
- [ ] Tests cover text, choice and boolean filters.
- [ ] QA passes.
BODY
)"
create_issue "Implement filter toolbar rendering" "type: feature,area: twig,area: bootstrap,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Wire filter controls to the Stimulus controller so changing filters refreshes datatable fragments.

## Context

The Stimulus controller already refreshes search, pagination and sorting.

Filter controls need to contribute query parameters to fragment requests.

## Scope

- Add filter targets or query selector strategy.
- Serialize filter values into request parameters.
- Reset page to 1 when filters change.
- Refresh fragments on filter changes.
- Add documentation notes.

## Out of scope

- Frontend test suite.
- Advanced widgets.
- Debounced complex filter builder.

## Constraints

- Vanilla JavaScript only.
- No jQuery.
- Keep controller small.
- Parameter names must match `DatatableRequestFactory`.

## Acceptance criteria

- [ ] Filter changes refresh fragments.
- [ ] Filter values are included in the Ajax request.
- [ ] Page resets to 1 on filter change.
- [ ] No frontend dependencies introduced.
BODY
)"
create_issue "Connect filter controls to Stimulus refresh" "type: feature,area: stimulus,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Apply user-facing filters in the Doctrine ORM provider.

## Context

Declared filters and request filter values must be translated into safe Doctrine QueryBuilder expressions.

## Scope

- Match incoming filter values against declared filters only.
- Apply text filters to string-like fields.
- Apply choice filters.
- Apply boolean filters.
- Apply date filters if supported by filter definitions.
- Bind all values as parameters.
- Apply filters to row queries and filtered counts.
- Add functional tests on main entity fields.

## Out of scope

- Joined field filters.
- Nested expression groups.
- SearchBuilder-like UI.
- Raw DQL expressions.

## Constraints

- Do not trust arbitrary frontend field names.
- Use declared filters only.
- Bind values safely.
- Avoid database-specific behavior unless explicit.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Doctrine provider applies declared user filters.
- [ ] Filtered counts are correct.
- [ ] Unknown filter input is ignored safely.
- [ ] Functional tests cover text, choice and boolean filters.
- [ ] QA passes.
BODY
)"
create_issue "Apply user filters in Doctrine provider" "type: feature,area: doctrine,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Support user-facing filters on fields from explicitly joined Doctrine associations.

## Context

Doctrine joins are implemented. User filters should also support joined fields when the join alias is explicitly declared.

## Scope

- Apply declared filters on joined aliases.
- Validate joined alias metadata.
- Support string-like joined fields.
- Support boolean joined fields.
- Add functional tests.

## Out of scope

- Collection filters.
- Deep joins.
- Custom non-mapped joins.
- Nested expressions.

## Constraints

- Explicit joins only.
- Declared filters only.
- Safe parameter binding.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Filters can target joined fields.
- [ ] Unknown joined aliases fail clearly or are ignored safely.
- [ ] Counts remain consistent.
- [ ] Functional tests cover joined filters.
- [ ] QA passes.
BODY
)"
create_issue "Support user filters on joined Doctrine fields" "type: feature,area: doctrine,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Render active filter state and provide a clear/reset filters action.

## Context

Once filters exist, users need to understand when a datatable is filtered and clear all filters easily.

## Scope

- Render active filter summary.
- Add a clear filters button.
- Wire clear filters action to Stimulus.
- Reset filter controls to empty/default values.
- Reset page to 1.
- Refresh fragments.
- Add tests where practical.

## Out of scope

- Saved filter presets.
- User preference persistence.
- Advanced filter chips UI.

## Constraints

- Bootstrap-first.
- Accessible button labels.
- Vanilla JavaScript only.
- QA passes.

## Acceptance criteria

- [ ] Active filters are visible.
- [ ] Clear filters action is available.
- [ ] Clear filters resets controls and refreshes data.
- [ ] Documentation updated.
- [ ] QA passes.
BODY
)"
create_issue "Implement active filter summary and clear filters action" "type: feature,area: twig,area: stimulus,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Document user-facing filters and current limitations.

## Context

After filter declaration, rendering and provider application are implemented, users need clear documentation.

## Scope

- Add `docs/filters.md`.
- Document filter declaration.
- Document filter request parameters.
- Document text, choice, boolean and date filters as implemented.
- Document Doctrine support.
- Document joined field filter support if implemented.
- Link from README, docs index and basic usage.

## Out of scope

- SearchBuilder documentation.
- Saved presets.
- User preference persistence.

## Constraints

- Documentation must match implemented behavior.
- Examples must be generic.
- No private/client-specific references.
- QA passes.

## Acceptance criteria

- [ ] Filter documentation exists.
- [ ] README/docs index links are updated.
- [ ] Current limitations are explicit.
- [ ] QA passes.
BODY
)"
create_issue "Document user-facing filters" "type: docs,area: doctrine,area: twig,priority: medium,ai-ready" "$body"
rm -f "$body"

echo "Advanced filtering issues created successfully."
