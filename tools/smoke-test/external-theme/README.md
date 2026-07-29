# Acme external datatable theme fixture

This package is intentionally kept outside the bundle namespace and source
tree. The fresh-application smoke test installs it through Composer, enables
its Symfony bundle and selects its `acme` theme.

Its complete template surface is owned by this package. Templates resolve
nested partials through the selected theme metadata and never include the core
Bootstrap namespace directly.
