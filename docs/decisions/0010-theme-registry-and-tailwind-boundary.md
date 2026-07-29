# 0010 - Theme registry and Tailwind boundary

## Status

Accepted for 2.0

## Context

The 1.x renderer accepted a `default_theme` name, but its partials included
`bootstrap/...` templates directly. The renderer also selected Bootstrap cell
alignment classes, while the Stimulus controller imported Bootstrap's `Modal`
class and generated Bootstrap classes for dynamic controls. A second theme
could therefore not be implemented without forking PHP and JavaScript code.

The 2.0 boundary must preserve server-rendered Twig fragments, vanilla
JavaScript and the existing Stimulus state machine while making presentation
ownership explicit.

## Decision

- Themes are services implementing `ThemeInterface`.
- Theme services are autoconfigured with the `zhortein_datatable.theme` tag.
- `ThemeMetadata` is immutable and declares the theme name, Twig template
  prefix, capabilities and asset requirements.
- Every bundled partial resolves nested templates through the selected
  metadata. A theme cannot silently fall back to Bootstrap markup.
- Explicit column templates remain authoritative. Otherwise, typed cell
  templates and default cell classes come from the selected theme.
- The Stimulus controller owns state and behavior only. Theme templates provide
  presentation classes for elements created or toggled at runtime.
- Action confirmation uses the native `<dialog>` contract. It no longer imports
  a UI framework adapter.
- Bootstrap remains the default and only theme maintained in the core package.
- A maintained Tailwind implementation must live in an optional package. It
  may depend on the public theme contract but must own its templates, CSS build
  instructions, content scanning and release lifecycle.

## Template resolution precedence

1. an explicit `ColumnDefinition::getTemplate()` value;
2. the selected theme's template prefix;
3. Symfony/Twig overrides for that template namespace and name.

There is deliberately no cross-theme fallback. Mixing Bootstrap partials into
another theme would make asset requirements implicit and produce markup that
only fails at runtime.

## Tailwind evaluation

An in-core Tailwind theme was rejected for the first 2.0 delivery:

- utility classes must be visible to the host application's Tailwind content
  scanner or safelist;
- Preflight and host design tokens affect the generated markup;
- the bundle does not own a CSS compilation pipeline;
- shipping two complete template suites would double interaction and
  accessibility maintenance.

The registry and the custom-theme renderer test prove the adapter boundary
without adding Tailwind as a runtime or development dependency. A separate
adapter can be evaluated against the compatibility matrix before it is
advertised as maintained.

## Consequences

- Bootstrap applications keep the same default theme and table options.
- The frontend controller no longer has a Bootstrap module dependency.
- Theme packages can be installed without decorating the renderer.
- Selecting an unregistered theme fails explicitly and lists registered names.
- Custom 1.x template overrides that directly include Bootstrap partials must
  be updated for 2.0.
