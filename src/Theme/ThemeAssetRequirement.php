<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Theme;

use Zhortein\DatatableBundle\Enum\ThemeAssetOwner;
use Zhortein\DatatableBundle\Enum\ThemeAssetType;

final readonly class ThemeAssetRequirement
{
    private string $package;

    public function __construct(
        string $package,
        private ThemeAssetType $type,
        private ThemeAssetOwner $owner,
    ) {
        $package = trim($package);

        if ('' === $package) {
            throw new \InvalidArgumentException('A theme asset package must not be empty.');
        }

        $this->package = $package;
    }

    public function getPackage(): string
    {
        return $this->package;
    }

    public function getType(): ThemeAssetType
    {
        return $this->type;
    }

    public function getOwner(): ThemeAssetOwner
    {
        return $this->owner;
    }
}
