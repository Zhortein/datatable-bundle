# Migrating from 1.x to 2.0

This guide covers the theme-boundary changes introduced in the 2.0 beta. Data providers,
definitions, filters, actions and exports are unaffected by this migration.

## Default Bootstrap applications

Bootstrap remains the default theme. Keep Bootstrap 5 CSS and JavaScript in the
host application because the maintained templates use dropdown and collapse
behaviors.

The bundle's Stimulus package no longer imports Bootstrap itself. This removes
Bootstrap from its JavaScript peer/importmap dependencies; it does not install
Bootstrap for the host.

## Template overrides

Every renderer entry point now receives a `theme` metadata object. Replace
hard-coded nested includes:

```twig
{% include '@ZhorteinDatatable/bootstrap/_pagination.html.twig' %}
```

with:

```twig
{% include theme.template('_pagination.html.twig') %}
```

When an include uses `only`, pass `theme` explicitly if the included template
resolves another theme partial.

## Confirmation dialogs

The Bootstrap JavaScript modal adapter has been replaced by a native
`<dialog>`. If `_confirmation_modal.html.twig` was overridden:

- render a `<dialog>` with the documented `confirmationModal` target;
- keep the `confirmationMessage` and `confirmationConfirmButton` targets;
- call `cancelPendingAction` from cancel controls;
- preserve the native `cancel` event for Escape-key behavior.

## Custom themes

`default_theme` now accepts any non-empty registered name. A custom name without
a matching `ThemeInterface` service fails explicitly.

Move default cell alignment out of renderer decoration and into
`ThemeInterface::getDefaultCellClassName()`. Declare template prefix,
capabilities and asset requirements with immutable `ThemeMetadata`.

See [Theme extension contract](theme-contract.md) for the full template and
frontend compatibility matrix.
