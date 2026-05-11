#!/usr/bin/env bash

set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI is required. Install it first: https://cli.github.com/"
  exit 1
fi

gh auth status >/dev/null

MILESTONE_TITLE="0.12 - Theming and template override polish"

ensure_milestone() {
  if gh api repos/:owner/:repo/milestones --jq ".[] | select(.title == \"${MILESTONE_TITLE}\") | .title" | grep -q "${MILESTONE_TITLE}"; then
    echo "Milestone already exists: ${MILESTONE_TITLE}"
  else
    echo "Creating milestone: ${MILESTONE_TITLE}"
    gh api repos/:owner/:repo/milestones \
      -f title="${MILESTONE_TITLE}" \
      -f description="Template override documentation, stable template context, Bootstrap variants, theme configuration polish and renderer customization."
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

Document the complete Twig template structure and override strategy.

## Context

The bundle now contains multiple Bootstrap templates, cell templates, action templates and toolbar partials.

Applications need a clear reference for overriding templates without forking the bundle.

## Scope

- Create `docs/templates.md`.
- Document the template tree.
- Document Symfony bundle override paths.
- Document which templates are public override points.
- Document which templates are internal/unstable.
- Document cell template context.
- Document action template context.
- Link docs from README and docs index.

## Out of scope

- Code changes to the renderer.
- New theme support.
- Tailwind support.

## Constraints

- Documentation must match implemented templates.
- Examples must be generic.
- No private/client-specific references.
- QA passes.

## Acceptance criteria

- [ ] Template structure is documented.
- [ ] Override paths are documented.
- [ ] Public template context is documented.
- [ ] README and docs index are updated.
- [ ] QA passes.
BODY
)"
create_issue "Document Twig template override strategy" "type: docs,area: twig,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Make the template context explicit and covered by tests.

## Context

Templates currently receive arrays and objects from `DatatableRenderer`, but the expected context is only implicit.

## Scope

- Introduce small documented context arrays or value objects where helpful.
- Add tests that verify expected keys passed to key templates.
- Document context for:
  - datatable shell;
  - toolbar;
  - header;
  - rows;
  - cells;
  - actions;
  - pagination.
- Keep backward compatibility with existing templates.

## Out of scope

- Full DTO refactor if too large.
- New theme support.
- Template override docs beyond context.

## Constraints

- Do not over-engineer.
- Keep Twig templates easy to override.
- PHPStan and TwigCS must pass.

## Acceptance criteria

- [ ] Template context is documented.
- [ ] Key context assumptions are tested.
- [ ] No existing rendering behavior is broken.
- [ ] QA passes.
BODY
)"
create_issue "Stabilize and document template context" "type: feature,area: twig,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Add Bootstrap display variants for common table styles.

## Context

The table currently uses a fixed Bootstrap class set.

Applications may need compact, bordered, borderless, striped or hover variants.

## Scope

- Add runtime/theme options for Bootstrap table classes.
- Support at least:
  - striped;
  - hover;
  - bordered;
  - borderless;
  - small/compact.
- Preserve current defaults.
- Add renderer tests.
- Document usage.

## Out of scope

- Full theming system.
- Tailwind support.
- CSS generation.

## Constraints

- Bootstrap-first.
- Runtime options should remain simple.
- Existing defaults must not break.
- QA passes.

## Acceptance criteria

- [ ] Bootstrap variants can be configured at render time.
- [ ] Defaults remain current behavior.
- [ ] Tests cover variant combinations.
- [ ] Documentation is updated.
- [ ] QA passes.
BODY
)"
create_issue "Implement Bootstrap table display variants" "type: feature,area: bootstrap,area: twig,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Allow Bootstrap defaults to be configured globally.

## Context

Runtime options are useful, but common Bootstrap defaults should also be configurable in bundle configuration.

## Scope

- Extend bundle configuration with Bootstrap table defaults.
- Include options for:
  - striped;
  - hover;
  - bordered;
  - small;
  - responsive.
- Apply defaults in `DatatableRenderer`.
- Allow runtime options to override config.
- Add tests.

## Out of scope

- Multiple themes.
- User preferences.
- CSS generation.

## Constraints

- Keep configuration small and explicit.
- Maintain current default behavior.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Bootstrap table defaults are configurable.
- [ ] Renderer consumes configured defaults.
- [ ] Runtime options override config.
- [ ] Tests cover config and runtime precedence.
- [ ] QA passes.
BODY
)"
create_issue "Configure Bootstrap rendering defaults" "type: feature,area: configuration,area: bootstrap,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Improve the icon rendering strategy for built-in controls without introducing a heavy dependency.

## Context

Actions can define icon CSS classes, but built-in controls such as sorting/export/loading do not have a clear icon strategy.

## Scope

- Document current icon behavior.
- Add optional icon slots/classes where useful.
- Keep icon rendering CSS-class based for now.
- Avoid mandatory icon dependencies.
- Add tests if markup changes.

## Out of scope

- Symfony UX Icons integration.
- FontAwesome dependency.
- Bootstrap Icons dependency.
- SVG icon provider abstraction.

## Constraints

- No mandatory icon package.
- Bootstrap-compatible markup.
- Keep accessibility labels.
- QA passes.

## Acceptance criteria

- [ ] Icon strategy is documented.
- [ ] Built-in controls remain accessible without icons.
- [ ] Optional icon classes are supported where implemented.
- [ ] QA passes.
BODY
)"
create_issue "Polish optional icon rendering strategy" "type: docs,area: twig,area: bootstrap,priority: low,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Add a formal cell template reference.

## Context

The bundle supports typed cell templates and custom templates, but users need a concise reference.

## Scope

- Document built-in cell templates.
- Document cell types.
- Document custom template context.
- Document fallback order.
- Document escaping behavior.
- Document how Doctrine type enrichment interacts with cell types.

## Out of scope

- New cell templates.
- Enum badge rendering.
- Icon rendering.

## Constraints

- Documentation must match current implementation.
- Examples must be generic.
- QA passes.

## Acceptance criteria

- [ ] Cell template reference exists.
- [ ] Fallback order is documented.
- [ ] Template context is documented.
- [ ] README/docs index links are updated if needed.
- [ ] QA passes.
BODY
)"
create_issue "Document cell template reference" "type: docs,area: twig,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Review existing Bootstrap templates for consistency and cleanup.

## Context

Templates have evolved incrementally across many milestones. A consistency pass is needed before more features are added.

## Scope

- Review indentation and Twig style.
- Review Bootstrap classes.
- Review ARIA attributes.
- Review target and value attributes.
- Remove duplicate or obsolete markup.
- Keep snapshots/assertions passing.
- Add tests only if behavior changes.

## Out of scope

- Large redesign.
- New features.
- Theming abstraction.

## Constraints

- No behavior regression.
- TwigCS must pass.
- Existing tests must pass.

## Acceptance criteria

- [ ] Templates are internally consistent.
- [ ] Obsolete markup is removed.
- [ ] Existing behavior is preserved.
- [ ] QA passes.
BODY
)"
create_issue "Review and clean Bootstrap templates" "type: refactor,area: twig,area: bootstrap,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Document theming and rendering limitations before the next major feature milestone.

## Context

The bundle currently supports Bootstrap-first rendering only.

Users should understand what is stable, what can be overridden and what is not supported yet.

## Scope

- Update `docs/architecture.md`.
- Update `docs/configuration.md`.
- Update `docs/basic-usage.md` if needed.
- Document current theme limitations.
- Document future theme extension direction.
- Update roadmap milestone 0.12 when completed.

## Out of scope

- Implementing another theme.
- Tailwind support.
- Full design system abstraction.

## Constraints

- Documentation must match current behavior.
- Keep future claims conservative.
- QA passes.

## Acceptance criteria

- [ ] Theming limitations are documented.
- [ ] Override strategy is clearly explained.
- [ ] Roadmap is updated.
- [ ] QA passes.
BODY
)"
create_issue "Document theming limitations and roadmap" "type: docs,area: twig,area: bootstrap,priority: medium,ai-ready" "$body"
rm -f "$body"

echo "Theming and template override issues created successfully."
