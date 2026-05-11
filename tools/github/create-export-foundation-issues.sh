#!/usr/bin/env bash

set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI is required. Install it first: https://cli.github.com/"
  exit 1
fi

gh auth status >/dev/null

MILESTONE_TITLE="0.11 - Export foundation"

ensure_milestone() {
  if gh api repos/:owner/:repo/milestones --jq ".[] | select(.title == \"${MILESTONE_TITLE}\") | .title" | grep -q "${MILESTONE_TITLE}"; then
    echo "Milestone already exists: ${MILESTONE_TITLE}"
  else
    echo "Creating milestone: ${MILESTONE_TITLE}"
    gh api repos/:owner/:repo/milestones \
      -f title="${MILESTONE_TITLE}" \
      -f description="Server-side export foundation: export request/result objects, CSV export, export controller endpoint, current/full dataset modes and documentation."
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

Introduce explicit export request and result objects.

## Context

Exports should be server-side and independent from DataTables.net or client-side exports.

Before writing CSV output, the bundle needs typed export inputs and outputs.

## Scope

- Add `ExportFormat` enum.
- Add `ExportMode` enum.
- Add `DatatableExportRequest` value object.
- Add `DatatableExportResult` value object if needed.
- Support current view vs full dataset modes.
- Add unit tests.

## Out of scope

- CSV writing.
- XLSX writing.
- Controller endpoint.
- Doctrine export query optimization.

## Constraints

- Follow `AGENTS.md`.
- Keep exports server-side.
- Keep request/result objects provider-agnostic.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Export format enum exists.
- [ ] Export mode enum exists.
- [ ] Export request object exists.
- [ ] Tests cover defaults and validation.
- [ ] QA passes.
BODY
)"
create_issue "Implement export request and format objects" "type: feature,area: export,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Add an export provider/writer contract for server-side exports.

## Context

CSV should be the first export implementation, but the architecture should allow later XLSX support without coupling the core to a specific library.

## Scope

- Add `ExportWriterInterface` or equivalent.
- Define supported format checks.
- Define a method returning a Symfony `Response` or streamed response.
- Add a writer registry if useful.
- Add unit tests.

## Out of scope

- CSV implementation.
- XLSX implementation.
- Export controller.
- Async exports.

## Constraints

- Keep writer architecture extensible.
- Do not introduce XLSX dependency yet.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Export writer contract exists.
- [ ] Writer resolution strategy exists if needed.
- [ ] Missing writer produces clear exception.
- [ ] Tests cover writer resolution.
- [ ] QA passes.
BODY
)"
create_issue "Implement export writer contract" "type: feature,area: export,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Implement CSV export writing.

## Context

CSV is the first export format because it has no extra runtime dependency and works well for server-side streaming.

## Scope

- Add `CsvExportWriter`.
- Stream CSV output.
- Use visible columns.
- Use column labels as headers.
- Export provider rows safely.
- Escape CSV values with PHP built-ins.
- Add tests.

## Out of scope

- XLSX export.
- PDF export.
- Async export.
- Styling.
- Locale-specific CSV separator configuration unless simple.

## Constraints

- No client-side export.
- No DataTables.net dependency.
- No spreadsheet library dependency.
- PHPStan max must pass.

## Acceptance criteria

- [ ] CSV writer exists.
- [ ] CSV headers use column labels.
- [ ] CSV rows use provider result data.
- [ ] Special characters are escaped correctly.
- [ ] Tests cover generated CSV.
- [ ] QA passes.
BODY
)"
create_issue "Implement CSV export writer" "type: feature,area: export,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Add a server-side export endpoint to the bundle controller/routes.

## Context

The bundle currently exposes Ajax fragments. It needs a generic export route.

## Scope

- Add export route.
- Add controller action.
- Resolve datatable definition.
- Build request/export request.
- Resolve provider.
- Resolve export writer.
- Return streamed/download response.
- Add functional tests.

## Out of scope

- XLSX support.
- Async export.
- Authorization layer.
- Custom filenames beyond a simple strategy.

## Constraints

- Route names must be namespaced with `zhortein_datatable_`.
- Controller remains thin.
- No DataTables.net response format.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Export route exists.
- [ ] CSV export endpoint works.
- [ ] Response headers are correct.
- [ ] Functional tests cover route and response.
- [ ] QA passes.
BODY
)"
create_issue "Implement datatable export endpoint" "type: feature,area: export,area: configuration,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Support current-view and full-dataset export modes.

## Context

Users often need to export either the current filtered/sorted view or all rows visible in the datatable context.

## Scope

- Define current-view mode.
- Define full-dataset mode.
- Make provider request generation respect mode.
- Current view keeps pagination.
- Full dataset removes pagination but keeps permanent filters/user filters/search/sort.
- Add tests.

## Out of scope

- Async export for large datasets.
- Maximum export limits.
- Background jobs.
- Streaming Doctrine iterators if not already needed.

## Constraints

- Avoid unbounded memory usage where practical.
- Keep behavior explicit.
- Document limitations.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Current-view export mode works.
- [ ] Full-dataset export mode works.
- [ ] Filtering/sorting behavior is documented.
- [ ] Tests cover both modes.
- [ ] QA passes.
BODY
)"
create_issue "Support current and full export modes" "type: feature,area: export,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Render export controls in the datatable toolbar.

## Context

Once the export endpoint exists, users need a way to trigger CSV exports from the UI.

## Scope

- Add export button/dropdown in toolbar.
- Generate export URL.
- Include current state parameters where appropriate.
- Add Bootstrap-compatible markup.
- Add translations.
- Add tests.

## Out of scope

- XLSX export button.
- Async export UI.
- Progress indicator.
- Permission checks.

## Constraints

- Bootstrap-first.
- Accessible label.
- No frontend dependency.
- TwigCS must pass.

## Acceptance criteria

- [ ] CSV export control renders.
- [ ] Export URL is generated.
- [ ] Current state parameters are included or documented.
- [ ] Tests cover rendering.
- [ ] QA passes.
BODY
)"
create_issue "Render CSV export control" "type: feature,area: export,area: twig,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Document server-side exports.

## Context

After export objects, writer, endpoint and toolbar controls exist, users need clear documentation.

## Scope

- Add `docs/exports.md`.
- Document CSV export.
- Document current/full modes.
- Document route and query parameters.
- Document limitations.
- Link README, docs index and basic usage.

## Out of scope

- XLSX documentation unless implemented.
- Async export documentation.
- Performance tuning guide.

## Constraints

- Documentation must match implemented behavior.
- Examples must be generic.
- No private/client-specific references.
- QA passes.

## Acceptance criteria

- [ ] Export documentation exists.
- [ ] README/docs index links are updated.
- [ ] Current limitations are explicit.
- [ ] Roadmap is updated for 0.11.
- [ ] QA passes.
BODY
)"
create_issue "Document server-side exports" "type: docs,area: export,priority: medium,ai-ready" "$body"
rm -f "$body"

echo "Export foundation issues created successfully."
