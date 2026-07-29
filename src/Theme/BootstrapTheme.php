<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Theme;

use Zhortein\DatatableBundle\Contract\ThemeInterface;
use Zhortein\DatatableBundle\Enum\CellType;
use Zhortein\DatatableBundle\Enum\ThemeAssetOwner;
use Zhortein\DatatableBundle\Enum\ThemeAssetType;
use Zhortein\DatatableBundle\Enum\ThemeCapability;

final readonly class BootstrapTheme implements ThemeInterface
{
    private ThemeMetadata $metadata;

    public function __construct()
    {
        $this->metadata = new ThemeMetadata(
            name: 'bootstrap',
            templatePrefix: '@ZhorteinDatatable/bootstrap',
            capabilities: ThemeCapability::cases(),
            assetRequirements: [
                new ThemeAssetRequirement('bootstrap:^5.3', ThemeAssetType::Stylesheet, ThemeAssetOwner::HostApplication),
                new ThemeAssetRequirement('bootstrap:^5.3', ThemeAssetType::JavaScript, ThemeAssetOwner::HostApplication),
            ],
        );
    }

    public function getMetadata(): ThemeMetadata
    {
        return $this->metadata;
    }

    public function getDefaultCellClassName(CellType $cellType): ?string
    {
        return match ($cellType) {
            CellType::Numeric => 'text-end align-middle',
            CellType::Boolean, CellType::Enum => 'text-center align-middle',
            default => null,
        };
    }
}
