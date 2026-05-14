#!/usr/bin/env bash

set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI is required. Install it first: https://cli.github.com/"
  exit 1
fi

gh auth status >/dev/null

MILESTONE_TITLE="0.20 - XLSX export decision"

ensure_milestone() {
  if gh api repos/:owner/:repo/milestones --jq ".[] | select(.title == \"${MILESTONE_TITLE}\") | .title" | grep -q "${MILESTONE_TITLE}"; then
    echo "Milestone already exists: ${MILESTONE_TITLE}"
  else
    echo "Creating milestone: ${MILESTONE_TITLE}"
    gh api repos/:owner/:repo/milestones \
      -f title="${MILESTONE_TITLE}" \
      -f description="Decide and implement the XLSX export strategy: optional dependency, writer design, memory/streaming constraints, tests and documentation."
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

Decide whether XLSX export belongs in the core bundle, in an optional writer, or in a separate package.

## Context

CSV export is implemented and dependency-free.

XLSX export is useful for business users, but it requires a spreadsheet writer dependency and can have memory/performance consequences.

## Scope

- Evaluate possible strategies:
  - core optional writer;
  - optional Composer dependency;
  - separate `zhortein/datatable-xlsx-export-bundle`;
  - no XLSX for now.
- Evaluate OpenSpout as candidate writer.
- Evaluate memory/streaming constraints.
- Evaluate API impact on `ExportWriterInterface`.
- Document final decision in an ADR.

## Out of scope

- Implementing XLSX writer.
- Adding dependencies.
- Changing CSV behavior.

## Constraints

- Keep core bundle lightweight where possible.
- Avoid mandatory spreadsheet dependency unless strongly justified.
- Keep public API stable enough for alpha users.

## Acceptance criteria

- [ ] XLSX strategy decision is documented.
- [ ] Dependency strategy is documented.
- [ ] Writer ownership is decided.
- [ ] Implementation issues are adjusted based on the decision.
- [ ] QA passes.
BODY
)"
create_issue "Decide XLSX export strategy" "type: architecture,area: export,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Add XLSX export format support at the enum/request level only if the architecture decision accepts it.

## Context

`ExportFormat` currently supports CSV only.

If XLSX is implemented in the core bundle or as an optional writer, the export format enum and request parsing need to support it.

## Scope

- Add `ExportFormat::Xlsx` if accepted.
- Add content type and file extension.
- Update route requirements if needed.
- Update export request tests.
- Ensure unsupported format handling remains clear.

## Out of scope

- XLSX writer implementation.
- OpenSpout dependency.
- UI control rendering.

## Constraints

- Do not expose XLSX format unless a writer strategy exists.
- Keep invalid format errors explicit.
- QA passes.

## Acceptance criteria

- [ ] XLSX format is supported only if writer strategy is accepted.
- [ ] Tests cover CSV and XLSX format metadata.
- [ ] Invalid formats are still rejected.
- [ ] QA passes.
BODY
)"
create_issue "Add XLSX export format support" "type: feature,area: export,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Introduce an optional XLSX writer implementation if the strategy decision keeps XLSX in the core bundle.

## Context

XLSX export likely requires a third-party writer such as OpenSpout.

This should remain optional if possible.

## Scope

- Add optional dependency strategy.
- Implement `XlsxExportWriter`.
- Register the writer only when the dependency is available.
- Use visible/exportable columns.
- Use column labels as headers.
- Normalize cell values safely.
- Add unit tests.
- Add functional writer resolution test if applicable.

## Out of scope

- Async exports.
- Complex styling.
- Formulas.
- Multi-sheet exports.
- Charts.
- Images.

## Constraints

- Dependency must not break users who only need CSV.
- Memory usage must be considered.
- No frontend XLSX generation.
- QA passes.

## Acceptance criteria

- [ ] XLSX writer exists if strategy accepts core implementation.
- [ ] Writer registration is conditional or dependency strategy is explicit.
- [ ] Headers and rows are exported.
- [ ] Date/boolean/scalar values are handled.
- [ ] Tests cover XLSX response.
- [ ] QA passes.
BODY
)"
create_issue "Implement optional XLSX export writer" "type: feature,area: export,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Update export controls to expose XLSX only when XLSX writer support is available.

## Context

The toolbar currently renders CSV export controls.

If XLSX is supported, the UI should not show XLSX actions unless the writer is available.

## Scope

- Add runtime/config option for enabled export formats if needed.
- Render CSV controls as before.
- Render XLSX current/full controls only when enabled.
- Keep current/full mode semantics.
- Add tests.

## Out of scope

- Async export UI.
- Export progress UI.
- Format icons unless already supported by generic action/icon strategy.

## Constraints

- Do not render broken XLSX links.
- Preserve existing CSV behavior.
- Bootstrap-first.
- QA passes.

## Acceptance criteria

- [ ] CSV controls remain unchanged.
- [ ] XLSX controls render only when available/enabled.
- [ ] Current/full mode URLs are correct.
- [ ] Tests cover enabled/disabled XLSX UI.
- [ ] QA passes.
BODY
)"
create_issue "Render XLSX export controls conditionally" "type: feature,area: export,area: twig,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Add frontend tests for export URL generation with multiple export formats if XLSX is supported.

## Context

Stimulus export URL generation currently covers CSV.

If XLSX is added, the frontend should preserve format-specific URLs and current/full state.

## Scope

- Test XLSX current export URL.
- Test XLSX full export URL.
- Test custom export URL if applicable.
- Ensure CSV tests remain valid.
- Ensure state serialization remains shared.

## Out of scope

- XLSX file content.
- Download assertions.
- Backend writer tests.

## Constraints

- No real navigation.
- Use existing export URL test patterns.
- QA passes.

## Acceptance criteria

- [ ] XLSX export URL generation is tested if XLSX is supported.
- [ ] CSV URL tests remain green.
- [ ] Frontend tests pass.
- [ ] QA passes.
BODY
)"
create_issue "Test Stimulus XLSX export URL generation" "type: tests,area: stimulus,area: export,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Document XLSX export behavior, installation and limitations.

## Context

XLSX export has different dependency and performance considerations from CSV.

Users need clear guidance.

## Scope

- Update `docs/exports.md`.
- Add or update installation/configuration docs.
- Document whether XLSX is core, optional or separate package.
- Document dependency requirements.
- Document current/full mode behavior.
- Document limitations.
- Update README/docs index if needed.

## Out of scope

- New implementation work.
- Performance benchmark.

## Constraints

- Documentation must match the strategy decision.
- Avoid over-promising spreadsheet features.
- QA passes.

## Acceptance criteria

- [ ] XLSX strategy is documented.
- [ ] Installation requirements are documented.
- [ ] Usage examples are documented.
- [ ] Limitations are explicit.
- [ ] QA passes.
BODY
)"
create_issue "Document XLSX export strategy and usage" "type: docs,area: export,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Review memory/performance implications of XLSX export and document safe usage.

## Context

Spreadsheet exports can become memory-heavy.

The provider currently returns rows through `DatatableResult`, and CSV is string/stream friendly.

XLSX may need streaming behavior for large datasets.

## Scope

- Review current export memory behavior.
- Document safe row-count expectations.
- Decide whether XLSX export should be limited.
- Decide whether async/streaming export is required before stable support.
- Create follow-up issues if needed.

## Out of scope

- Async export implementation.
- Benchmark suite.
- Queue integration.

## Constraints

- Be conservative.
- Do not claim large dataset support without proof.
- QA passes if docs are updated.

## Acceptance criteria

- [ ] Memory/performance implications are reviewed.
- [ ] Current limitations are documented.
- [ ] Follow-up issues are created if needed.
- [ ] QA passes.
BODY
)"
create_issue "Review XLSX export memory and performance constraints" "type: docs,area: export,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Update roadmap after the XLSX export decision milestone.

## Context

After the XLSX strategy is decided and any accepted implementation/documentation work is completed, roadmap should reflect the result.

## Scope

- Update `docs/roadmap.md`.
- Mark 0.20 as completed.
- Clarify whether XLSX was implemented, postponed or moved to a separate package.
- Define the next milestone direction.
- Update current limitations.

## Out of scope

- New export implementation.
- New Doctrine features.

## Constraints

- Roadmap must reflect the actual decision.
- QA passes.

## Acceptance criteria

- [ ] Roadmap reflects XLSX decision.
- [ ] Next milestone direction is clear.
- [ ] QA passes.
BODY
)"
create_issue "Update roadmap after XLSX export decision" "type: docs,area: export,priority: medium,ai-ready" "$body"
rm -f "$body"

echo "XLSX export decision issues created successfully."
