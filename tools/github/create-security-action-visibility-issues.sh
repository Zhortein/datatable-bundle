#!/usr/bin/env bash

set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI is required. Install it first: https://cli.github.com/"
  exit 1
fi

gh auth status >/dev/null

MILESTONE_TITLE="0.13 - Security and action visibility"

ensure_milestone() {
  if gh api repos/:owner/:repo/milestones --jq ".[] | select(.title == \"${MILESTONE_TITLE}\") | .title" | grep -q "${MILESTONE_TITLE}"; then
    echo "Milestone already exists: ${MILESTONE_TITLE}"
  else
    echo "Creating milestone: ${MILESTONE_TITLE}"
    gh api repos/:owner/:repo/milestones \
      -f title="${MILESTONE_TITLE}" \
      -f description="Action visibility, authorization extension points, confirmation metadata and security documentation."
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

Introduce an action visibility extension point independent from Symfony Security.

## Context

Actions are currently always rendered when declared.

Applications need to hide actions depending on row data, context, business rules or security rules.

## Scope

- Add an action visibility checker interface.
- Add a default implementation that allows all actions.
- Define a small context object if needed.
- Support row actions and global actions.
- Add unit tests.

## Out of scope

- Symfony voter integration.
- Security expressions.
- Per-user persistence.
- JavaScript confirmation handling.

## Constraints

- No mandatory dependency on Symfony Security.
- Keep the extension point simple and replaceable.
- Do not couple to a User entity.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Action visibility interface exists.
- [ ] Default implementation allows all actions.
- [ ] Renderer can ask the visibility checker.
- [ ] Tests cover visible and hidden actions.
- [ ] QA passes.
BODY
)"
create_issue "Implement action visibility extension point" "type: feature,area: security,area: twig,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Apply action visibility rules when rendering row actions.

## Context

Row action rendering currently renders every declared action.

After the visibility extension point exists, row actions should be filtered before rendering.

## Scope

- Apply visibility checker to row actions.
- Pass row data to the visibility context.
- Keep route parameter resolution only for visible actions.
- Add tests.

## Out of scope

- Symfony voters.
- Global actions.
- JavaScript confirmation.
- Batch actions.

## Constraints

- Do not generate URLs for hidden actions.
- Keep renderer behavior deterministic.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Hidden row actions are not rendered.
- [ ] Visible row actions are still rendered.
- [ ] URL generation is skipped for hidden actions.
- [ ] Tests cover row action visibility.
- [ ] QA passes.
BODY
)"
create_issue "Apply visibility rules to row actions" "type: feature,area: security,area: twig,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Apply action visibility rules when rendering global actions.

## Context

Global actions are currently always rendered when declared.

After the visibility extension point exists, global actions should be filterable too.

## Scope

- Apply visibility checker to global actions.
- Use a context without row data.
- Keep URL generation only for visible actions.
- Add tests.

## Out of scope

- Symfony voters.
- Batch action selection state.
- JavaScript confirmation.

## Constraints

- Do not generate URLs for hidden actions.
- Keep behavior consistent with row actions.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Hidden global actions are not rendered.
- [ ] Visible global actions are still rendered.
- [ ] URL generation is skipped for hidden global actions.
- [ ] Tests cover global action visibility.
- [ ] QA passes.
BODY
)"
create_issue "Apply visibility rules to global actions" "type: feature,area: security,area: twig,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Add optional Symfony AuthorizationChecker integration for action visibility.

## Context

The core visibility extension point should not require Symfony Security, but applications using Symfony Security should have a convenient adapter.

## Scope

- Add optional authorization checker based implementation.
- Support action attributes or permission metadata if needed.
- Keep dependency optional or guarded.
- Add tests with a mock AuthorizationChecker.

## Out of scope

- Mandatory security-bundle dependency.
- Built-in voters.
- Application-specific roles.
- Security expression language.

## Constraints

- The bundle must remain usable without Symfony Security.
- Keep implementation replaceable.
- PHPStan max must pass.

## Acceptance criteria

- [ ] Optional Symfony authorization checker adapter exists.
- [ ] Adapter is not required for basic usage.
- [ ] Tests cover granted and denied behavior.
- [ ] QA passes.
BODY
)"
create_issue "Add optional Symfony authorization action visibility adapter" "type: feature,area: security,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Expose confirmation metadata in action rendering.

## Context

`ActionDefinition` already has `confirmationMessage`, but templates and frontend behavior do not use it consistently yet.

## Scope

- Render confirmation metadata on action links/buttons/forms.
- Add `data-confirmation-message` or a namespaced equivalent.
- Keep behavior passive until JS confirmation is implemented.
- Add tests.

## Out of scope

- Browser confirmation dialog behavior.
- Modal confirmation UI.
- Translated confirmation helper.
- Action visibility rules.

## Constraints

- Do not introduce new JavaScript dependencies.
- Keep markup accessible.
- TwigCS must pass.

## Acceptance criteria

- [ ] Confirmation message appears in action markup as data attribute.
- [ ] Existing action rendering still works.
- [ ] Tests cover GET and non-GET actions.
- [ ] QA passes.
BODY
)"
create_issue "Render action confirmation metadata" "type: feature,area: twig,area: security,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Implement optional vanilla JavaScript confirmation behavior for actions.

## Context

Action confirmation metadata can be rendered as data attributes. The Stimulus controller should optionally intercept actions that require confirmation.

## Scope

- Add a small confirmation handler in the Stimulus controller.
- Use native `window.confirm()` for the first implementation.
- Support links and forms.
- Keep behavior opt-in through data attributes.
- Add documentation notes.

## Out of scope

- Bootstrap modal confirmation.
- Custom confirmation provider.
- Async confirmation.
- Translation catalog for dynamic messages.

## Constraints

- Vanilla JavaScript only.
- No external dependency.
- Must not affect actions without confirmation metadata.
- QA passes.

## Acceptance criteria

- [ ] Actions with confirmation metadata trigger confirmation.
- [ ] Cancel prevents navigation/submission.
- [ ] Actions without metadata are unaffected.
- [ ] Documentation updated.
BODY
)"
create_issue "Implement vanilla action confirmation behavior" "type: feature,area: stimulus,area: security,priority: medium,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Review and harden CSRF-related action rendering.

## Context

Non-GET actions are rendered as forms with CSRF tokens when available.

Before further action features, the behavior should be reviewed and documented.

## Scope

- Review CSRF token id strategy.
- Ensure non-GET actions never render as simple links.
- Ensure GET actions do not include tokens.
- Add or improve tests.
- Document current CSRF behavior.

## Out of scope

- Controller action handlers.
- Symfony form integration.
- Voters/authorization.

## Constraints

- Follow Symfony security best practices.
- Keep CSRF manager optional.
- QA passes.

## Acceptance criteria

- [ ] CSRF token id strategy is documented.
- [ ] Non-GET actions render as forms.
- [ ] GET actions render as links.
- [ ] Tests cover CSRF behavior.
- [ ] QA passes.
BODY
)"
create_issue "Review CSRF action rendering behavior" "type: tests,area: security,area: twig,priority: high,ai-ready" "$body"
rm -f "$body"

body="$(make_body <<'BODY'
## Objective

Document action security, visibility and confirmation behavior.

## Context

After action visibility and confirmation features are implemented, users need clear guidance.

## Scope

- Update `docs/actions-and-cells.md`.
- Add a dedicated security/action section if needed.
- Document visibility extension point.
- Document optional Symfony authorization adapter.
- Document CSRF behavior.
- Document confirmation metadata and JS behavior.
- Update README/docs index if a new doc is added.

## Out of scope

- Application-specific voters.
- Full security cookbook.
- Controller handling examples beyond generic guidance.

## Constraints

- Documentation must match implemented behavior.
- Examples must remain generic.
- No private/client-specific references.
- QA passes.

## Acceptance criteria

- [ ] Action visibility is documented.
- [ ] CSRF action rendering is documented.
- [ ] Confirmation behavior is documented.
- [ ] Current limitations are explicit.
- [ ] QA passes.
BODY
)"
create_issue "Document action security and visibility" "type: docs,area: security,priority: medium,ai-ready" "$body"
rm -f "$body"

echo "Security and action visibility issues created successfully."
