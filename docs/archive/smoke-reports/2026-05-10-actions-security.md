# Smoke test report — actions and security integration

## Metadata

| Field | Value |
|---|---|
| Date | 2026-05-10 |
| Bundle branch | develop |
| Smoke app | Fresh Symfony application |
| Area | Actions and security |

## Validated behavior

- Row GET actions render as links.
- Global GET actions render as links.
- Non-GET row actions render as forms.
- Non-GET row actions include `_method`.
- Non-GET row actions include CSRF token when CSRF is available.
- Confirmation metadata is rendered.
- Stimulus confirmation behavior works.
- Cancelling confirmation prevents form submission.
- Replacing `ActionVisibilityCheckerInterface` works.
- Hidden row actions are not rendered.
- Visible row actions remain rendered.

## Issues found

### Non-blocking smoke app routing issue

The `create` route was initially matched by the dynamic `/smoke/users/{id}` route, causing `create` to be passed as a string `$id`.

Resolution:

- Add numeric requirements to routes using `{id}`.
- Or define the static `/smoke/users/create` route before dynamic routes.

This is an application routing issue, not a bundle issue.

## Outcome

The action and security smoke path is validated for first alpha preparation.