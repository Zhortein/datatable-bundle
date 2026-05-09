#!/usr/bin/env bash

set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI is required. Install it first: https://cli.github.com/"
  exit 1
fi

gh auth status >/dev/null

MILESTONE_TITLE="0.1 - Foundation"

ensure_milestone() {
  if gh api repos/:owner/:repo/milestones --jq ".[] | select(.title == \"${MILESTONE_TITLE}\") | .title" | grep -q "${MILESTONE_TITLE}"; then
    echo "Milestone already exists: ${MILESTONE_TITLE}"
  else
    echo "Creating milestone: ${MILESTONE_TITLE}"
    gh api repos/:owner/:repo/milestones \
      -f title="${MILESTONE_TITLE}" \
      -f description="Initial bundle foundation: contracts, definitions, registry, documentation and CI."
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
"Define the initial public datatable declaration API" \
"type: architecture,area: configuration,priority: high,ai-ready" \
"## Objective

Define the first public API for declaring datatables in host Symfony applications.

## Context

The bundle already contains a minimal \`#[AsDatatable]\` attribute and \`DatatableInterface\`.

This issue should refine the initial API shape without implementing the registry or Doctrine provider yet.

## Scope

- Review \`AsDatatable\`.
- Review \`DatatableInterface\`.
- Review \`DatatableDefinition\`.
- Decide whether a context object is needed now or later.
- Keep the API minimal and Symfony-friendly.
- Add or update unit tests.
- Update documentation if the public API changes.

## Out of scope

- Datatable registry.
- Doctrine data provider.
- Twig rendering.
- Ajax controller.
- Stimulus controller.
- Export support.

## Constraints

- Follow \`AGENTS.md\`.
- Code and comments must be written in English.
- No DataTables.net.
- No jQuery.
- No unnecessary dependencies.
- Public API must remain explicit and typed.

## Acceptance criteria

- [ ] Public declaration API is clear.
- [ ] Unit tests cover attribute and definition behavior.
- [ ] Documentation examples are aligned with the implemented API.
- [ ] QA passes.
"

create_issue \
"Implement datatable definition value objects" \
"type: feature,area: configuration,priority: high,ai-ready" \
"## Objective

Implement the initial set of value objects used to describe a datatable.

## Context

The bundle needs explicit definition objects before implementing registration, rendering or data loading.

## Scope

- Improve \`DatatableDefinition\`.
- Improve \`ColumnDefinition\`.
- Add \`ActionDefinition\` if needed.
- Add \`FilterDefinition\` if needed.
- Keep mutability limited to the builder-style API.
- Add unit tests.

## Out of scope

- Doctrine query building.
- Twig rendering.
- Ajax responses.
- JavaScript behavior.
- Export support.

## Constraints

- Follow \`AGENTS.md\`.
- No monolithic datatable class.
- No application-specific code.
- No raw legacy source reuse.
- Public methods must be typed.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Datatable definitions can describe entity class, translation domain and columns.
- [ ] Column definitions expose name, label, visibility, sorting, search and CSS class metadata.
- [ ] Tests cover the fluent API.
- [ ] QA passes.
"

create_issue \
"Implement datatable service discovery and registry" \
"type: feature,area: registry,priority: high,ai-ready" \
"## Objective

Implement Symfony-native datatable discovery and registry.

## Context

Datatable classes must be regular Symfony services discovered through \`#[AsDatatable]\`, autoconfiguration and service tags.

## Scope

- Add a service tag for datatables.
- Add autoconfiguration for classes using \`#[AsDatatable]\`.
- Implement a \`DatatableRegistry\`.
- Resolve datatables by name.
- Throw explicit exceptions when a datatable is missing or duplicated.
- Add unit tests.

## Out of scope

- Controller endpoints.
- Doctrine provider.
- Twig rendering.
- Stimulus controller.

## Constraints

- Do not scan all application services manually.
- Do not instantiate datatables manually with \`new\`.
- Use Symfony dependency injection properly.
- Follow \`AGENTS.md\`.

## Acceptance criteria

- [ ] Datatables are discoverable through Symfony service tags.
- [ ] Registry resolves a datatable by name.
- [ ] Duplicate names are detected.
- [ ] Missing datatables produce a clear exception.
- [ ] QA passes.
"

create_issue \
"Design the initial Bootstrap rendering strategy" \
"type: architecture,area: twig,area: bootstrap,priority: medium,ai-ready" \
"## Objective

Design the first Twig and Bootstrap rendering strategy.

## Context

The bundle must render Bootstrap-first datatables without relying on DataTables.net.

## Scope

- Propose the initial Twig template structure.
- Define override strategy for host applications.
- Define how columns, rows and actions should be rendered.
- Define empty, loading and error states.
- Document the chosen approach.

## Out of scope

- Full implementation.
- JavaScript behavior.
- Doctrine data loading.
- Export support.

## Constraints

- Follow Symfony bundle best practices.
- Use Bootstrap-friendly markup.
- Do not introduce Tailwind.
- Do not introduce jQuery.
- Do not introduce DataTables.net.

## Acceptance criteria

- [ ] A documented rendering strategy exists.
- [ ] Template responsibilities are clearly split.
- [ ] Override strategy is documented.
- [ ] Follow-up implementation issues can be created from this decision.
"

create_issue \
"Design the vanilla Stimulus interaction model" \
"type: architecture,area: stimulus,priority: medium,ai-ready" \
"## Objective

Design the frontend interaction model for the datatable Stimulus controller.

## Context

The bundle must use vanilla JavaScript through Stimulus. No jQuery and no DataTables.net are allowed.

## Scope

- Define controller responsibilities.
- Define expected data attributes.
- Define Ajax request parameters.
- Define loading and error behavior.
- Define pagination, search and sorting interactions.
- Document the proposed model.

## Out of scope

- Full implementation.
- Doctrine provider.
- Twig rendering implementation.
- Export support.

## Constraints

- Vanilla JavaScript only.
- Stimulus is allowed as Symfony UX integration layer.
- No jQuery.
- No DataTables.net.
- Keep HTML progressive-enhancement friendly.

## Acceptance criteria

- [ ] Stimulus controller responsibilities are documented.
- [ ] Expected HTML data attributes are documented.
- [ ] Ajax payload shape is proposed.
- [ ] Follow-up implementation issues can be created.
"

create_issue \
"Design the Doctrine ORM provider architecture" \
"type: architecture,area: doctrine,priority: high,ai-ready" \
"## Objective

Design the Doctrine ORM provider architecture before implementation.

## Context

Doctrine ORM will be the first data provider, but the bundle must remain extensible to other data sources later.

## Scope

- Define provider interface expectations.
- Define Doctrine provider responsibilities.
- Define metadata type guessing responsibilities.
- Define pagination, sorting and search responsibilities.
- Define how persistent filters should be represented.
- Document compatibility expectations for Doctrine ORM 3 and 4.

## Out of scope

- Full Doctrine provider implementation.
- Twig rendering.
- Stimulus controller.
- Export support.

## Constraints

- Keep provider architecture extensible.
- Avoid database-specific behavior unless explicit.
- No PostgreSQL-only assumptions.
- Follow \`AGENTS.md\`.

## Acceptance criteria

- [ ] Doctrine provider responsibilities are documented.
- [ ] Type guessing strategy is documented.
- [ ] Persistent filter strategy is documented.
- [ ] Follow-up implementation issues can be created.
"

create_issue \
"Prepare first release documentation structure" \
"type: docs,priority: medium,ai-ready" \
"## Objective

Prepare the documentation structure for the first development releases.

## Context

The bundle should have a clear README and developer-oriented docs from the beginning.

## Scope

- Review README structure.
- Add installation placeholder.
- Add basic usage placeholder.
- Add configuration placeholder.
- Add development workflow section.
- Link architecture, features and roadmap docs.

## Out of scope

- Full end-user documentation.
- Provider-specific documentation.
- Rendering-specific documentation.

## Constraints

- Documentation must not mention private application names.
- Documentation must not include legacy source code.
- Keep examples generic and sanitized.

## Acceptance criteria

- [ ] README links to relevant docs.
- [ ] Documentation structure is clear.
- [ ] No private/client-specific information is present.
- [ ] QA passes.
"

echo "Foundation issues created successfully."