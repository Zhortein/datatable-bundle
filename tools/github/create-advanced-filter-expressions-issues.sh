#!/usr/bin/env bash

set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI is required. Install it first: https://cli.github.com/"
  exit 1
fi

gh auth status >/dev/null

MILESTONE_TITLE="0.26 - Advanced filter expressions"

ensure_milestone() {
  if gh api repos/:owner/:repo/milestones --jq ".[] | select(.title == \"${MILESTONE_TITLE}\") | .title" | grep -q "${MILESTONE_TITLE}"; then
    echo "Milestone already exists: ${MILESTONE_TITLE}"
  else
    echo "Creating milestone: ${MILESTONE_TITLE}"
    gh api repos/:owner/:repo/milestones \
      -f title="${MILESTONE_TITLE}" \
      -f description="Add safe advanced filter expressions / search builder: backend model, Bootstrap UI, Stimulus state, Array/Doctrine provider support, export compatibility and documentation."
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

create_issue "Design advanced filter expression model" "type: architecture,area: filters,priority: high,ai-ready" \
"## Objective

Design the backend-controlled advanced filter expression model before implementation.

## Context

The previous DataTables.net implementation had a SearchBuilder concept:
- fields could opt in/out of the search builder;
- JS column definitions exposed a search-builder type;
- backend code parsed a searchBuilder payload and translated it to Doctrine conditions.

The new bundle must provide the same business capability without DataTables.net and without exposing Doctrine QueryBuilder, DQL, SQL or arbitrary expressions to the frontend.

## Scope

- Define public terminology:
  - advanced filter expression;
  - condition;
  - group;
  - logic operator;
  - comparison operator.
- Define the first payload shape.
- Define supported field types:
  - string/text;
  - number;
  - boolean;
  - date/datetime;
  - choice.
- Define first-version operators:
  - equals;
  - not equals;
  - contains;
  - not contains;
  - starts with;
  - ends with;
  - greater than;
  - greater than or equals;
  - less than;
  - less than or equals;
  - between;
  - is null;
  - is not null;
  - in;
  - not in.
- Define logic:
  - AND;
  - OR.
- Define nesting policy:
  - one root group;
  - nested groups only if safe;
  - max depth.
- Define security boundaries:
  - no arbitrary DQL;
  - no arbitrary SQL;
  - no frontend-provided field paths outside declared filterable fields;
  - no frontend-provided join expressions;
  - parameters must always be bound.
- Define provider mapping expectations:
  - Array provider;
  - Doctrine provider.
- Define out-of-scope items:
  - saved filters;
  - persisted user presets;
  - async filtering;
  - custom widgets;
  - collection-valued association filtering;
  - arbitrary expression language.

## Acceptance criteria

- [ ] Decision document exists under docs/decisions.
- [ ] Payload shape is documented.
- [ ] Supported operators are documented.
- [ ] Security boundaries are explicit.
- [ ] Public wording avoids exposing Doctrine internals.
- [ ] No runtime code is implemented unless strictly necessary.
- [ ] QA passes."

create_issue "Implement advanced filter expression value objects" "type: feature,area: filters,priority: high,ai-ready" \
"## Objective

Implement the backend model for advanced filter expressions.

## Scope

- Add enums/value objects for:
  - logic operator;
  - comparison operator;
  - condition;
  - group;
  - expression.
- Use a namespace consistent with the bundle, for example:
  - Zhortein\\DatatableBundle\\Filter\\Expression
- Prefer immutable PHP objects where practical.
- Supported operators must match the decision document.
- Add validation for:
  - empty field;
  - empty operator;
  - invalid operator;
  - malformed group;
  - unsupported value shape.
- Add max-depth handling if defined in the decision.
- Add unit tests.

## Out of scope

- HTTP request parsing.
- Twig/UI rendering.
- Provider application.
- Export behavior.

## Acceptance criteria

- [ ] Expression value objects exist.
- [ ] Logic operator enum exists.
- [ ] Comparison operator enum exists.
- [ ] Invalid expressions fail clearly.
- [ ] Unit tests cover valid and invalid expressions.
- [ ] Existing filters still work.
- [ ] Frontend tests pass.
- [ ] QA passes."

create_issue "Declare advanced-filterable fields" "type: feature,area: definition,area: filters,priority: high,ai-ready" \
"## Objective

Allow datatable definitions to declare which fields are available in the advanced filter builder.

## Context

The previous implementation had an inSearchBuilder flag on fields. The new bundle needs an equivalent concept in its own definition model, not tied to DataTables.net.

## Scope

- Add advanced-filter metadata to the current bundle definition model.
- Decide whether this belongs on:
  - column definitions;
  - user filter definitions;
  - a dedicated advanced filter field definition.
- Prefer backend-explicit configuration.
- Include metadata:
  - field name;
  - label;
  - type;
  - allowed operators;
  - optional choices for choice fields.
- Provide sensible defaults only if consistent with current design.
- Add methods on DatatableDefinition if needed:
  - addAdvancedFilterField(...);
  - getAdvancedFilterFields().
- Add unit tests.

## Out of scope

- UI rendering.
- Request parsing.
- Provider application.
- Frontend-only fields.

## Acceptance criteria

- [ ] Advanced-filterable fields can be declared.
- [ ] Allowed operators can be controlled.
- [ ] Field labels/types are available for rendering.
- [ ] Unit tests cover declarations and defaults.
- [ ] Existing filters and columns still work.
- [ ] QA passes."

create_issue "Normalize advanced filter expression request payload" "type: feature,area: request,area: filters,priority: high,ai-ready" \
"## Objective

Parse and normalize advanced filter expression payloads into the backend expression model.

## Scope

- Choose the request key, for example:
  - advancedFilters;
  - filterExpression.
- Parse payload from Ajax requests.
- Normalize it into expression value objects.
- Validate:
  - field exists in allowed advanced filter fields;
  - operator is allowed for that field;
  - value shape matches operator/type;
  - group logic is valid;
  - nesting depth is valid.
- Fail safely:
  - malformed advanced filters must not create unsafe queries;
  - define whether invalid payloads are ignored or returned as request errors.
- Add tests around DatatableRequest / request factory according to the current architecture.

## Important constraints

- Existing toolbar/header filters must keep working.
- Simple search must keep working.
- Sorting/pagination/export state must keep working.
- Do not apply provider logic yet.

## Acceptance criteria

- [ ] Valid payload is normalized.
- [ ] Invalid payload is rejected or ignored safely according to documented behavior.
- [ ] Request object exposes advanced filter expression.
- [ ] Existing request behavior is preserved.
- [ ] Tests pass.
- [ ] QA passes."

create_issue "Render advanced filter builder UI" "type: feature,area: twig,area: bootstrap,area: filters,priority: high,ai-ready" \
"## Objective

Render an optional Bootstrap-first advanced filter builder UI.

## Scope

- Add an option to enable advanced filters/search builder, for example:
  - advancedFilters: true;
  - or searchBuilder: true.
- Render a Bootstrap panel/dropdown/offcanvas-friendly block.
- Render:
  - field selector;
  - operator selector;
  - value input;
  - add condition button;
  - remove condition button;
  - clear advanced filters button;
  - logic selector AND/OR.
- Use backend-declared advanced filter fields only.
- Render operators based on field type.
- Keep simple toolbar/header filters unchanged.
- Use translations.
- Add renderer tests.

## Out of scope

- Third-party JS widgets.
- jQuery.
- Select2.
- Datepicker dependency.
- Nested group drag/drop.
- Full SearchBuilder parity.

## Acceptance criteria

- [ ] Advanced filter UI is opt-in.
- [ ] Available fields are backend-defined.
- [ ] Operators are type-aware.
- [ ] Simple filters remain unchanged.
- [ ] Renderer tests cover output.
- [ ] QA passes."

create_issue "Implement Stimulus advanced filter builder state" "type: feature,area: stimulus,area: filters,priority: high,ai-ready" \
"## Objective

Manage advanced filter builder state in the existing vanilla Stimulus controller.

## Scope

- Add/remove conditions.
- Change field.
- Update operator choices when field changes.
- Change operator.
- Manage value input.
- Serialize expression payload into Ajax requests.
- Refresh table when applying advanced filters.
- Clear advanced filters.
- Keep simple filters/header filters working.
- Add frontend tests.

## Important constraints

- JavaScript must remain vanilla.
- Do not add jQuery.
- Do not add third-party widgets.
- Do not break pagination/sorting/export URL generation.
- Do not persist filters yet.

## Acceptance criteria

- [ ] Conditions can be added/removed.
- [ ] Expression payload is serialized.
- [ ] Ajax refresh includes advanced filter payload.
- [ ] Clear advanced filters works.
- [ ] Existing filters still serialize.
- [ ] Frontend tests cover core interactions.
- [ ] Frontend tests pass.
- [ ] QA passes."

create_issue "Apply advanced filter expressions in Array provider" "type: feature,area: provider,area: filters,priority: high,ai-ready" \
"## Objective

Evaluate advanced filter expressions against in-memory rows in the Array provider.

## Scope

- Add an expression evaluator for array rows.
- Support:
  - AND;
  - OR;
  - equals;
  - not equals;
  - contains;
  - not contains;
  - starts with;
  - ends with;
  - greater/less comparisons;
  - between;
  - is null;
  - is not null;
  - in;
  - not in.
- Implement type-aware behavior for:
  - strings;
  - numbers;
  - booleans;
  - dates if currently supported in the Array provider.
- Keep existing simple filters/search/sort/pagination working.
- Add unit/functional tests.

## Out of scope

- Doctrine provider support.
- Custom expression callbacks unless explicitly designed.
- Saved presets.

## Acceptance criteria

- [ ] Array provider applies expression filters.
- [ ] AND works.
- [ ] OR works.
- [ ] Type-aware operators work.
- [ ] Existing filters still work.
- [ ] Tests cover combinations.
- [ ] QA passes."

create_issue "Apply advanced filter expressions in Doctrine provider" "type: feature,area: doctrine,area: filters,priority: high,ai-ready" \
"## Objective

Convert validated advanced filter expressions into safe Doctrine QueryBuilder conditions.

## Scope

- Add a Doctrine expression applier service.
- Reuse existing field reference resolver / metadata resolver where possible.
- Support main entity fields.
- Support joined fields when already declared/supported.
- Support:
  - AND;
  - OR;
  - equals;
  - not equals;
  - contains;
  - not contains;
  - starts with;
  - ends with;
  - greater/less comparisons;
  - between;
  - is null;
  - is not null;
  - in;
  - not in.
- Always bind parameters.
- Generate unique parameter names.
- Apply advanced filters to both data query and count query.
- Keep permanent filters, simple filters, search, sorting and pagination working.
- Add functional Doctrine tests.

## Important constraints

- No arbitrary DQL.
- No arbitrary SQL.
- No frontend-provided field paths outside allowed fields.
- No frontend-provided join expressions.
- No collection-valued association support unless already safe and covered.
- Avoid PostgreSQL-only functions unless already abstracted or explicitly documented.
- Prefer portable Doctrine expressions where possible.

## Acceptance criteria

- [ ] Doctrine provider applies advanced expressions.
- [ ] Main fields work.
- [ ] Joined fields work where supported.
- [ ] Parameters are safely bound.
- [ ] Count query matches data filtering.
- [ ] Invalid expressions do not produce unsafe queries.
- [ ] Functional tests cover AND/OR and multiple types.
- [ ] QA passes."

create_issue "Ensure advanced filters apply to CSV and XLSX exports" "type: feature,area: export,area: filters,priority: medium,ai-ready" \
"## Objective

Ensure exports respect advanced filter expressions.

## Scope

- Verify current CSV export respects advanced filters.
- Verify current XLSX export respects advanced filters.
- Ensure current-page and full export modes behave as intended.
- Add tests if export tests already exist.
- Document limitations.

## Important constraints

- Do not change export format support.
- Do not make optional XLSX dependencies mandatory beyond current behavior.
- Do not break filename/format logic.
- Do not break visible column behavior.

## Acceptance criteria

- [ ] CSV export includes advanced filters.
- [ ] XLSX export includes advanced filters.
- [ ] Current/full export modes are covered.
- [ ] Tests pass.
- [ ] QA passes."

create_issue "Document advanced filter expressions" "type: docs,area: filters,priority: high,ai-ready" \
"## Objective

Document the advanced filter/search-builder system.

## Scope

- Add user-facing documentation.
- Add developer/reference documentation.
- Explain:
  - enabling advanced filters;
  - declaring advanced-filterable fields;
  - supported operators;
  - supported types;
  - AND/OR logic;
  - Array provider behavior;
  - Doctrine provider behavior;
  - export behavior.
- Explain security boundaries:
  - backend-defined fields only;
  - no arbitrary DQL;
  - no arbitrary SQL;
  - no arbitrary join expressions;
  - parameters are bound.
- Explain limitations:
  - no saved presets yet;
  - no persisted user filters yet;
  - no third-party widgets yet;
  - no collection-valued association support unless implemented.
- Link from README/docs index/UI docs.

## Acceptance criteria

- [ ] Docs are findable.
- [ ] Examples match current API.
- [ ] Security boundaries are explicit.
- [ ] Limitations are clear.
- [ ] QA passes."

create_issue "Smoke test advanced filter expressions" "type: tests,area: filters,priority: high,ai-ready" \
"## Objective

Validate advanced filter expressions in the fresh Symfony smoke application.

## Scope

- Enable advanced filters on at least one Array provider datatable.
- Enable advanced filters on at least one Doctrine provider datatable if the smoke app supports it.
- Test:
  - one string condition;
  - one boolean condition;
  - one numeric condition;
  - AND conditions;
  - OR conditions;
  - clear filters;
  - pagination after filtering;
  - sorting after filtering;
  - CSV export;
  - XLSX export.
- Record findings.
- Create follow-up issues for blockers.

## Acceptance criteria

- [ ] Smoke test report exists.
- [ ] Advanced filters work in browser.
- [ ] Exports respect advanced filters.
- [ ] Blockers are fixed or tracked.
- [ ] QA passes."

create_issue "Update roadmap after advanced filter expressions" "type: docs,priority: medium,ai-ready" \
"## Objective

Update roadmap after milestone 0.26.

## Scope

- Mark 0.26 - Advanced filter expressions as completed.
- Summarize delivered capabilities:
  - backend expression model;
  - field declarations;
  - request normalization;
  - Bootstrap UI;
  - Stimulus serialization;
  - Array provider support;
  - Doctrine provider support;
  - export compatibility.
- List current limitations.
- Set next milestone depending on priorities:
  - 0.27 - Frontend E2E and accessibility evaluation;
  - or 0.28 - Hierarchical tables / expandable child datatables.
- Keep later ideas coherent.

## Acceptance criteria

- [ ] Roadmap updated.
- [ ] Next milestone direction is clear.
- [ ] QA passes."

echo "Advanced filter expressions milestone issues created successfully."
