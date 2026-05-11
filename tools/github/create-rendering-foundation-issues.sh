#!/usr/bin/env bash

set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI is required. Install it first: https://cli.github.com/"
  exit 1
fi

gh auth status >/dev/null

MILESTONE_TITLE="0.2 - Rendering foundation"

ensure_milestone() {
  if gh api repos/:owner/:repo/milestones --jq ".[] | select(.title == \"${MILESTONE_TITLE}\") | .title" | grep -q "${MILESTONE_TITLE}"; then
    echo "Milestone already exists: ${MILESTONE_TITLE}"
  else
    echo "Creating milestone: ${MILESTONE_TITLE}"
    gh api repos/:owner/:repo/milestones \
      -f title="${MILESTONE_TITLE}" \
      -f description="Rendering foundation: request/result objects, provider contracts, Twig rendering skeleton, Bootstrap templates and Stimulus skeleton."
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
"Implement typed datatable request object" \
"type: feature,area: configuration,priority: high,ai-ready" \
"## Objective

Implement a typed request object used by providers and renderers.

## Context

Architecture decisions introduced a typed datatable request object so providers do not parse Symfony HTTP requests directly.

Relevant docs:
- docs/decisions/0004-vanilla-stimulus-interaction-model.md
- docs/decisions/0005-doctrine-orm-provider-architecture.md

## Scope

- Add a \`DatatableRequest\` value object.
- Store current page.
- Store page size.
- Store optional search query.
- Store optional sort field.
- Store sort direction.
- Store runtime options.
- Add unit tests.

## Out of scope

- Symfony Request parsing.
- Controller implementation.
- Doctrine provider implementation.
- Twig rendering.
- Stimulus controller.

## Constraints

- Follow \`AGENTS.md\`.
- Keep the object typed and immutable if practical.
- Validate page and page size.
- Restrict sort direction to supported values.
- PHPStan max must pass.

## Acceptance criteria

- [ ] \`DatatableRequest\` exists and is covered by tests.
- [ ] Invalid page/page size values are handled safely.
- [ ] Sort direction is normalized or validated.
- [ ] Runtime options are typed through PHPDoc.
- [ ] QA passes.
"

create_issue \
"Implement typed datatable result object" \
"type: feature,area: configuration,priority: high,ai-ready" \
"## Objective

Implement a typed result object returned by data providers.

## Context

Providers should return structured data to renderers instead of raw arrays.

Relevant docs:
- docs/decisions/0005-doctrine-orm-provider-architecture.md

## Scope

- Add a \`DatatableResult\` value object.
- Store rows.
- Store current page.
- Store page size.
- Store total items.
- Store filtered items.
- Store total pages.
- Add unit tests.

## Out of scope

- Doctrine provider.
- Twig renderer.
- Controller.
- Export support.

## Constraints

- Follow \`AGENTS.md\`.
- Rows should be documented with PHPStan-friendly types.
- The object should not render HTML.
- PHPStan max must pass.

## Acceptance criteria

- [ ] \`DatatableResult\` exists and is covered by tests.
- [ ] Pagination metadata is available through getters.
- [ ] Rows are exposed with documented array types.
- [ ] QA passes.
"

create_issue \
"Implement data provider contract and provider registry skeleton" \
"type: feature,area: configuration,priority: high,ai-ready" \
"## Objective

Implement the generic data provider contract and a provider registry skeleton.

## Context

Doctrine ORM will be the first provider, but the bundle must remain extensible to other data sources.

Relevant docs:
- docs/decisions/0005-doctrine-orm-provider-architecture.md

## Scope

- Add \`DataProviderInterface\`.
- Add \`DataProviderRegistry\` or equivalent resolver.
- Allow resolving a provider by explicit provider name.
- Allow resolving the first provider that supports a definition.
- Add meaningful exceptions.
- Add unit tests.

## Out of scope

- Doctrine ORM provider implementation.
- Twig rendering.
- Controller.
- Stimulus controller.

## Constraints

- Follow \`AGENTS.md\`.
- Do not hardcode Doctrine as the only possible provider.
- Do not introduce unnecessary dependencies.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Provider contract exists.
- [ ] Provider registry/resolver exists.
- [ ] Missing providers produce clear exceptions.
- [ ] Tests cover provider resolution.
- [ ] QA passes.
"

create_issue \
"Implement initial Twig renderer skeleton" \
"type: feature,area: twig,area: bootstrap,priority: high,ai-ready" \
"## Objective

Implement the first Twig rendering skeleton without data loading.

## Context

The rendering strategy is Twig-first and Bootstrap-first.

Relevant docs:
- docs/decisions/0003-bootstrap-rendering-strategy.md

## Scope

- Add a renderer service.
- Render the datatable shell.
- Render an empty table state.
- Add initial Bootstrap templates.
- Add tests where practical.
- Keep rendering independent from Doctrine.

## Out of scope

- Ajax controller.
- Stimulus controller.
- Doctrine provider.
- Real row rendering from provider results.
- Actions rendering.

## Constraints

- Follow \`AGENTS.md\`.
- Bootstrap-first.
- Twig-based.
- No jQuery.
- No DataTables.net.
- PHPStan max must pass.
- twigcs must pass.

## Acceptance criteria

- [ ] Renderer service exists.
- [ ] Bootstrap datatable shell template exists.
- [ ] Empty state template exists.
- [ ] Rendering does not require Doctrine.
- [ ] QA passes.
"

create_issue \
"Implement initial Twig function for datatable rendering" \
"type: feature,area: twig,priority: high,ai-ready" \
"## Objective

Implement the initial public Twig API for rendering a datatable.

## Context

The documented expected usage is:

\`\`\`twig
{{ zhortein_datatable('users') }}
\`\`\`

Relevant docs:
- README.md
- docs/basic-usage.md
- docs/decisions/0003-bootstrap-rendering-strategy.md

## Scope

- Add a Twig extension.
- Add the \`zhortein_datatable\` Twig function.
- Resolve the datatable definition through the registry.
- Delegate rendering to the renderer service.
- Add tests.

## Out of scope

- Doctrine provider.
- Ajax data endpoint.
- Stimulus controller.
- Export support.

## Constraints

- Follow \`AGENTS.md\`.
- The Twig extension must remain thin.
- Rendering logic must stay in renderer service/templates.
- PHPStan max must pass.

## Acceptance criteria

- [ ] \`zhortein_datatable()\` Twig function exists.
- [ ] It delegates to a renderer.
- [ ] It resolves datatables by name.
- [ ] Tests cover basic rendering call.
- [ ] QA passes.
"

create_issue \
"Implement initial Ajax controller skeleton" \
"type: feature,area: configuration,priority: medium,ai-ready" \
"## Objective

Implement the first Ajax controller skeleton for future datatable refreshes.

## Context

The Stimulus model and rendering strategy require Ajax endpoints returning server-rendered HTML fragments.

Relevant docs:
- docs/decisions/0003-bootstrap-rendering-strategy.md
- docs/decisions/0004-vanilla-stimulus-interaction-model.md

## Scope

- Add controller class.
- Add route configuration.
- Add placeholder endpoint for datatable fragments.
- Use registry and renderer.
- Return JSON with safe placeholder HTML.
- Add tests where practical.

## Out of scope

- Doctrine provider.
- Full request parsing.
- Full pagination/search/sort behavior.
- Export support.

## Constraints

- Follow \`AGENTS.md\`.
- No application-specific routes.
- No DataTables.net response format.
- No jQuery assumptions.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Controller skeleton exists.
- [ ] Routes are bundle-owned and generic.
- [ ] Response shape follows documented direction.
- [ ] QA passes.
"

create_issue \
"Implement initial Stimulus controller skeleton" \
"type: feature,area: stimulus,priority: medium,ai-ready" \
"## Objective

Implement the initial vanilla Stimulus controller skeleton.

## Context

The frontend controller should update server-rendered fragments and avoid client-side cell rendering.

Relevant docs:
- docs/decisions/0004-vanilla-stimulus-interaction-model.md

## Scope

- Add \`assets/controllers/datatable_controller.js\`.
- Define Stimulus values and targets.
- Add loading/error helpers.
- Add placeholder refresh method using \`fetch()\`.
- Do not implement full pagination/search/sort yet.
- Add documentation notes if needed.

## Out of scope

- Full frontend behavior.
- DataTables.net compatibility.
- jQuery.
- Build tool integration beyond Symfony UX conventions.

## Constraints

- Vanilla JavaScript only.
- No jQuery.
- No DataTables.net.
- Keep controller small.
- Code comments in English.

## Acceptance criteria

- [ ] Stimulus controller skeleton exists.
- [ ] It uses native browser APIs.
- [ ] It exposes documented values/targets.
- [ ] It does not depend on external JS libraries.
"

create_issue \
"Add Symfony test application kernel foundation" \
"type: tests,area: configuration,priority: medium,ai-ready" \
"## Objective

Add a minimal Symfony test kernel foundation to support future functional tests.

## Context

The bundle will need functional tests for dependency injection, Twig rendering, routes and controllers.

## Scope

- Add a minimal test kernel if needed.
- Add test configuration fixtures.
- Keep unit tests working.
- Prepare functional test directory structure.
- Document how functional tests are bootstrapped.

## Out of scope

- Doctrine functional test setup.
- Browser tests.
- Full controller tests.
- Twig rendering feature implementation.

## Constraints

- Follow Symfony bundle testing best practices.
- Keep the test setup minimal.
- Do not introduce unnecessary runtime dependencies.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Test kernel foundation exists or the decision to postpone is documented.
- [ ] Functional tests can be added consistently later.
- [ ] Existing QA remains green.
"

echo "Rendering foundation issues created successfully."
