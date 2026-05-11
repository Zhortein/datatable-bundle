# Changelog fragments

Add unreleased changelog fragments under:

```text
changelog/unreleased/
```

Supported filename prefixes:

```text
added-
changed-
deprecated-
removed-
fixed-
security-
```

Example:

```text
added-csv-export.md
```

Fragment content:

```md
- Added CSV export writer.
```

Build the unreleased changelog section with:

```bash
composer changelog
```
