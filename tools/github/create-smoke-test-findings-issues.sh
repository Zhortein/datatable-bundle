#!/usr/bin/env bash

set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI is required. Install it first: https://cli.github.com/"
  exit 1
fi

gh auth status >/dev/null

MILESTONE_TITLE="0.15 - First alpha preparation"

ensure_milestone() {
  if gh api repos/:owner/:repo/milestones --jq ".[] | select(.title == \"${MILESTONE_TITLE}\") | .title" | grep -q "${MILESTONE_TITLE}"; then
    echo "Milestone already exists: ${MILESTONE_TITLE}"
  else
    echo "Creating milestone: ${MILESTONE_TITLE}"
    gh api repos/:owner/:repo/milestones \
      -f title="${MILESTONE_TITLE}" \
      -f description="Fresh Symfony application smoke test, alpha blockers, changelog preparation and first pre-release readiness."
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

Expose the bundle Stimulus controller through a Symfony UX / AssetMapper compatible package instead of requiring applications to copy controller source manually.

## Smoke test finding

The fresh Symfony smoke app showed that importing the controller directly from vendor through a relative browser path causes a 404:

```text
GET /vendor/zhortein/datatable-bundle/assets/controllers/datatable_controller.js 404
```

Copying the controller source into the host app works, which confirms the controller itself works, but the distribution/integration strategy is wrong.

## Scope

- Add an `assets/package.json` compatible with Symfony UX / StimulusBundle conventions.
- Expose the datatable Stimulus controller as a third-party controller.
- Define the final controller identifier strategy.
- Update rendered `data-controller` names if needed.
- Update documentation for host application setup.
- Validate in the smoke application.

## Out of scope

- Symfony Flex recipe.
- NPM/Webpack Encore integration.
- Rewriting the Stimulus controller behavior.

## Constraints

- Symfony 8+ / AssetMapper first.
- Vanilla JavaScript only.
- No jQuery.
- No DataTables.net.
- Do not require copying source files manually in final integration.

## Acceptance criteria

- [ ] Host app can enable the controller without copying source code.
- [ ] No 404 is produced for the controller asset.
- [ ] Stimulus controller connects in the smoke app.
- [ ] Documentation reflects the final integration.
- [ ] QA passes.
BODY
)"
create_issue "Expose Stimulus controller through a UX-compatible assets package" "type: feature,area: stimulus,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Document that host applications must provide Bootstrap CSS and JavaScript, and include Symfony AssetMapper/importmap examples.

## Smoke test finding

The datatable renders Bootstrap-first markup. Without Bootstrap CSS the table looks unstyled, and without Bootstrap JavaScript dropdown controls such as column visibility and export do not behave as expected.

## Scope

- Update installation documentation.
- Update Stimulus/AssetMapper documentation if needed.
- Add examples using `importmap:require bootstrap`.
- Document importing Bootstrap JavaScript.
- Document loading Bootstrap CSS.
- Mention dropdown controls require Bootstrap bundle JS.

## Out of scope

- Automatically installing Bootstrap.
- Bundling Bootstrap CSS/JS in the datatable bundle.
- Adding a CSS framework dependency.

## Constraints

- The bundle must not force Bootstrap installation.
- Documentation must be Symfony 8 / AssetMapper friendly.
- Keep examples generic.

## Acceptance criteria

- [ ] Bootstrap CSS requirement is documented.
- [ ] Bootstrap JS requirement is documented.
- [ ] AssetMapper/importmap example exists.
- [ ] Dropdown dependency is explicit.
- [ ] QA passes.
BODY
)"
create_issue "Document Bootstrap CSS and JS requirements for host applications" "type: docs,area: bootstrap,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Investigate and fix or document the global search behavior of `ArrayDataProvider`.

## Smoke test finding

The fresh Symfony smoke test shows that global search appears to behave like starts-with matching instead of contains matching.

## Scope

- Verify what `ArrayDataProvider` currently does.
- Verify the actual query parameter sent by Stimulus.
- Add explicit tests for contains-style search.
- Fix the provider if the implementation is wrong.
- Document the intended behavior.

## Out of scope

- Doctrine search behavior.
- Advanced filter behavior.
- SearchBuilder-style expressions.

## Constraints

- Array provider should stay simple.
- Behavior should be consistent with documentation.
- QA passes.

## Acceptance criteria

- [ ] Expected behavior is defined.
- [ ] Tests cover contains, starts-with and no-match cases.
- [ ] Provider behavior matches the tests.
- [ ] Documentation is updated if needed.
- [ ] QA passes.
BODY
)"
create_issue "Investigate ArrayDataProvider global search matching behavior" "type: bug,area: configuration,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Apply declared user-facing filters in `ArrayDataProvider`.

## Smoke test finding

The minimal array datatable renders filters and refreshes when they change, but filter values do not affect the displayed data.

This makes the minimal example misleading.

## Scope

- Apply declared user-facing filters from `DatatableDefinition::getFilters()`.
- Read normalized values from `DatatableRequest::getFilters()`.
- Support at least:
  - text filters;
  - choice filters;
  - boolean filters.
- Ignore unknown frontend filter input.
- Add unit tests.
- Update docs if some filter types remain unsupported.

## Out of scope

- Complex nested expressions.
- Full parity with Doctrine filters for every type if too large.
- SearchBuilder behavior.

## Constraints

- Keep `ArrayDataProvider` simple.
- Only declared filters should be applied.
- QA passes.

## Acceptance criteria

- [ ] Text filters work with array data.
- [ ] Choice filters work with array data.
- [ ] Boolean filters work with array data.
- [ ] Unknown filters are ignored.
- [ ] Smoke array example filters work.
- [ ] QA passes.
BODY
)"
create_issue "Apply user-facing filters in ArrayDataProvider" "type: feature,area: configuration,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Do not render definition-hidden columns as toggleable controls in the column visibility dropdown.

## Smoke test finding

A column declared with `visible: false`, such as an internal `id`, appears in the column visibility menu. Checking it triggers a refresh but the column cannot appear because definition-level hidden columns remain hidden.

This is confusing for users.

## Scope

- Update column visibility template.
- Hide definition-hidden columns from the toggle list, or render them disabled with clear semantics.
- Prefer hiding them for the first implementation.
- Update tests that currently expect hidden definition columns in the dropdown.
- Update documentation.

## Out of scope

- Runtime ability to force-show definition-hidden columns.
- Permission-aware column visibility.
- Column visibility persistence.

## Constraints

- Definition-level hidden columns must remain hidden.
- Toolbar should not offer controls that cannot work.
- TwigCS must pass.

## Acceptance criteria

- [ ] Definition-hidden columns are no longer toggleable.
- [ ] Existing runtime visibility still works for visible columns.
- [ ] Tests are updated.
- [ ] Documentation is updated if needed.
- [ ] QA passes.
BODY
)"
create_issue "Do not render definition-hidden columns as toggleable visibility controls" "type: bug,area: twig,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Refresh the table header when column visibility changes.

## Smoke test finding

When a visible column is unchecked, the table body is refreshed correctly but the header remains unchanged.

The Ajax fragments response currently returns body, pagination and summary, but not the header.

## Scope

- Add a header fragment to the Ajax response.
- Add a `header` target in the Stimulus controller.
- Render the header with the same runtime column visibility options as the body.
- Update datatable shell templates.
- Update Stimulus fragment application.
- Add tests.

## Out of scope

- Full table re-rendering.
- Persisted column visibility.
- Column order customization.

## Constraints

- Keep rendering server-side.
- Keep Stimulus simple.
- Header and body must use the same visibility state.
- QA passes.

## Acceptance criteria

- [ ] Ajax response includes `header`.
- [ ] Stimulus updates the header target.
- [ ] Header and body stay synchronized after column visibility changes.
- [ ] Smoke test column visibility works.
- [ ] QA passes.
BODY
)"
create_issue "Refresh table header when column visibility changes" "type: bug,area: twig,area: stimulus,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Include the current datatable frontend state in CSV export links.

## Smoke test finding

CSV export currently exports the default dataset state, regardless of the current page size, filters, search, sorting or column visibility.

## Scope

- Update export controls and/or Stimulus controller so export URLs include current state.
- Current-view export should include:
  - page;
  - pageSize;
  - search;
  - filters;
  - sort field;
  - sort direction;
  - visible/hidden columns.
- Full export should include:
  - search;
  - filters;
  - sort field;
  - sort direction;
  - visible/hidden columns;
  - mode=full.
- Add tests where practical.
- Update documentation.

## Out of scope

- Async exports.
- XLSX export.
- Export progress UI.

## Constraints

- No client-side CSV generation.
- Export remains server-side.
- No jQuery.
- QA passes.

## Acceptance criteria

- [ ] Export links include current state.
- [ ] Current export keeps pagination.
- [ ] Full export removes pagination but keeps filter/search/sort/visibility state.
- [ ] Smoke test export reflects current state.
- [ ] QA passes.
BODY
)"
create_issue "Include current datatable state in CSV export links" "type: bug,area: export,area: stimulus,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Make CSV delimiter and related formatting options configurable.

## Smoke test finding

CSV export currently uses comma as delimiter. In French/European spreadsheet workflows, semicolon is often expected.

## Scope

- Add CSV configuration options:
  - delimiter;
  - enclosure;
  - escape;
  - optional UTF-8 BOM.
- Inject configuration into `CsvExportWriter`.
- Keep safe defaults.
- Add tests.
- Update export documentation.

## Out of scope

- XLSX export.
- Per-user CSV preferences.
- Locale auto-detection unless simple and explicit.

## Constraints

- Server-side CSV only.
- Use PHP CSV built-ins.
- Do not break existing default behavior without an explicit decision.
- QA passes.

## Acceptance criteria

- [ ] CSV delimiter is configurable.
- [ ] Enclosure/escape are configurable or explicitly documented.
- [ ] Optional BOM behavior is decided.
- [ ] Tests cover semicolon output.
- [ ] Documentation is updated.
- [ ] QA passes.
BODY
)"
create_issue "Make CSV delimiter and formatting configurable" "type: feature,area: export,area: configuration,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Translate and refine the datatable result summary.

## Smoke test finding

The summary is hardcoded in English:

```text
Showing 1 to 3 of 3 entries
```

It should be translated and should handle empty/default/filtered states consistently.

## Scope

- Add translation keys for summary messages.
- Replace hardcoded English strings in the controller.
- Support empty state.
- Support normal state.
- Support filtered state.
- Add tests.
- Update documentation.

## Out of scope

- Complex pluralization if too large for the first pass.
- Locale-specific number formatting.
- Frontend summary generation.

## Constraints

- Use Symfony Translation.
- Keep summary generated server-side.
- QA passes.

## Acceptance criteria

- [ ] Summary messages use translation keys.
- [ ] English and French catalogs are updated.
- [ ] Ajax fragments return translated summaries.
- [ ] Tests cover empty/default/filtered summaries.
- [ ] QA passes.
BODY
)"
create_issue "Translate and refine datatable result summary" "type: feature,area: i18n,area: twig,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Record the fresh Symfony smoke test findings in the repository.

## Context

The first smoke test validated several behaviors and uncovered alpha blockers.

## Scope

- Copy `docs/smoke-test-report-template.md` to a dated report under `docs/smoke-reports/`.
- Record validated behavior.
- Record blocking issues.
- Record non-blocking issues.
- Link created GitHub issues.
- Add go/no-go recommendation.

## Out of scope

- Fixing the issues directly.
- Running Doctrine smoke test if not done yet.

## Constraints

- Keep report generic.
- No private URLs or sensitive hostnames if avoidable.
- QA passes.

## Acceptance criteria

- [ ] Smoke report exists.
- [ ] Findings are documented.
- [ ] Created issues are linked.
- [ ] Go/no-go recommendation is recorded.
- [ ] QA passes.
BODY
)"
create_issue "Record fresh Symfony smoke test findings" "type: docs,type: tests,priority: high,ai-ready" "$body"
rm -f "$body"

echo "Smoke test finding issues created successfully."
