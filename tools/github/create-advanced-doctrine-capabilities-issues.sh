#!/usr/bin/env bash

set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI is required. Install it first: https://cli.github.com/"
  exit 1
fi

gh auth status >/dev/null

MILESTONE_TITLE="0.19 - Advanced Doctrine capabilities"

ensure_milestone() {
  if gh api repos/:owner/:repo/milestones --jq ".[] | select(.title == \"${MILESTONE_TITLE}\") | .title" | grep -q "${MILESTONE_TITLE}"; then
    echo "Milestone already exists: ${MILESTONE_TITLE}"
  else
    echo "Creating milestone: ${MILESTONE_TITLE}"
    gh api repos/:owner/:repo/milestones \
      -f title="${MILESTONE_TITLE}" \
      -f description="Improve Doctrine-backed datatables for richer business reporting: provider decomposition, joins, aggregates, counts, performance and advanced documentation."
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

Refactor `DoctrineOrmDataProvider` by extracting smaller query-building collaborators.

## Context

The Doctrine provider has grown as support was added for pagination, sorting, search, filters, joins and exports.

Before adding more advanced Doctrine capabilities, the provider should be decomposed into testable units.

## Scope

- Identify responsibilities currently inside `DoctrineOrmDataProvider`.
- Extract small collaborators where useful, for example:
  - select builder;
  - join applier;
  - permanent filter applier;
  - user filter applier;
  - search applier;
  - sort applier;
  - metadata resolver.
- Preserve existing public behavior.
- Add/adjust tests.
- Keep services internal unless they are useful extension points.

## Out of scope

- New Doctrine features.
- Deep joins.
- Aggregates.
- API-breaking provider contract changes.

## Constraints

- No behavior regression.
- PHPStan max level must pass.
- Doctrine 3 and 4 compatibility must be preserved.
- Existing functional tests must remain green.

## Acceptance criteria

- [ ] Provider responsibilities are split into smaller collaborators.
- [ ] Existing Doctrine tests still pass.
- [ ] Public provider contract remains unchanged.
- [ ] New collaborators are covered by tests where appropriate.
- [ ] QA passes.
BODY
)"
create_issue "Refactor Doctrine provider query-building responsibilities" "type: refactor,area: doctrine,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Improve Doctrine field and alias metadata resolution for main and joined aliases.

## Context

Joined field support currently resolves metadata from explicit joins. This logic will become more important with deeper joins, filters and aggregate support.

## Scope

- Introduce or refine a metadata resolver service.
- Resolve metadata for:
  - main alias `e`;
  - explicit join aliases;
  - future nested join aliases if supported later.
- Provide clear exceptions for invalid aliases/fields.
- Add tests.

## Out of scope

- Automatic deep association traversal.
- Non-mapped joins.
- Aggregates.

## Constraints

- Explicit aliases only.
- Clear failure modes.
- Doctrine 3 and 4 compatibility.
- PHPStan max level must pass.

## Acceptance criteria

- [ ] Metadata resolution is isolated.
- [ ] Main alias metadata is resolved.
- [ ] Joined alias metadata is resolved.
- [ ] Unknown alias/field behavior is tested.
- [ ] QA passes.
BODY
)"
create_issue "Extract Doctrine field metadata resolver" "type: feature,area: doctrine,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Support one additional level of explicitly declared Doctrine joins when safe.

## Context

The provider currently supports simple explicit joins such as `e.organization`.

Some business datatables need a field from a second-level association, for example `organization.group.name`.

## Scope

- Support explicitly declared chained joins, for example:
  - `organization.group`
  - `group.owner`
- Do not auto-discover deep joins.
- Require each join alias to be explicitly declared.
- Support displaying fields from chained join aliases.
- Add functional tests.

## Out of scope

- Automatic association traversal.
- Collection-valued associations.
- ManyToMany aggregation.
- Arbitrary DQL.

## Constraints

- Explicit join declarations only.
- Alias validation must remain strict.
- Counts must remain consistent.
- PHPStan max level must pass.

## Acceptance criteria

- [ ] Explicit second-level joins can be applied.
- [ ] Columns from second-level aliases can be selected.
- [ ] Existing simple joins still work.
- [ ] Functional tests cover chained joins.
- [ ] QA passes.
BODY
)"
create_issue "Support explicitly chained Doctrine joins" "type: feature,area: doctrine,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Define and implement a safe custom join expression API for Doctrine-backed datatables.

## Context

Legacy implementations sometimes needed joins between entities without a mapped Doctrine association.

The current bundle intentionally avoids arbitrary raw DQL joins, but a controlled API may be useful.

## Scope

- Design a `CustomJoinDefinition` or extend `JoinDefinition` safely.
- Support joining a target entity class with an explicit condition.
- Support left/inner join.
- Bind parameters if needed or explicitly forbid dynamic values for now.
- Add tests.
- Document limitations.

## Out of scope

- User-provided frontend joins.
- Arbitrary unsafe DQL snippets.
- Collection aggregation.
- Auto-discovery.

## Constraints

- Backend-only declarations.
- Clear security limitations.
- Strict alias validation.
- PHPStan max level must pass.

## Acceptance criteria

- [ ] Custom joins can be declared backend-side.
- [ ] Custom joins are applied safely.
- [ ] Invalid custom joins fail clearly.
- [ ] Functional tests cover a custom join.
- [ ] Documentation explains limitations.
- [ ] QA passes.
BODY
)"
create_issue "Design and implement safe custom Doctrine joins" "type: feature,area: doctrine,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Introduce aggregate column support for Doctrine-backed datatables.

## Context

Business datatables often need simple aggregated values, such as counts or sums.

The legacy reference included aggregate-related ideas. The bundle should support a small, explicit, safe subset.

## Scope

- Add aggregate column definition support.
- Support at least:
  - count;
  - sum;
  - min;
  - max;
  - avg if feasible.
- Require explicit aggregate declaration.
- Add group-by behavior where needed.
- Add tests.

## Out of scope

- Complex SQL expressions.
- Database-specific string aggregation for first implementation.
- Collection aggregation if it requires unstable behavior.
- User-defined aggregate expressions from frontend.

## Constraints

- Explicit backend declarations only.
- Doctrine/DQL portability where possible.
- Counts and pagination must remain correct or limitations documented.
- PHPStan max level must pass.

## Acceptance criteria

- [ ] Aggregate definition API exists.
- [ ] At least count/sum are supported or explicitly limited.
- [ ] Generated DQL is safe and tested.
- [ ] Documentation explains grouping limitations.
- [ ] QA passes.
BODY
)"
create_issue "Implement Doctrine aggregate columns foundation" "type: feature,area: doctrine,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Review and improve Doctrine count and distinct strategies.

## Context

Joins and future aggregates can produce duplicate rows or incorrect counts.

The provider should have explicit behavior and tests for counts with joins and filters.

## Scope

- Review current total and filtered count queries.
- Add tests for counts with:
  - simple joins;
  - chained joins;
  - filters;
  - search;
  - sorting.
- Decide whether `COUNT(e)` or `COUNT(DISTINCT e.id)` is needed in specific contexts.
- Document behavior.

## Out of scope

- Full aggregate support unless already implemented.
- Collection joins if unsupported.
- Performance benchmarking beyond query shape review.

## Constraints

- Counts must remain correct for supported joins.
- Avoid unnecessary DISTINCT when not needed if possible.
- Doctrine compatibility must be preserved.
- QA passes.

## Acceptance criteria

- [ ] Count strategy is documented.
- [ ] Count behavior with joins is tested.
- [ ] Count behavior with filters/search is tested.
- [ ] Any known limitations are explicit.
- [ ] QA passes.
BODY
)"
create_issue "Review Doctrine count and distinct strategy" "type: tests,area: doctrine,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Add provider-level performance and safety notes for Doctrine datatables.

## Context

Advanced Doctrine datatables can become expensive if joins, filters and full exports are used carelessly.

Users need practical guidance.

## Scope

- Add performance documentation.
- Cover indexes for filtered/sorted fields.
- Cover joined fields.
- Cover full exports.
- Cover pagination.
- Cover search limitations.
- Cover avoiding large in-memory exports.
- Add roadmap notes if follow-up features are needed.

## Out of scope

- Benchmark suite.
- Automatic query optimization.
- Database-specific tuning.

## Constraints

- Guidance must be generic and accurate.
- Avoid over-promising performance.
- QA passes.

## Acceptance criteria

- [ ] Doctrine performance notes are documented.
- [ ] Export performance cautions are documented.
- [ ] Indexing recommendations are documented.
- [ ] Current limitations are explicit.
- [ ] QA passes.
BODY
)"
create_issue "Document Doctrine provider performance guidance" "type: docs,area: doctrine,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Document the advanced Doctrine capabilities added in this milestone and update the roadmap.

## Context

After refactoring and advanced Doctrine improvements, documentation must reflect the real supported scope.

## Scope

- Update `docs/doctrine-provider.md`.
- Update `docs/architecture.md`.
- Add/refresh advanced Doctrine examples if useful.
- Update `docs/roadmap.md`.
- Clarify unsupported areas.

## Out of scope

- New Doctrine feature implementation.
- XLSX export decision.

## Constraints

- Documentation must match implemented behavior.
- Examples must be generic.
- QA passes.

## Acceptance criteria

- [ ] Advanced Doctrine capabilities are documented.
- [ ] Roadmap marks 0.19 as complete.
- [ ] Unsupported areas are explicit.
- [ ] QA passes.
BODY
)"
create_issue "Document advanced Doctrine capabilities" "type: docs,area: doctrine,priority: medium,ai-ready" "$body"
rm -f "$body"

echo "Advanced Doctrine capabilities issues created successfully."
