#!/usr/bin/env bash

set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI is required. Install it first: https://cli.github.com/"
  exit 1
fi

gh auth status >/dev/null

MILESTONE_TITLE="0.6 - Configuration and Symfony integration foundation"

ensure_milestone() {
  if gh api repos/:owner/:repo/milestones --jq ".[] | select(.title == \"${MILESTONE_TITLE}\") | .title" | grep -q "${MILESTONE_TITLE}"; then
    echo "Milestone already exists: ${MILESTONE_TITLE}"
  else
    echo "Creating milestone: ${MILESTONE_TITLE}"
    gh api repos/:owner/:repo/milestones \
      -f title="${MILESTONE_TITLE}" \
      -f description="Bundle configuration, route integration, translation catalog, Stimulus/AssetMapper integration docs and installation polish."
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

Implement the first public bundle configuration tree.

## Context

The bundle currently has hardcoded defaults in services, renderer and request factory.

The next step is to expose a small, documented configuration surface.

## Scope

- Add configuration support in the bundle class or dedicated configuration object.
- Support default provider.
- Support default theme.
- Support default page size.
- Support maximum page size.
- Support default search enabled flag if appropriate.
- Validate configuration values.
- Add unit tests for configuration processing.

## Out of scope

- Flex recipe.
- Full route customization.
- User preferences.
- Per-datatable runtime configuration persistence.

## Constraints

- Follow Symfony bundle best practices.
- Keep the first configuration surface small.
- Do not introduce legacy Symfony bundle structure.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Configuration key is available.
- [ ] Defaults are defined and tested.
- [ ] Invalid configuration fails clearly.
- [ ] Documentation is updated if needed.
- [ ] QA passes.
BODY
)"
create_issue "Implement bundle configuration" "type: feature,area: configuration,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Use bundle configuration values in services that currently rely on hardcoded defaults.

## Context

After the configuration tree exists, runtime services should consume these values where appropriate.

## Scope

- Inject default theme into the renderer.
- Inject default page size and max page size into request parsing where appropriate.
- Keep runtime options able to override configured defaults.
- Add tests.

## Out of scope

- User-specific preferences.
- Per-datatable persisted configuration.
- Admin UI.

## Constraints

- Keep services testable.
- Avoid container parameter sprawl.
- Maintain explicit constructor dependencies.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Renderer uses configured default theme.
- [ ] Request factory uses configured pagination defaults.
- [ ] Runtime options still override defaults.
- [ ] Tests cover configured defaults.
- [ ] QA passes.
BODY
)"
create_issue "Apply bundle configuration to renderer and request defaults" "type: feature,area: configuration,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Clarify and implement the bundle route loading strategy.

## Context

The bundle contains route definitions, but host applications need a predictable way to load them.

## Scope

- Review current route file.
- Ensure route names and paths are stable.
- Add documentation for importing bundle routes in Symfony applications.
- Add functional tests for route availability in the test kernel.
- Add route configuration examples.

## Out of scope

- Full route customization system.
- Multiple route prefixes per firewall.
- External portal-specific routes.

## Constraints

- Follow Symfony routing best practices.
- Keep route names namespaced.
- Do not introduce application-specific paths.
- QA passes.

## Acceptance criteria

- [ ] Route strategy is documented.
- [ ] Functional tests prove route availability.
- [ ] Route names are stable and prefixed.
- [ ] QA passes.
BODY
)"
create_issue "Finalize bundle route loading strategy" "type: feature,area: configuration,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Introduce the first translation catalog for built-in datatable messages.

## Context

Several labels are currently hardcoded in Twig templates.

Examples include Search, Loading, No data available, Previous, Next, Actions, Yes and No.

## Scope

- Add translation files for at least English and French.
- Define stable translation keys.
- Replace hardcoded labels in Twig templates.
- Update tests to include translation extension where needed.
- Add documentation.

## Out of scope

- All possible languages.
- User-provided translation loader.
- JavaScript-side translation catalog.
- Locale-aware date formatting.

## Constraints

- Use Symfony Translation.
- Keep translation keys stable and namespaced.
- Do not depend on BazingaJsTranslationBundle.
- TwigCS and PHPStan must pass.

## Acceptance criteria

- [ ] Built-in labels use translation keys.
- [ ] English and French catalogs exist.
- [ ] Tests pass with translation extension.
- [ ] Documentation mentions translation domains/keys.
- [ ] QA passes.
BODY
)"
create_issue "Implement built-in translation catalog" "type: feature,area: i18n,area: twig,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Introduce locale-aware datetime rendering for datetime cell templates.

## Context

Datetime cells currently use a fixed format.

The bundle should eventually use Symfony/Twig locale-aware formatting.

## Scope

- Decide the initial locale-aware formatting strategy.
- Update datetime cell template.
- Add tests.
- Keep behavior deterministic in tests.

## Out of scope

- User timezone preferences.
- Per-column date format configuration.
- JavaScript-side formatting.

## Constraints

- Use Symfony/Twig features where possible.
- Keep fallback behavior safe.
- QA passes.

## Acceptance criteria

- [ ] Datetime rendering strategy is documented.
- [ ] Datetime cell rendering is locale-aware or clearly configurable.
- [ ] Tests remain deterministic.
- [ ] QA passes.
BODY
)"
create_issue "Improve datetime cell localization" "type: feature,area: i18n,area: twig,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Document how host Symfony applications should install and load the Stimulus controller.

## Context

The bundle provides a vanilla Stimulus controller, but integration with Symfony UX/AssetMapper must be documented clearly.

## Scope

- Document AssetMapper usage.
- Document Stimulus controller registration.
- Document expected generated controller identifier.
- Add installation notes to README and docs.
- Add examples for importmap/AssetMapper projects.

## Out of scope

- Webpack Encore integration.
- NPM build system.
- Tailwind support.
- Automatic Flex recipe.

## Constraints

- Symfony 8+ first.
- Vanilla JavaScript only.
- No jQuery.
- No DataTables.net.
- Documentation must be accurate.

## Acceptance criteria

- [ ] Stimulus installation docs exist.
- [ ] AssetMapper usage is documented.
- [ ] README links to integration docs.
- [ ] QA passes.
BODY
)"
create_issue "Document Stimulus and AssetMapper integration" "type: docs,area: stimulus,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Improve installation and configuration documentation now that the bundle has a real data/rendering pipeline.

## Context

The README and docs were created early and need to be aligned with current capabilities.

## Scope

- Update README installation section.
- Update docs/installation.md.
- Update docs/configuration.md.
- Link route loading, translations, assets and Doctrine provider docs.
- Keep current limitations explicit.

## Out of scope

- Full Packagist release process.
- Flex recipe.
- Changelog automation.

## Constraints

- Documentation must match implemented behavior.
- No private/client-specific references.
- QA passes.

## Acceptance criteria

- [ ] Installation docs are aligned with current bundle state.
- [ ] Configuration docs are aligned with implemented configuration.
- [ ] README is concise and links to detailed docs.
- [ ] QA passes.
BODY
)"
create_issue "Refresh installation and configuration documentation" "type: docs,area: configuration,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Prepare the package for a first development release by reviewing Composer metadata and public package structure.

## Context

The bundle is getting closer to a first dev release and should expose coherent metadata.

## Scope

- Review composer package name, description, keywords and suggestions.
- Review runtime dependencies vs dev dependencies.
- Ensure optional dependencies remain optional where possible.
- Review autoload and extra Symfony metadata.
- Add missing package support metadata if useful.

## Out of scope

- Packagist publication.
- GitHub release creation.
- Semantic version tag.
- Flex recipe.

## Constraints

- Keep Symfony 8+ target.
- Avoid unnecessary runtime dependencies.
- QA passes.

## Acceptance criteria

- [ ] Composer metadata is coherent.
- [ ] Runtime dependencies are justified.
- [ ] Optional integrations are documented as suggestions when possible.
- [ ] QA passes.
BODY
)"
create_issue "Review Composer package metadata" "type: ci,area: configuration,priority: medium,ai-ready" "$body"
rm -f "$body"

echo "Configuration and Symfony integration issues created successfully."
