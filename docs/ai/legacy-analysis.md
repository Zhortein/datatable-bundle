# Legacy NC Manager Datatable Analysis

The files in `docs/legacy-ncmanager` come from the NC Manager project.

They must be used as functional inspiration only.

## Keep

- PHP attribute-based datatable declaration.
- Dedicated datatable classes in the host application.
- Fluent API to add columns.
- Automatic Doctrine field type detection.
- Template resolution by field type.
- Persistent filters.
- Custom joins.
- Ajax endpoints for columns and data.
- Future export support.

## Do not copy directly

- Do not keep the `DataTableNet` or `DatatableNet` names.
- Do not depend on DataTables.net.
- Do not depend on jQuery.
- Do not depend on Select2.
- Do not depend on BazingaJsTranslationBundle.
- Do not instantiate datatable classes manually with `new`.
- Do not scan all `App\\` services manually.
- Do not mix Doctrine query building, Twig rendering, export, and HTTP handling in one class.
- Do not depend on application-specific services like Historizer, CacheManager or LocaleProvider.
- Do not use project-specific paths such as `templates/parts/datatable_rendering`.

## Target architecture

The final bundle must follow this responsibility split:

```text
Datatable class
→ DatatableDefinition
→ ColumnDefinition / ActionDefinition / FilterDefinition
→ Registry Symfony
→ DataProvider
→ Twig renderer
→ Ajax controller
→ Stimulus vanilla controller

