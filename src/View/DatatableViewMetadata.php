<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\View;

final readonly class DatatableViewMetadata
{
    public function __construct(
        private string $identifier,
        private string $name,
        private string $revision,
        private bool $default = false,
    ) {
        $this->assertIdentifier($this->identifier, 'identifier');
        $this->assertName($this->name);
        $this->assertIdentifier($this->revision, 'revision');
    }

    public static function create(
        string $identifier,
        string $name,
        string $revision,
        bool $default = false,
    ): self {
        return new self(
            identifier: trim($identifier),
            name: trim($name),
            revision: trim($revision),
            default: $default,
        );
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRevision(): string
    {
        return $this->revision;
    }

    public function isDefault(): bool
    {
        return $this->default;
    }

    public function with(
        ?string $name = null,
        ?string $revision = null,
        ?bool $default = null,
    ): self {
        return self::create(
            identifier: $this->identifier,
            name: $name ?? $this->name,
            revision: $revision ?? $this->revision,
            default: $default ?? $this->default,
        );
    }

    /**
     * @return array{id: string, name: string, revision: string, default: bool}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->identifier,
            'name' => $this->name,
            'revision' => $this->revision,
            'default' => $this->default,
        ];
    }

    private function assertIdentifier(string $value, string $label): void
    {
        if ('' === $value || 255 < strlen($value) || 1 === preg_match('/[\x00-\x1F\x7F]/', $value)) {
            throw new \InvalidArgumentException(sprintf(
                'The datatable view %s must be a non-empty string of at most 255 characters without control characters.',
                $label,
            ));
        }
    }

    private function assertName(string $name): void
    {
        if ('' === $name || 120 < strlen($name) || 1 === preg_match('/[\x00-\x1F\x7F]/', $name)) {
            throw new \InvalidArgumentException('A datatable view name must be a non-empty string of at most 120 characters without control characters.');
        }
    }
}
