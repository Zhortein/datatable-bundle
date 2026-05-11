#!/usr/bin/env bash

set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI is required. Install it first: https://cli.github.com/"
  exit 1
fi

gh auth status >/dev/null

MILESTONE_TITLE="0.8 - Doctrine associations and joins"

ensure_milestone() {
  if gh api repos/:owner/:repo/milestones --jq ".[] | select(.title == \"${MILESTONE_TITLE}\") | .title" | grep -q "${MILESTONE_TITLE}"; then
    echo "Milestone already exists: ${MILESTONE_TITLE}"
  else
    echo "Creating milestone: ${MILESTONE_TITLE}"
    gh api repos/:owner/:repo/milestones \
      -f title="${MILESTONE_TITLE}" \
      -f description="Explicit Doctrine joins and association fields support for display, search, sorting and permanent filters."
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

Add explicit value objects and enums to describe Doctrine joins in a datatable definition.

## Context

The Doctrine provider currently supports only simple scalar fields on the main alias `e`.

Before supporting associated entity fields, joins must be explicit and typed.

## Scope

- Add a `JoinType` enum.
- Add a `JoinDefinition` value object.
- Add `DatatableDefinition::addJoin()`.
- Add `DatatableDefinition::getJoins()`.
- Add unit tests.
- Document the first join API direction.

## Out of scope

- Applying joins in Doctrine queries.
- Automatic association traversal.
- Custom non-mapped joins.
- ManyToMany or collection aggregation.

## Constraints

- Follow `AGENTS.md`.
- Keep joins explicit.
- Prefer safe Doctrine association joins such as `e.customer`.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Join value object exists.
- [ ] Join type enum exists.
- [ ] Datatable definitions can declare joins.
- [ ] Tests cover join declaration.
- [ ] QA passes.
BODY
)"
create_issue "Implement Doctrine join definition objects" "type: feature,area: doctrine,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Extend the Doctrine functional test foundation with associated entities.

## Context

Association support needs real Doctrine metadata and real SQL joins in functional tests.

## Scope

- Add a simple associated entity fixture, for example `DoctrineOrganization`.
- Link `DoctrineUser` to the associated entity through a ManyToOne relation.
- Update schema creation metadata in functional tests.
- Add helper fixture methods if needed.
- Add a functional test proving the association can be persisted and fetched.

## Out of scope

- Doctrine provider join implementation.
- ManyToMany relations.
- Collection aggregation.
- Custom joins.

## Constraints

- Keep fixtures minimal.
- Use attributes mapping.
- Use SQLite in-memory only.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Associated test entity exists.
- [ ] DoctrineUser has a ManyToOne association.
- [ ] Functional tests can persist and fetch associated data.
- [ ] Existing tests remain green.
- [ ] QA passes.
BODY
)"
create_issue "Extend Doctrine test fixtures with associations" "type: tests,area: doctrine,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Apply explicitly declared Doctrine joins in `DoctrineOrmDataProvider`.

## Context

Once joins are declared in `DatatableDefinition`, the Doctrine provider must apply them to row queries and count queries.

## Scope

- Read joins from `DatatableDefinition`.
- Apply left/inner joins to row queries.
- Apply joins to count queries only when needed.
- Validate aliases.
- Prevent duplicate aliases.
- Add functional tests.

## Out of scope

- Automatic join discovery.
- Non-mapped joins.
- ManyToMany aggregation.
- Custom raw DQL conditions.

## Constraints

- Only explicit joins.
- Do not trust frontend input.
- Keep query building predictable.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Doctrine provider applies declared joins.
- [ ] Duplicate aliases are rejected.
- [ ] Invalid joins fail clearly.
- [ ] Functional tests cover joined query execution.
- [ ] QA passes.
BODY
)"
create_issue "Apply explicit Doctrine joins in provider" "type: feature,area: doctrine,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Allow Doctrine provider to select and render fields from explicitly joined entities.

## Context

After joins are applied, datatables should be able to declare columns such as `organization.name`.

## Scope

- Support joined alias field selection.
- Generate stable result aliases, for example `organization_name`.
- Ensure renderer can read joined field values.
- Add functional tests.

## Out of scope

- Deep automatic traversal.
- Collection fields.
- Custom field expressions.
- Aggregate fields.

## Constraints

- Joined aliases must be explicitly declared.
- Unknown aliases must fail clearly or be ignored safely according to provider design.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Joined fields can be selected.
- [ ] Joined field values are returned in `DatatableResult`.
- [ ] Renderer displays joined values.
- [ ] Tests cover joined field display.
- [ ] QA passes.
BODY
)"
create_issue "Support joined entity columns in Doctrine provider" "type: feature,area: doctrine,area: twig,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Support sorting on fields from explicitly joined Doctrine associations.

## Context

The Doctrine provider already supports single-column sorting on main entity fields.

Joined fields should also be sortable when the column is declared sortable and the join is explicit.

## Scope

- Validate sort field against declared columns.
- Support sort fields on declared join aliases.
- Add functional tests for ascending and descending sort on joined fields.

## Out of scope

- Multi-column sorting.
- Sorting on undeclared joins.
- Sorting on collections.
- Custom sort expressions.

## Constraints

- Do not trust arbitrary frontend sort fields.
- Only declared sortable columns.
- Only explicit joins.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Joined fields can be sorted.
- [ ] Unknown aliases are ignored or rejected safely.
- [ ] Non-sortable joined columns are ignored or rejected safely.
- [ ] Functional tests cover sorting.
- [ ] QA passes.
BODY
)"
create_issue "Support sorting on joined Doctrine fields" "type: feature,area: doctrine,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Support global search on fields from explicitly joined Doctrine associations.

## Context

The Doctrine provider already supports simple global search on main entity fields.

Joined fields should also be searchable when declared searchable and the join is explicit.

## Scope

- Detect metadata for joined aliases.
- Apply search expressions to joined fields.
- Support string-like joined fields.
- Support numeric joined fields when safe.
- Add functional tests.

## Out of scope

- Search on collections.
- JSON search.
- PostgreSQL-only `ILIKE`.
- Search builder expressions.

## Constraints

- Portable Doctrine/DQL behavior.
- Only declared searchable columns.
- Only explicit joins.
- Bind all values safely.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Joined string fields can be searched.
- [ ] Non-searchable joined columns are ignored.
- [ ] Permanent filters and search still combine correctly.
- [ ] Functional tests cover search.
- [ ] QA passes.
BODY
)"
create_issue "Support search on joined Doctrine fields" "type: feature,area: doctrine,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Support permanent filters on fields from explicitly joined Doctrine associations.

## Context

Permanent filters currently support main alias fields.

Business datatables often need backend filters on associated entities.

## Scope

- Allow permanent filters such as `organization.enabled`.
- Validate alias against declared joins.
- Apply filters safely with bound parameters.
- Add functional tests.

## Out of scope

- Frontend filters.
- Non-mapped joins.
- Collection filters.
- Raw DQL expressions.

## Constraints

- Only explicit join aliases.
- Safe parameter binding.
- Counts and rows must be consistent.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Permanent filters work on joined fields.
- [ ] Counts reflect joined permanent filters.
- [ ] Tests cover joined filters.
- [ ] QA passes.
BODY
)"
create_issue "Support permanent filters on joined Doctrine fields" "type: feature,area: doctrine,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Document Doctrine joins and association fields.

## Context

Once explicit join support exists, users need clear documentation and examples.

## Scope

- Update `docs/doctrine-provider.md`.
- Add examples for declaring joins.
- Add examples for joined columns.
- Document sorting/search/filter support on joined fields.
- Document limitations.

## Out of scope

- Advanced filters documentation.
- ManyToMany aggregation documentation.
- Custom join expression documentation if not implemented.

## Constraints

- Documentation must match implemented behavior.
- Keep examples generic.
- No private/client-specific references.
- QA passes.

## Acceptance criteria

- [ ] Doctrine joins are documented.
- [ ] Joined columns are documented.
- [ ] Limitations are explicit.
- [ ] README/docs links remain accurate.
- [ ] QA passes.
BODY
)"
create_issue "Document Doctrine joins and association fields" "type: docs,area: doctrine,priority: medium,ai-ready" "$body"
rm -f "$body"

echo "Doctrine associations and joins issues created successfully."
