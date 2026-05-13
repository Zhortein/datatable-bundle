#!/usr/bin/env bash

set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI is required. Install it first: https://cli.github.com/"
  exit 1
fi

gh auth status >/dev/null

MILESTONE_TITLE="0.18 - Frontend test foundation"

ensure_milestone() {
  if gh api repos/:owner/:repo/milestones --jq ".[] | select(.title == \"${MILESTONE_TITLE}\") | .title" | grep -q "${MILESTONE_TITLE}"; then
    echo "Milestone already exists: ${MILESTONE_TITLE}"
  else
    echo "Creating milestone: ${MILESTONE_TITLE}"
    gh api repos/:owner/:repo/milestones \
      -f title="${MILESTONE_TITLE}" \
      -f description="Add automated JavaScript tests for the vanilla Stimulus datatable controller and document the frontend test strategy."
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

Choose and install a lightweight JavaScript test setup for the bundle Stimulus controller.

## Context

The datatable Stimulus controller now handles auto-load, Ajax refresh, search, filters, column visibility, exports, confirmation and state updates.

Until now, this behavior has mostly been validated through smoke tests.

## Scope

- Choose a JS test runner.
- Prefer a lightweight stack such as Vitest + jsdom.
- Add minimal package metadata if needed.
- Add test scripts.
- Ensure tests can run in CI.
- Keep the setup independent from Webpack Encore.
- Document the choice.

## Out of scope

- End-to-end browser testing.
- Playwright/Cypress.
- Testing Bootstrap dropdown internals.
- Rewriting the controller.

## Constraints

- Vanilla JavaScript remains required.
- No jQuery.
- No DataTables.net.
- Keep tooling minimal.
- CI runtime must remain reasonable.

## Acceptance criteria

- [ ] JS test runner is selected and installed.
- [ ] JS tests can run locally.
- [ ] Composer or Makefile command exists to run frontend tests.
- [ ] CI can run frontend tests.
- [ ] Documentation explains the chosen setup.
BODY
)"
create_issue "Set up frontend test tooling" "type: ci,area: stimulus,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Add tests for Stimulus controller connection and initial auto-load behavior.

## Context

The smoke test revealed that data did not load initially until the controller called `refresh()` on connect.

This behavior is important enough to be covered by automated JS tests.

## Scope

- Test controller connects.
- Test auto-load true triggers a fetch.
- Test auto-load false does not trigger a fetch.
- Test missing fragments URL shows an error.
- Mock `fetch`.
- Use a minimal DOM fixture.

## Out of scope

- Full Ajax response rendering.
- Bootstrap dropdown testing.
- Browser E2E tests.

## Constraints

- Test the current UX controller identifier.
- Keep tests deterministic.
- No network calls.

## Acceptance criteria

- [ ] Connect is tested.
- [ ] Auto-load true is tested.
- [ ] Auto-load false is tested.
- [ ] Missing fragments URL behavior is tested.
- [ ] Frontend tests pass.
BODY
)"
create_issue "Test Stimulus connect and auto-load behavior" "type: tests,area: stimulus,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Add automated tests for Ajax fragment application.

## Context

The controller updates server-rendered fragments returned by the backend.

This is core behavior for body, header, pagination and summary refresh.

## Scope

- Test applying `header` fragment.
- Test applying `body` fragment.
- Test applying `pagination` fragment.
- Test applying `summary`.
- Test applying returned page/pageSize state.
- Mock fetch response.

## Out of scope

- Rendering Twig fragments.
- Provider tests.
- Browser E2E testing.

## Constraints

- No network calls.
- No jQuery.
- Keep HTML fixtures small.

## Acceptance criteria

- [ ] Header replacement is tested.
- [ ] Body replacement is tested.
- [ ] Pagination replacement is tested.
- [ ] Summary update is tested.
- [ ] State update is tested.
- [ ] Frontend tests pass.
BODY
)"
create_issue "Test Stimulus Ajax fragment application" "type: tests,area: stimulus,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Add automated tests for search, filter and page size interactions.

## Context

The controller serializes search and filter controls into the Ajax request and resets page state.

## Scope

- Test search input updates search value.
- Test search resets page to 1.
- Test filter controls serialize `filters[...]`.
- Test filter changes reset page to 1.
- Test page size changes update pageSize and reset page.
- Test empty filter values are omitted.

## Out of scope

- Backend filter application.
- Browser E2E tests.
- Debounce timing precision beyond deterministic fake timers.

## Constraints

- Use fake timers where needed.
- No real network.
- Keep fixture markup realistic.

## Acceptance criteria

- [ ] Search serialization is tested.
- [ ] Filter serialization is tested.
- [ ] Page size changes are tested.
- [ ] Page reset behavior is tested.
- [ ] Frontend tests pass.
BODY
)"
create_issue "Test Stimulus search filters and page size interactions" "type: tests,area: stimulus,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Add automated tests for sorting and pagination interactions.

## Context

The controller handles sortable headers and pagination buttons.

## Scope

- Test sort on a new field sets direction to `asc`.
- Test clicking same field toggles direction to `desc`.
- Test sorting resets page to 1.
- Test pagination button changes page.
- Test invalid page parameter is ignored.
- Test request URL contains expected sort/page parameters.

## Out of scope

- Backend sorting.
- Header Twig rendering.
- Browser E2E tests.

## Constraints

- No network calls.
- Use small fixtures.
- Keep test names explicit.

## Acceptance criteria

- [ ] New sort field behavior is tested.
- [ ] Sort toggle behavior is tested.
- [ ] Pagination behavior is tested.
- [ ] Invalid page handling is tested.
- [ ] Frontend tests pass.
BODY
)"
create_issue "Test Stimulus sorting and pagination interactions" "type: tests,area: stimulus,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Add automated tests for column visibility interactions.

## Context

The smoke test found multiple issues around column visibility and header refresh.

This behavior needs frontend tests.

## Scope

- Test visible/hidden column parameters are serialized.
- Test definition-hidden columns are ignored.
- Test column visibility change resets page to 1.
- Test column visibility change triggers refresh.
- Test header fragment replacement after refresh if fixture includes header.

## Out of scope

- Twig rendering of the controls.
- Backend visibility normalization.
- Browser E2E tests.

## Constraints

- Use the current UX namespaced data attributes.
- No real network.
- Keep fixtures readable.

## Acceptance criteria

- [ ] Visible columns are serialized.
- [ ] Hidden columns are serialized.
- [ ] Definition-hidden columns are not serialized.
- [ ] Page reset is tested.
- [ ] Frontend tests pass.
BODY
)"
create_issue "Test Stimulus column visibility interactions" "type: tests,area: stimulus,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Add automated tests for CSV export URL generation.

## Context

The smoke test found that export links initially ignored the current datatable state.

The controller now builds export URLs from current state.

## Scope

- Test current export includes page and pageSize.
- Test full export omits pagination.
- Test search is included.
- Test filters are included.
- Test sort is included.
- Test column visibility is included.
- Test custom export URL is used.

## Out of scope

- CSV file content.
- Backend export endpoint.
- Browser download behavior.

## Constraints

- Mock `window.location`.
- No real navigation.
- Keep assertions focused on URL.

## Acceptance criteria

- [ ] Current mode export URL is tested.
- [ ] Full mode export URL is tested.
- [ ] Search/filter/sort/visibility state is tested.
- [ ] Custom export URL is tested.
- [ ] Frontend tests pass.
BODY
)"
create_issue "Test Stimulus export URL generation" "type: tests,area: stimulus,area: export,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Add automated tests for confirmation behavior.

## Context

The controller uses `window.confirm()` for action confirmation metadata.

## Scope

- Test action without confirmation does nothing.
- Test confirm true allows event.
- Test confirm false prevents default and stops propagation.
- Test blank confirmation message is ignored.
- Test namespaced confirmation data attribute is read correctly.

## Out of scope

- Bootstrap modal confirmation.
- Browser E2E tests.
- Action controller backend behavior.

## Constraints

- Mock `window.confirm`.
- Keep behavior opt-in.
- No external dependencies.

## Acceptance criteria

- [ ] Confirm true behavior is tested.
- [ ] Confirm false behavior is tested.
- [ ] Missing/blank message behavior is tested.
- [ ] Frontend tests pass.
BODY
)"
create_issue "Test Stimulus action confirmation behavior" "type: tests,area: stimulus,area: security,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Add frontend tests to GitHub Actions.

## Context

After JS test tooling and tests are added, CI must run them.

## Scope

- Add frontend test command to CI.
- Add frontend test command to Makefile if needed.
- Add Composer script or npm script as appropriate.
- Ensure CI cache strategy is reasonable.
- Ensure local documentation is updated.

## Out of scope

- Browser E2E matrix.
- Code coverage thresholds.
- Mutation testing.

## Constraints

- CI runtime must remain reasonable.
- Existing PHP QA must remain unchanged.
- Frontend tests must fail CI on failure.

## Acceptance criteria

- [ ] Frontend tests run in CI.
- [ ] Frontend tests can run locally.
- [ ] Documentation is updated.
- [ ] CI is green.
BODY
)"
create_issue "Run frontend tests in CI" "type: ci,area: stimulus,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Document the frontend test strategy and update the roadmap.

## Context

After frontend tests are added, contributors need to know how to run and extend them.

## Scope

- Add or update frontend test documentation.
- Document local commands.
- Document fixture strategy.
- Document what is covered and not covered.
- Update roadmap for 0.18 completion.
- Link from docs index if useful.

## Out of scope

- Writing new controller behavior tests.
- Adding E2E tests.

## Constraints

- Documentation must match the implemented tooling.
- Keep current limitations explicit.
- QA passes.

## Acceptance criteria

- [ ] Frontend test docs exist.
- [ ] Roadmap is updated for 0.18.
- [ ] Docs index is updated if needed.
- [ ] QA passes.
BODY
)"
create_issue "Document frontend test strategy" "type: docs,area: stimulus,priority: medium,ai-ready" "$body"
rm -f "$body"

echo "Frontend test foundation issues created successfully."
