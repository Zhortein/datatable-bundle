<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Preference;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Zhortein\DatatableBundle\Contract\DatatablePreferenceIdentityResolverInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;

final readonly class DatatablePreferenceScopeResolver
{
    public function __construct(
        private RequestStack $requestStack,
        private DatatablePreferenceIdentityResolverInterface $identityResolver,
    ) {
    }

    public function resolveCurrent(
        DatatableDefinition $definition,
        string $instance,
        string $namespace,
        string $locale,
        string $schemaVersion,
        ?string $contextFingerprint,
    ): ?DatatablePreferenceScope {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return null;
        }

        $locale = '' === trim($locale)
            ? ($request->getLocale() ?: 'und')
            : $locale;

        return $this->resolve(
            request: $request,
            definition: $definition,
            instance: $instance,
            namespace: $namespace,
            locale: $locale,
            schemaVersion: $schemaVersion,
            contextFingerprint: $contextFingerprint,
        );
    }

    public function resolve(
        Request $request,
        DatatableDefinition $definition,
        string $instance,
        string $namespace,
        string $locale,
        string $schemaVersion,
        ?string $contextFingerprint,
        ?string $routeScope = null,
    ): ?DatatablePreferenceScope {
        $ownerIdentifier = $this->identityResolver
            ->resolvePreferenceOwnerIdentifier($request)
        ;

        if (null === $ownerIdentifier) {
            return null;
        }

        $routeScope ??= $this->resolveRouteScope($request);

        return DatatablePreferenceScope::create(
            ownerIdentifier: $ownerIdentifier,
            datatableName: $definition->getName(),
            instance: $instance,
            routeScope: $routeScope,
            namespace: $namespace,
            locale: $locale,
            schemaVersion: $schemaVersion,
            contextFingerprint: $contextFingerprint,
        );
    }

    private function resolveRouteScope(Request $request): string
    {
        $route = $request->attributes->get('_route');

        return is_string($route) && '' !== trim($route)
            ? trim($route)
            : $request->getPathInfo();
    }
}
