#!/usr/bin/env bash

set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI is required. Install it first: https://cli.github.com/"
  exit 1
fi

gh auth status >/dev/null

MILESTONE_TITLE="0.4 - Doctrine ORM provider foundation"

ensure_milestone() {
  if gh api repos/:owner/:repo/milestones --jq ".[] | select(.title == \"${MILESTONE_TITLE}\") | .title" | grep -q "${MILESTONE_TITLE}"; then
    echo "Milestone already exists: ${MILESTONE_TITLE}"
  else
    echo "Creating milestone: ${MILESTONE_TITLE}"
    gh api repos/:owner/:repo/milestones \
      -f title="${MILESTONE_TITLE}" \
      -f description="Doctrine ORM provider foundation: metadata type guessing, test entities, pagination, search, sorting and documentation."
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

Prepare the Doctrine ORM test foundation required to implement and validate the Doctrine provider.

## Context

Doctrine ORM will be the first production data provider, but it must be tested with a real Symfony kernel and a predictable database.

## Scope

- Add Doctrine ORM test configuration.
- Add a minimal test entity.
- Add a minimal SQLite-based test setup if practical.
- Add fixtures or helper methods to create predictable test data.
- Keep current unit and functional tests green.
- Document the functional Doctrine test setup.

## Out of scope

- Doctrine provider implementation.
- Doctrine search/sort/filter logic.
- Production Doctrine configuration.

## Constraints

- Follow `AGENTS.md`.
- Keep the test setup minimal.
- Do not require an external database service.
- Prefer SQLite for functional tests.
- PHPStan max must pass.

## Acceptance criteria

- [ ] A Doctrine test entity exists.
- [ ] Functional tests can boot with Doctrine ORM available.
- [ ] Test data can be created predictably.
- [ ] Existing QA remains green.
BODY
)"
create_issue "Add Doctrine ORM functional test foundation" "type: tests,area: doctrine,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Implement a dedicated service that guesses datatable column metadata from Doctrine ORM metadata.

## Context

The Doctrine provider should not contain all type-guessing rules directly.

Relevant decision:
- docs/decisions/0005-doctrine-orm-provider-architecture.md

## Scope

- Add `DoctrineFieldTypeGuesser`.
- Infer DBAL type for entity fields.
- Detect backed enum fields when Doctrine metadata exposes enum type information.
- Map Doctrine types to initial datatable cell types.
- Add unit tests around common DBAL types.

## Out of scope

- Doctrine provider data loading.
- Twig cell templates by type.
- Association traversal.
- Custom joins.

## Constraints

- Follow `AGENTS.md`.
- Keep Doctrine-specific logic isolated.
- Maintain Doctrine ORM 3 and 4 compatibility.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Field type guesser exists.
- [ ] Common Doctrine DBAL types are mapped.
- [ ] Enum metadata is handled when available.
- [ ] Tests cover type guessing behavior.
- [ ] QA passes.
BODY
)"
create_issue "Implement Doctrine field type guesser" "type: feature,area: doctrine,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Implement the initial Doctrine ORM data provider skeleton.

## Context

The provider must implement `DataProviderInterface` and return `DatatableResult`.

## Scope

- Add `DoctrineOrmDataProvider`.
- Support datatable definitions with an entity class.
- Create the base Doctrine QueryBuilder.
- Return paginated rows with selected visible columns.
- Return total and filtered counts.
- Add functional tests with the Doctrine test foundation.

## Out of scope

- Advanced search.
- Advanced sorting.
- Permanent filters.
- Association traversal.
- Custom joins.
- Exports.

## Constraints

- Follow `AGENTS.md`.
- Keep provider independent from Twig and HTTP.
- Do not parse Symfony Request directly.
- Avoid PostgreSQL-specific behavior.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Doctrine provider implements `DataProviderInterface`.
- [ ] Provider supports definitions with an entity class.
- [ ] Provider returns `DatatableResult`.
- [ ] Basic pagination works.
- [ ] Functional tests cover a simple entity dataset.
- [ ] QA passes.
BODY
)"
create_issue "Implement Doctrine ORM data provider skeleton" "type: feature,area: doctrine,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Wire the Doctrine ORM provider into the Symfony container without making Doctrine mandatory for applications that do not use it.

## Context

Doctrine ORM is optional at package level but required for Doctrine-backed datatables.

## Scope

- Register Doctrine provider only when Doctrine classes/services are available.
- Tag the Doctrine provider as `zhortein_datatable.data_provider`.
- Ensure provider registry can resolve Doctrine provider by name.
- Add functional tests when Doctrine is installed in the test environment.
- Update documentation if needed.

## Out of scope

- Provider query logic implementation beyond existing provider behavior.
- Doctrine bundle recipe.
- Production app configuration.

## Constraints

- Follow Symfony DI best practices.
- Do not break applications without Doctrine installed.
- Do not make Doctrine the only possible provider.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Doctrine provider is wired conditionally.
- [ ] Provider registry exposes Doctrine provider in test environment.
- [ ] Applications without Doctrine should not be forced to use it.
- [ ] QA passes.
BODY
)"
create_issue "Wire Doctrine provider into the Symfony container" "type: feature,area: doctrine,area: configuration,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Apply permanent filters from `DatatableDefinition` in the Doctrine ORM provider.

## Context

Permanent filters are backend-defined and must not be controlled by the frontend.

## Scope

- Translate `FilterDefinition` objects into Doctrine QueryBuilder expressions.
- Support initial operators from `FilterOperator`.
- Bind values as parameters.
- Apply permanent filters to both total visible universe and filtered result counts.
- Add functional tests.

## Out of scope

- User-controlled advanced filters.
- Search builder.
- Custom joins.
- Security voters.

## Constraints

- Never concatenate user-controlled values into DQL.
- Keep expression generation testable.
- Avoid database-specific behavior unless explicit.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Permanent filters are applied to Doctrine queries.
- [ ] Values are bound as parameters.
- [ ] Initial filter operators are covered by tests.
- [ ] Counts reflect permanent filters.
- [ ] QA passes.
BODY
)"
create_issue "Implement Doctrine permanent filters" "type: feature,area: doctrine,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Implement simple global search in the Doctrine ORM provider.

## Context

The provider should support global search on columns marked as searchable.

## Scope

- Apply search only to searchable columns.
- Support string-like fields with portable `LIKE`.
- Support numeric fields only when the search query is numeric.
- Ignore unsupported field types safely.
- Add functional tests.

## Out of scope

- PostgreSQL `ILIKE`.
- Advanced filters.
- Search builder.
- Association traversal.
- JSON search.

## Constraints

- Do not assume PostgreSQL.
- Bind all search parameters safely.
- Validate searchable columns against the datatable definition.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Search applies to searchable columns.
- [ ] String search works.
- [ ] Numeric search works when safe.
- [ ] Non-searchable columns are ignored.
- [ ] QA passes.
BODY
)"
create_issue "Implement Doctrine global search" "type: feature,area: doctrine,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Implement single-column sorting in the Doctrine ORM provider.

## Context

The provider should support sorting requested through `DatatableRequest`.

## Scope

- Apply sorting only when a sort field is present.
- Validate sort field against declared columns.
- Apply sorting only to sortable columns.
- Support asc and desc directions.
- Add functional tests.

## Out of scope

- Multi-column sorting.
- User preferences.
- Association sorting unless already supported by explicit fields.
- Custom sort expressions.

## Constraints

- Do not trust arbitrary frontend field names.
- Use only declared datatable columns.
- Keep behavior predictable.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Single-column sorting works.
- [ ] Non-sortable columns are ignored or rejected safely.
- [ ] Unknown sort fields are ignored or rejected safely.
- [ ] Tests cover asc and desc sorting.
- [ ] QA passes.
BODY
)"
create_issue "Implement Doctrine single-column sorting" "type: feature,area: doctrine,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Document how to declare and use Doctrine-backed datatables.

## Context

Once Doctrine provider foundations are implemented, users need a basic usage guide.

## Scope

- Add `docs/doctrine-provider.md`.
- Document requirements.
- Document entity-class based datatable declarations.
- Document searchable/sortable columns.
- Document permanent filters.
- Document current limitations.
- Link the page from README and docs index.

## Out of scope

- Advanced filters.
- Custom joins.
- Exports.
- Performance tuning guide.

## Constraints

- Keep examples generic.
- No private/client-specific references.
- Documentation must match implemented behavior.
- QA passes.

## Acceptance criteria

- [ ] Doctrine provider documentation exists.
- [ ] README links to it.
- [ ] Docs index links to it.
- [ ] Current limitations are explicit.
- [ ] QA passes.
BODY
)"
create_issue "Document Doctrine-backed datatables" "type: docs,area: doctrine,priority: medium,ai-ready" "$body"
rm -f "$body"

echo "Doctrine provider foundation issues created successfully."
