# Legacy NC Manager Datatable Analysis

This repository does not contain the original legacy application-specific datatable source files.

This is intentional.

The original implementation belongs to a private application and must not be copied into this public bundle repository.
## Public legacy reference

The public, sanitized legacy reference is split into:

- `docs/legacy-reference/functional-lessons.md`
- `docs/legacy-reference/anti-patterns.md`
- `docs/legacy-reference/sanitized-examples.md`
- `docs/decisions/0001-legacy-code-as-functional-reference-only.md`

## Main conclusion

The previous implementation shows what the bundle should be able to do.

It does not show how the final bundle must be architected.

The final bundle must keep the developer experience and useful business concepts, but must use a clean Symfony bundle architecture, vanilla JavaScript, Stimulus, Twig rendering and Bootstrap-first templates.