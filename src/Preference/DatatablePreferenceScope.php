<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Preference;

final readonly class DatatablePreferenceScope
{
    public const string SCOPE_QUERY_PARAMETER = '_zd_preference_scope';
    public const string ROUTE_QUERY_PARAMETER = '_zd_preference_route';
    public const string LOCALE_QUERY_PARAMETER = '_zd_preference_locale';

    public function __construct(
        private string $ownerIdentifier,
        private string $datatableName,
        private string $instance,
        private string $routeScope,
        private string $namespace,
        private string $locale,
        private string $schemaVersion,
        private ?string $contextFingerprint = null,
    ) {
        $this->assertValidPart($this->ownerIdentifier, 'owner identifier', 512);
        $this->assertValidPart($this->datatableName, 'datatable name');
        $this->assertValidPart($this->instance, 'instance');
        $this->assertValidPart($this->routeScope, 'route scope', 512);
        $this->assertValidPart($this->namespace, 'namespace');
        $this->assertValidPart($this->locale, 'locale');
        $this->assertValidPart($this->schemaVersion, 'schema version', 128);

        if (null !== $this->contextFingerprint) {
            $this->assertValidPart($this->contextFingerprint, 'context fingerprint', 128);
        }
    }

    public static function create(
        string $ownerIdentifier,
        string $datatableName,
        string $instance,
        string $routeScope,
        string $namespace = 'default',
        string $locale = 'und',
        string $schemaVersion = '1',
        ?string $contextFingerprint = null,
    ): self {
        return new self(
            ownerIdentifier: trim($ownerIdentifier),
            datatableName: trim($datatableName),
            instance: trim($instance),
            routeScope: trim($routeScope),
            namespace: trim($namespace),
            locale: trim($locale),
            schemaVersion: trim($schemaVersion),
            contextFingerprint: null === $contextFingerprint ? null : trim($contextFingerprint),
        );
    }

    public function getOwnerIdentifier(): string
    {
        return $this->ownerIdentifier;
    }

    public function getDatatableName(): string
    {
        return $this->datatableName;
    }

    public function getInstance(): string
    {
        return $this->instance;
    }

    public function getRouteScope(): string
    {
        return $this->routeScope;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getSchemaVersion(): string
    {
        return $this->schemaVersion;
    }

    public function getContextFingerprint(): ?string
    {
        return $this->contextFingerprint;
    }

    public function getStorageKey(): string
    {
        return hash('sha256', json_encode([
            'owner' => $this->ownerIdentifier,
            'datatable' => $this->datatableName,
            'instance' => $this->instance,
            'route' => $this->routeScope,
            'namespace' => $this->namespace,
            'locale' => $this->locale,
            'schema' => $this->schemaVersion,
            'context' => $this->contextFingerprint,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function assertValidPart(string $value, string $label, int $maximumLength = 255): void
    {
        if (
            '' === $value
            || $maximumLength < strlen($value)
            || 1 === preg_match('/[\x00-\x1F\x7F]/', $value)
        ) {
            throw new \InvalidArgumentException(sprintf('The datatable preference %s is invalid.', $label));
        }
    }
}
