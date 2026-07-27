# Explicit datatable context

`DatatableContext` carries the small amount of application state a datatable
definition needs but cannot obtain from a row, such as a locale, tenant public
identifier, display variant or business scope.

The contract is explicit. It never copies the current request, session,
security token or user object into browser requests.

## Server-only and browser-safe values

Declare all values used by the definition, then separately allowlist the keys
that may travel through the browser:

```php
use Zhortein\DatatableBundle\Context\DatatableContext;

$definition->setContext(new DatatableContext(
    values: [
        'locale' => 'en',
        'tenant' => $tenant->getPublicId(),
        'user' => $user,
    ],
    browserSafeKeys: [
        'locale',
        'tenant',
    ],
));
```

`locale` and `tenant` may be signed and propagated. `user` remains server-side
and is never serialized. Browser-safe values must be scalar, `null`, a backed
enum or `Stringable`.

The signing key is Symfony's `kernel.secret`. The token provides integrity, not
encryption: its values are readable by the browser and must not contain
passwords, API keys, personal data or other secrets.

## Per-render values

The definition owns the allowlist. A render may override only allowlisted
values:

```twig
{{ zhortein_datatable('articles', {
    instance: 'articles-fr',
    context: {
        locale: app.request.locale,
        tenant: tenant.publicId
    }
}) }}
```

Passing an unknown key is rejected before rendering. This makes a render
override useful for two occurrences of the same datatable without allowing a
template to expose arbitrary server context.

`instance` identifies one occurrence of a datatable. It defaults to the
datatable name. Use a distinct value when the same definition appears several
times on one page:

```twig
{{ zhortein_datatable('articles', {
    instance: 'published-fr',
    context: {locale: 'fr', tenant: tenant.publicId}
}) }}

{{ zhortein_datatable('articles', {
    instance: 'draft-en',
    context: {locale: 'en', tenant: tenant.publicId}
}) }}
```

The instance participates in the HTML identifier and signed token. A token
issued for one datatable name or instance is rejected by another.

## Cross-request propagation

When at least one browser-safe value is present, the renderer adds a signed
token and the instance key to:

- fragment URLs;
- CSV and XLSX export URLs, including custom URLs;
- opt-in Ajax action URLs;
- fragment refreshes performed after an Ajax action.

The fragment and export controllers verify the signature, datatable name,
instance and current definition allowlist before restoring values. Invalid,
forbidden or tampered context produces an HTTP `400` response before the
provider is called.

The restored context is available to typed action parameters:

```php
use Zhortein\DatatableBundle\Definition\RouteParameter;

$definition->addRowAction(
    name: 'preview',
    route: 'app_article_preview',
    routeParameters: [
        '_locale' => RouteParameter::context('locale'),
        'tenant' => RouteParameter::context('tenant'),
        'id' => RouteParameter::row('e.id'),
    ],
);
```

This keeps localized route generation coherent in the initial page and every
Ajax fragment.

## Trust boundary

A valid signature proves that the bundle issued the values and that they were
not modified. It does not prove that a user is still authorized for a tenant
or business scope, because a previously issued URL may be replayed.

The host application must therefore:

1. place only validated, non-secret identifiers in browser-safe context;
2. protect fragment, export and action routes with normal Symfony security;
3. revalidate authorization for tenant or business scopes in the provider,
   voter or target action;
4. apply permanent provider filters for data isolation instead of relying on
   UI visibility.

Server-only context remains appropriate for objects and values that can be
reconstructed safely on every request.

## Compatibility

Existing definitions need no changes. A `DatatableContext` without
`browserSafeKeys` remains entirely server-side and produces no token. Existing
fragment, export and action URLs therefore remain unchanged until propagation
is explicitly enabled.

## Related documentation

- [Actions and security](actions.md)
- [Exports](exports.md)
- [Routes](routes.md)
- [Public API](public-api.md)
