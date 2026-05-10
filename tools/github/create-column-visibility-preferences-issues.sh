#!/usr/bin/env bash

set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI is required. Install it first: https://cli.github.com/"
  exit 1
fi

gh auth status >/dev/null

MILESTONE_TITLE="0.10 - Column visibility and user preferences"

ensure_milestone() {
  if gh api repos/:owner/:repo/milestones --jq ".[] | select(.title == \"${MILESTONE_TITLE}\") | .title" | grep -q "${MILESTONE_TITLE}"; then
    echo "Milestone already exists: ${MILESTONE_TITLE}"
  else
    echo "Creating milestone: ${MILESTONE_TITLE}"
    gh api repos/:owner/:repo/milestones \
      -f title="${MILESTONE_TITLE}" \
      -f description="Column visibility controls and preference extension points without coupling the bundle to a user storage model."
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

Introduce explicit column visibility metadata and runtime state support.

## Context

Columns already have a `visible` flag, but there is no dedicated model for user-driven visibility changes.

## Scope

- Review current `ColumnDefinition::isVisible()`.
- Add a stable public name/key for each column if needed.
- Add runtime visible columns support through render options or request state.
- Ensure hidden columns are not rendered.
- Ensure hidden columns are not selected when provider supports visibility-aware loading.
- Add tests.

## Out of scope

- UI toggle rendering.
- Preference persistence.
- User-specific storage.
- Doctrine query optimization beyond current provider behavior.

## Constraints

- Do not break existing column API.
- Keep hidden column behavior predictable.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Column visibility can be controlled at runtime.
- [ ] Hidden columns are not rendered.
- [ ] Tests cover definition-level and runtime visibility.
- [ ] QA passes.
BODY
)"
create_issue "Implement runtime column visibility state" "type: feature,area: twig,area: configuration,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Render a Bootstrap-compatible column visibility control.

## Context

Users need a way to show/hide optional columns.

## Scope

- Render a column visibility dropdown or checklist in the toolbar.
- Include visible columns and hidden-by-default columns.
- Use stable control names.
- Add accessible labels.
- Add tests.

## Out of scope

- Preference persistence.
- Drag-and-drop column order.
- Advanced dropdown UI dependency.
- DataTables.net column visibility plugin.

## Constraints

- Bootstrap-first markup.
- Vanilla JavaScript only.
- No jQuery.
- TwigCS must pass.

## Acceptance criteria

- [ ] Column visibility controls render in the toolbar.
- [ ] Controls have accessible labels.
- [ ] Hidden-by-default columns can be represented.
- [ ] Tests cover rendering.
- [ ] QA passes.
BODY
)"
create_issue "Render column visibility controls" "type: feature,area: twig,area: bootstrap,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Wire column visibility controls to the Stimulus controller.

## Context

The toolbar can render column visibility controls, but changing them must refresh the rendered fragments.

## Scope

- Add Stimulus action for column visibility changes.
- Serialize visible/hidden column state into Ajax requests.
- Reset page to 1 when visibility changes if needed.
- Refresh fragments.
- Add documentation notes.

## Out of scope

- Persisting preferences.
- Column reordering.
- Drag-and-drop.
- Frontend test suite.

## Constraints

- Vanilla JavaScript only.
- No jQuery.
- Keep controller small.
- Request parameter names must be documented.

## Acceptance criteria

- [ ] Column visibility changes trigger refresh.
- [ ] Visibility state is included in fragment requests.
- [ ] Hidden columns are not rendered after refresh.
- [ ] QA passes.
BODY
)"
create_issue "Connect column visibility controls to Stimulus refresh" "type: feature,area: stimulus,area: twig,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Normalize column visibility state from HTTP requests into `DatatableRequest`.

## Context

The backend must receive column visibility state from the frontend in a typed and safe way.

## Scope

- Extend `DatatableRequest` with visible/hidden column state.
- Extend `DatatableRequestFactory` parsing.
- Define stable request parameter shape.
- Normalize invalid values.
- Add tests.

## Out of scope

- Preference persistence.
- UI rendering.
- Provider-specific column selection optimizations.

## Constraints

- Do not trust arbitrary frontend state blindly.
- Providers/renderers must still rely on declared columns.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Request object carries column visibility state.
- [ ] Request factory parses visibility parameters.
- [ ] Tests cover query and payload formats.
- [ ] QA passes.
BODY
)"
create_issue "Implement column visibility request normalization" "type: feature,area: configuration,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Introduce an extension point for datatable preferences.

## Context

The bundle should support user preferences later without coupling to a specific User entity or storage model.

## Scope

- Add `DatatablePreferenceProviderInterface` or equivalent.
- Define a small preference DTO/value object.
- Provide a null/no-op implementation.
- Wire the no-op implementation by default.
- Add tests.

## Out of scope

- Database persistence.
- User entity integration.
- Symfony security user dependency.
- Admin UI.

## Constraints

- No application-specific assumptions.
- Host applications must be able to replace the service.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Preference interface exists.
- [ ] Null implementation exists.
- [ ] Service is replaceable.
- [ ] Tests cover default behavior.
- [ ] QA passes.
BODY
)"
create_issue "Introduce datatable preference extension point" "type: feature,area: configuration,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Apply preference defaults to datatable rendering when a preference provider is available.

## Context

Once a preference extension point exists, rendering can consume it to initialize page size, visibility and sorting defaults.

## Scope

- Load preferences by datatable name.
- Merge preferences with runtime options.
- Runtime options should still take precedence.
- Add tests.

## Out of scope

- Preference persistence implementation.
- User identity handling.
- UI save/reset actions.

## Constraints

- Keep precedence rules explicit.
- Do not couple to security user.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Preferences can influence initial rendering.
- [ ] Runtime options override preferences.
- [ ] Tests cover precedence.
- [ ] QA passes.
BODY
)"
create_issue "Apply datatable preferences to rendering defaults" "type: feature,area: twig,area: configuration,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Document column visibility and preference extension points.

## Context

After column visibility and preference extension points are implemented, users need integration documentation.

## Scope

- Add `docs/preferences.md`.
- Document column visibility controls.
- Document request parameter format.
- Document preference provider interface.
- Document no-op implementation.
- Document how applications can replace the provider.
- Link README and docs index.

## Out of scope

- Full persistence example with database entities.
- Security user integration guide.
- Admin UI documentation.

## Constraints

- Documentation must match implemented behavior.
- Examples must remain generic.
- QA passes.

## Acceptance criteria

- [ ] Preference documentation exists.
- [ ] Column visibility documentation exists.
- [ ] README/docs index links are updated.
- [ ] Current limitations are explicit.
- [ ] QA passes.
BODY
)"
create_issue "Document column visibility and preferences" "type: docs,area: configuration,area: twig,priority: medium,ai-ready" "$body"
rm -f "$body"

echo "Column visibility and user preferences issues created successfully."
