# Migrating from 1.x to 2.0

This guide covers the theme-boundary changes of 2.0, its Doctrine requirement
and the defaults it changes. Definitions written for 1.x keep working unchanged.

Applications replacing an older, application-specific DatatableNet or
DataTables.net layer should use the separate
[DatatableNet-style application migration guide](legacy-datatablenet-migration.md).

## Default Bootstrap applications

Bootstrap remains the default theme. Keep Bootstrap 5 CSS and JavaScript in the
host application because the maintained templates use dropdown and collapse
behaviors.

The bundle's Stimulus package no longer imports Bootstrap itself. This removes
Bootstrap from its JavaScript peer/importmap dependencies; it does not install
Bootstrap for the host.

## Doctrine

Doctrine-backed datatables require Doctrine ORM 3.7 or later. Run
`composer update doctrine/orm` before upgrading the bundle.

## Changed defaults

- An aggregate column is sortable. Pass `sortable: false` to keep a header fixed.
- The Search Builder shows whenever the definition declares an advanced filter
  field. Pass `searchBuilder: false` to the Twig function to hide it.
- A date-only bound covers its whole day: an end date now includes the rows of
  that day.
- Every Doctrine sort ends on the identifier, so rows tied on the sorted value
  keep one order across pages.
- A join following a collection lists each entity once, unless a column of that
  collection is shown.

A default order that pages repeated through the `sorts` Twig option can move to
the definition with `setDefaultSorts()`.

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

An overridden `datatable.html.twig` shell must pass the initial filters to the
controller, as an object even when empty:

```twig
{% set initial_filters = filters|default({}) is empty ? '{}' : filters|json_encode %}
data-zhortein--datatable-bundle--datatable-filters-value="{{ initial_filters|e('html_attr') }}"
```

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
