<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\View;

/**
 * Identifies one collision-free saved-view namespace.
 *
 * The optional namespace and locale are application-provided opaque values.
 * The context fingerprint is derived from the signed browser-safe context
 * token and never exposes its values.
 */
final readonly class DatatableViewScope
{
    public const string SCOPE_QUERY_PARAMETER = '_zd_view_scope';
    public const string LOCALE_QUERY_PARAMETER = '_zd_view_locale';

    public function __construct(
        private string $datatableName,
        private string $instance,
        private string $namespace = 'default',
        private string $locale = 'und',
        private ?string $contextFingerprint = null,
    ) {
        $this->assertValidPart($this->datatableName, 'datatable name');
        $this->assertValidPart($this->instance, 'instance');
        $this->assertValidPart($this->namespace, 'namespace');
        $this->assertValidPart($this->locale, 'locale');

        if (null !== $this->contextFingerprint) {
            $this->assertValidPart($this->contextFingerprint, 'context fingerprint', 128);
        }
    }

    public static function create(
        string $datatableName,
        string $instance,
        string $namespace = 'default',
        string $locale = 'und',
        ?string $contextFingerprint = null,
    ): self {
        return new self(
            datatableName: trim($datatableName),
            instance: trim($instance),
            namespace: trim($namespace),
            locale: trim($locale),
            contextFingerprint: null === $contextFingerprint ? null : trim($contextFingerprint),
        );
    }

    public function getDatatableName(): string
    {
        return $this->datatableName;
    }

    public function getInstance(): string
    {
        return $this->instance;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getContextFingerprint(): ?string
    {
        return $this->contextFingerprint;
    }

    /**
     * Returns a deterministic, opaque key suitable for storage partitioning.
     */
    public function getStorageKey(): string
    {
        return hash('sha256', json_encode([
            'datatable' => $this->datatableName,
            'instance' => $this->instance,
            'namespace' => $this->namespace,
            'locale' => $this->locale,
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
            throw new \InvalidArgumentException(sprintf(
                'The datatable view %s must be a non-empty string of at most %d characters without control characters.',
                $label,
                $maximumLength,
            ));
        }
    }
}
