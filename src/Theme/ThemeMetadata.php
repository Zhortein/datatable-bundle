<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Theme;

use Zhortein\DatatableBundle\Enum\ThemeCapability;

final readonly class ThemeMetadata
{
    private string $templatePrefix;

    /**
     * @var array<string, ThemeCapability>
     */
    private array $capabilities;

    /**
     * @var list<ThemeAssetRequirement>
     */
    private array $assetRequirements;

    /**
     * @param iterable<ThemeCapability>       $capabilities
     * @param iterable<ThemeAssetRequirement> $assetRequirements
     */
    public function __construct(
        private string $name,
        string $templatePrefix,
        iterable $capabilities,
        iterable $assetRequirements = [],
    ) {
        if (1 !== preg_match('/^[a-z][a-z0-9_-]*$/', $this->name)) {
            throw new \InvalidArgumentException(sprintf('The theme name "%s" is invalid.', $this->name));
        }

        $templatePrefix = rtrim(trim($templatePrefix), '/');

        if ('' === $templatePrefix) {
            throw new \InvalidArgumentException('The theme template prefix must not be empty.');
        }

        $resolvedCapabilities = [];

        foreach ($capabilities as $capability) {
            $resolvedCapabilities[$capability->value] = $capability;
        }

        $resolvedAssetRequirements = [];

        foreach ($assetRequirements as $assetRequirement) {
            $resolvedAssetRequirements[] = $assetRequirement;
        }

        $this->templatePrefix = $templatePrefix;
        $this->capabilities = $resolvedCapabilities;
        $this->assetRequirements = $resolvedAssetRequirements;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTemplatePrefix(): string
    {
        return $this->templatePrefix;
    }

    /**
     * @return list<ThemeCapability>
     */
    public function getCapabilities(): array
    {
        return array_values($this->capabilities);
    }

    public function supports(ThemeCapability $capability): bool
    {
        return isset($this->capabilities[$capability->value]);
    }

    /**
     * @return list<ThemeAssetRequirement>
     */
    public function getAssetRequirements(): array
    {
        return $this->assetRequirements;
    }

    public function template(string $relativePath): string
    {
        $relativePath = ltrim(trim($relativePath), '/');

        if ('' === $relativePath || str_contains($relativePath, '..')) {
            throw new \InvalidArgumentException(sprintf('The theme template path "%s" is invalid.', $relativePath));
        }

        return $this->templatePrefix.'/'.$relativePath;
    }
}
