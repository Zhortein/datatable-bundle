#!/usr/bin/env bash

set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI is required. Install it first: https://cli.github.com/"
  exit 1
fi

gh auth status >/dev/null

MILESTONE_TITLE="0.3 - Data pipeline foundation"

ensure_milestone() {
  if gh api repos/:owner/:repo/milestones --jq ".[] | select(.title == \"${MILESTONE_TITLE}\") | .title" | grep -q "${MILESTONE_TITLE}"; then
    echo "Milestone already exists: ${MILESTONE_TITLE}"
  else
    echo "Creating milestone: ${MILESTONE_TITLE}"
    gh api repos/:owner/:repo/milestones \
      -f title="${MILESTONE_TITLE}" \
      -f description="Connect definitions, typed requests, providers, results, Twig rendering and Ajax fragments into a first usable data pipeline."
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

  echo "Creating issue: $title"

  gh issue create \
    --title "$title" \
    --body-file "$tmpfile" \
    --milestone "$MILESTONE_TITLE" \
    "${label_args[@]}"

  rm -f "$tmpfile"
}

ensure_milestone

create_issue \
"Implement datatable definition factory" \
"type: feature,area: registry,priority: high,ai-ready" \
"## Objective

Implement a reusable service that builds a `DatatableDefinition` from a registered datatable name.

## Context

The Twig function and Ajax controller currently both resolve the datatable, create a definition and call `buildDatatable()`.

This logic should be centralized before the data pipeline grows.

## Scope

- Add `DatatableDefinitionFactory`.
- Resolve the datatable by name through `DatatableRegistry`.
- Create the `DatatableDefinition`.
- Call `buildDatatable()`.
- Return the completed definition.
- Replace duplicated logic in Twig extension and Ajax controller.
- Add unit and/or functional tests.

## Out of scope

- Doctrine provider implementation.
- Row rendering.
- Request parsing.
- Export support.

## Constraints

- Follow `AGENTS.md`.
- Keep the service small.
- Do not introduce application-specific logic.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Definition building is centralized.
- [ ] Twig extension delegates to the factory.
- [ ] Ajax controller delegates to the factory.
- [ ] Tests cover the factory.
- [ ] QA passes.
"

create_issue \
"Implement datatable HTTP request factory" \
"type: feature,area: configuration,priority: high,ai-ready" \
"## Objective

Convert Symfony HTTP requests into typed `DatatableRequest` objects.

## Context

Providers should not parse `Symfony\\Component\\HttpFoundation\\Request` directly.

## Scope

- Add `DatatableRequestFactory`.
- Read page, page size, search, sort field and sort direction from query/request parameters.
- Normalize defaults.
- Validate invalid inputs safely.
- Add tests.

## Out of scope

- Doctrine provider implementation.
- Ajax controller full integration.
- Frontend behavior changes.

## Constraints

- Follow `AGENTS.md`.
- Keep request parsing independent from providers.
- Do not trust arbitrary frontend data.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Symfony Request can be converted to `DatatableRequest`.
- [ ] Invalid values are handled predictably.
- [ ] Tests cover query and request payload sources.
- [ ] QA passes.
"

create_issue \
"Implement array data provider for tests and demos" \
"type: feature,area: configuration,priority: high,ai-ready" \
"## Objective

Implement a simple array-backed data provider for tests, examples and early rendering integration.

## Context

Before implementing Doctrine ORM, the bundle needs a provider that can return predictable data without a database.

## Scope

- Add an `ArrayDataProvider`.
- Support definitions carrying array rows through provider options or a dedicated definition option.
- Support pagination.
- Support simple search on scalar string values.
- Support single-column sorting.
- Return `DatatableResult`.
- Add tests.

## Out of scope

- Doctrine provider.
- Complex filters.
- Association handling.
- Export support.

## Constraints

- Follow `AGENTS.md`.
- Keep the provider intentionally simple.
- It must not become the main production provider.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Array provider supports matching definitions.
- [ ] Pagination works.
- [ ] Simple search works.
- [ ] Single-column sorting works.
- [ ] Tests cover core behavior.
- [ ] QA passes.
"

create_issue \
"Wire data provider registry into the Symfony container" \
"type: feature,area: configuration,priority: high,ai-ready" \
"## Objective

Register data providers as Symfony services and make `DataProviderRegistry` usable through dependency injection.

## Context

The provider registry currently exists as a value-level service but must be wired in the bundle container.

## Scope

- Define a data provider service tag.
- Add compiler pass or tagged locator/iterator wiring.
- Register `DataProviderRegistry`.
- Register the array provider if available.
- Add functional tests with the test kernel.

## Out of scope

- Doctrine provider implementation.
- Controller full data integration.
- Twig row rendering.

## Constraints

- Follow Symfony DI best practices.
- Do not hardcode Doctrine as the only provider.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Providers can be registered through service tags.
- [ ] `DataProviderRegistry` is available in the container.
- [ ] Functional tests cover provider registry wiring.
- [ ] QA passes.
"

create_issue \
"Implement row and cell rendering templates" \
"type: feature,area: twig,area: bootstrap,priority: high,ai-ready" \
"## Objective

Render datatable rows and cells from structured provider results.

## Context

The renderer currently renders only the shell and an empty state.

## Scope

- Add row template.
- Add cell template.
- Render rows from a `DatatableResult`.
- Keep default cell rendering simple.
- Respect visible columns.
- Respect column CSS classes.
- Add tests.

## Out of scope

- Type-specific cell templates.
- Row actions.
- Global actions.
- Doctrine provider.
- Export support.

## Constraints

- Twig-first rendering.
- Bootstrap-first markup.
- No DataTables.net.
- No jQuery.
- twigcs must pass.

## Acceptance criteria

- [ ] Rows can be rendered from `DatatableResult`.
- [ ] Cells match visible columns.
- [ ] Empty state is still rendered when result has no rows.
- [ ] Tests cover row rendering.
- [ ] QA passes.
"

create_issue \
"Implement pagination rendering from datatable result" \
"type: feature,area: twig,area: bootstrap,priority: medium,ai-ready" \
"## Objective

Render Bootstrap pagination from `DatatableResult`.

## Context

The pagination template currently renders a placeholder only.

## Scope

- Render pagination controls from result metadata.
- Include previous/next controls.
- Include current page state.
- Add data-action/data-param attributes for Stimulus.
- Keep markup accessible.
- Add tests.

## Out of scope

- Full frontend pagination behavior.
- Cursor pagination.
- Doctrine provider.

## Constraints

- Bootstrap pagination markup.
- Accessible labels.
- Vanilla Stimulus-compatible attributes.
- twigcs must pass.

## Acceptance criteria

- [ ] Pagination renders when total pages > 1.
- [ ] Pagination is omitted or empty when no pagination is needed.
- [ ] Controls contain Stimulus-compatible data parameters.
- [ ] Tests cover pagination rendering.
- [ ] QA passes.
"

create_issue \
"Connect Ajax fragments endpoint to provider and renderer" \
"type: feature,area: configuration,area: twig,priority: high,ai-ready" \
"## Objective

Make the Ajax fragments endpoint return rendered body and pagination fragments based on provider results.

## Context

The Ajax controller currently returns placeholder fragments.

## Scope

- Use `DatatableDefinitionFactory`.
- Use `DatatableRequestFactory`.
- Resolve a provider through `DataProviderRegistry`.
- Retrieve `DatatableResult`.
- Render body and pagination fragments.
- Return documented JSON payload.
- Add tests.

## Out of scope

- Doctrine provider implementation.
- Advanced frontend behavior.
- Export support.

## Constraints

- Controller remains thin.
- No DataTables.net response format.
- No application-specific routes.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Fragments endpoint uses provider results.
- [ ] JSON response contains body, pagination, summary and pagination metadata.
- [ ] Tests cover non-empty and empty data.
- [ ] QA passes.
"

create_issue \
"Connect Stimulus controller to pagination and search fragments" \
"type: feature,area: stimulus,priority: medium,ai-ready" \
"## Objective

Complete the first usable frontend interaction loop for search and pagination.

## Context

The Stimulus controller skeleton already has refresh, search, sort and pagination placeholders.

## Scope

- Ensure pagination controls trigger `goToPage`.
- Ensure search input triggers debounced refresh.
- Ensure fragment URL parameters match `DatatableRequestFactory`.
- Update documentation if needed.

## Out of scope

- Complex sorting UI.
- Column visibility.
- Batch actions.
- DataTables.net compatibility.

## Constraints

- Vanilla JavaScript only.
- No jQuery.
- No external frontend dependency.
- Keep controller small.

## Acceptance criteria

- [ ] Search refreshes fragments.
- [ ] Pagination refreshes fragments.
- [ ] Query parameter names are documented and consistent.
- [ ] No external JS dependencies are introduced.
"

create_issue \
"Document first end-to-end datatable flow" \
"type: docs,priority: medium,ai-ready" \
"## Objective

Document the first usable end-to-end flow from datatable declaration to rendered Ajax fragments.

## Context

Once the data pipeline is connected, the documentation should explain how the pieces interact.

## Scope

- Update architecture docs.
- Update basic usage docs.
- Add a flow diagram in text form.
- Document request parameters.
- Document response payload.
- Document current limitations.

## Out of scope

- Doctrine-specific documentation.
- Export documentation.
- Advanced frontend behavior documentation.

## Constraints

- No private/client-specific references.
- Keep examples generic.
- Documentation must reflect implemented behavior.

## Acceptance criteria

- [ ] Basic end-to-end flow is documented.
- [ ] Current limitations are explicit.
- [ ] README links remain accurate.
- [ ] QA passes.
"

echo "Data pipeline foundation issues created successfully."
