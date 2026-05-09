#!/usr/bin/env bash

set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI is required. Install it first: https://cli.github.com/"
  exit 1
fi

gh auth status >/dev/null

MILESTONE_TITLE="0.5 - Actions and cell rendering foundation"

ensure_milestone() {
  if gh api repos/:owner/:repo/milestones --jq ".[] | select(.title == \"${MILESTONE_TITLE}\") | .title" | grep -q "${MILESTONE_TITLE}"; then
    echo "Milestone already exists: ${MILESTONE_TITLE}"
  else
    echo "Creating milestone: ${MILESTONE_TITLE}"
    gh api repos/:owner/:repo/milestones \
      -f title="${MILESTONE_TITLE}" \
      -f description="Action rendering, route parameter resolution, CSRF-aware actions, typed cell templates and documentation."
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

Implement a small service responsible for resolving route parameters for row actions.

## Context

`ActionDefinition` already supports `routeParameters`, but rendering actions requires turning row values into route parameters.

## Scope

- Add a `RouteParametersResolver` or equivalent service.
- Resolve route parameters from row arrays.
- Support full column keys such as `e_id`.
- Support direct row keys such as `id`.
- Keep behavior predictable when a value is missing.
- Add unit tests.

## Out of scope

- URL generation.
- Twig action rendering.
- CSRF handling.
- JavaScript behavior.

## Constraints

- Follow `AGENTS.md`.
- Keep the resolver independent from Symfony routing.
- Do not introduce application-specific behavior.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Route parameters can be resolved from a row.
- [ ] Missing values are handled predictably.
- [ ] Tests cover direct and aliased keys.
- [ ] QA passes.
BODY
)"
create_issue "Implement row action route parameter resolver" "type: feature,area: twig,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Render row actions from `ActionDefinition` objects.

## Context

Datatable definitions can already declare row actions, but actions are not rendered yet.

## Scope

- Add action rendering support in the Twig renderer.
- Add Bootstrap-compatible action templates.
- Generate URLs with Symfony routing.
- Use the route parameter resolver.
- Render actions for each row.
- Add tests.

## Out of scope

- CSRF handling for non-GET methods.
- Global actions.
- Dropdown/grouped actions.
- Permission voters.

## Constraints

- Twig-first rendering.
- Bootstrap-first markup.
- No jQuery.
- No DataTables.net.
- PHPStan and twigcs must pass.

## Acceptance criteria

- [ ] Row actions render in each row.
- [ ] GET action URLs are generated.
- [ ] Route parameters are resolved from row values.
- [ ] Tests cover row action rendering.
- [ ] QA passes.
BODY
)"
create_issue "Implement row action rendering" "type: feature,area: twig,area: bootstrap,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Render global actions declared on `DatatableDefinition`.

## Context

Global actions are useful for operations such as create, import or batch actions.

## Scope

- Render global actions in the datatable toolbar.
- Generate URLs with Symfony routing.
- Use Bootstrap-compatible buttons.
- Add tests.

## Out of scope

- Batch selected-row actions.
- CSRF handling for non-GET methods.
- Permission voters.
- Dropdown action groups.

## Constraints

- Twig-first rendering.
- Bootstrap-first markup.
- No jQuery.
- No DataTables.net.
- PHPStan and twigcs must pass.

## Acceptance criteria

- [ ] Global actions render in the toolbar.
- [ ] GET action URLs are generated.
- [ ] Tests cover global action rendering.
- [ ] QA passes.
BODY
)"
create_issue "Implement global action rendering" "type: feature,area: twig,area: bootstrap,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Add CSRF-aware rendering for non-GET actions.

## Context

Actions using POST, PUT, PATCH or DELETE must be safe by default.

## Scope

- Add CSRF token support for non-GET actions.
- Render non-GET actions as forms or safe button markup.
- Keep GET actions as links.
- Add tests.

## Out of scope

- JavaScript confirmation modals.
- Permission voters.
- Batch actions.
- Controller handling of submitted actions.

## Constraints

- Follow Symfony security best practices.
- Do not render unsafe destructive links.
- Keep dependencies optional where possible.
- PHPStan and twigcs must pass.

## Acceptance criteria

- [ ] GET actions render as links.
- [ ] Non-GET actions render as forms or safe buttons.
- [ ] CSRF tokens are included when CSRF manager is available.
- [ ] Tests cover GET and non-GET action rendering.
- [ ] QA passes.
BODY
)"
create_issue "Implement CSRF-aware action rendering" "type: feature,area: security,area: twig,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Introduce type-specific cell templates for common datatable cell types.

## Context

The renderer currently uses a generic cell template.

## Scope

- Add default cell templates for:
  - string;
  - numeric;
  - boolean;
  - datetime;
  - array/json;
  - enum placeholder.
- Use the column `type` option when provided.
- Keep a safe default fallback.
- Add tests.

## Out of scope

- Doctrine automatic type-to-template integration.
- Custom user template resolution.
- Rich enum rendering with badges/icons.

## Constraints

- Twig-first rendering.
- Values must be escaped by default.
- Bootstrap-friendly markup.
- twigcs must pass.

## Acceptance criteria

- [ ] Type-specific templates exist.
- [ ] Renderer selects templates based on column type.
- [ ] Unknown types fall back safely.
- [ ] Tests cover each initial type.
- [ ] QA passes.
BODY
)"
create_issue "Implement typed cell templates" "type: feature,area: twig,area: bootstrap,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Connect Doctrine type guessing to default column type metadata.

## Context

`DoctrineFieldTypeGuesser` can infer cell types, but column definitions do not yet receive those inferred types automatically.

## Scope

- Add a service to enrich Doctrine-backed datatable definitions.
- Use `DoctrineFieldTypeGuesser` for columns without explicit type.
- Preserve explicit column type when provided.
- Add functional tests.

## Out of scope

- Association traversal.
- Custom joins.
- Advanced field mapping.
- User-defined type guessers.

## Constraints

- Keep Doctrine-specific behavior isolated.
- Do not force Doctrine for non-Doctrine providers.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Doctrine-backed columns can receive inferred cell types.
- [ ] Explicit column types are preserved.
- [ ] Non-Doctrine definitions are unaffected.
- [ ] Tests cover type enrichment.
- [ ] QA passes.
BODY
)"
create_issue "Connect Doctrine type guessing to column metadata" "type: feature,area: doctrine,area: twig,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Allow datatable definitions to specify custom Twig templates per column.

## Context

`ColumnDefinition` already exposes a `template` option, but the renderer does not use it yet.

## Scope

- Use `ColumnDefinition::getTemplate()` when provided.
- Fall back to type-specific templates.
- Fall back to default template.
- Add tests.

## Out of scope

- Template existence validation at compile time.
- Template namespace configuration.
- User-defined template resolvers.

## Constraints

- Custom templates must still receive a safe and documented context.
- Twig errors should remain explicit.
- twigcs must pass.

## Acceptance criteria

- [ ] Custom column templates are used when configured.
- [ ] Default templates are still used otherwise.
- [ ] Template context is documented.
- [ ] Tests cover custom template rendering.
- [ ] QA passes.
BODY
)"
create_issue "Implement custom column template rendering" "type: feature,area: twig,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Document row actions, global actions and typed cell rendering.

## Context

After action rendering and typed cells are implemented, users need clear documentation.

## Scope

- Add or update documentation for row actions.
- Add or update documentation for global actions.
- Document GET vs non-GET action behavior.
- Document typed cell templates.
- Document custom column templates.
- Update README and docs index links if needed.

## Out of scope

- Export documentation.
- Advanced security voters.
- Batch actions.

## Constraints

- Documentation must match implemented behavior.
- No private/client-specific references.
- Examples must remain generic.
- QA passes.

## Acceptance criteria

- [ ] Actions are documented.
- [ ] Typed cells are documented.
- [ ] Custom templates are documented.
- [ ] README/docs index remain accurate.
- [ ] QA passes.
BODY
)"
create_issue "Document actions and typed cell rendering" "type: docs,area: twig,priority: medium,ai-ready" "$body"
rm -f "$body"

echo "Actions and cell rendering issues created successfully."
