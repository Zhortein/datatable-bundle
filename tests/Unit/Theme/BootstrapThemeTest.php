<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Theme;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Enum\CellType;
use Zhortein\DatatableBundle\Enum\ThemeAssetOwner;
use Zhortein\DatatableBundle\Enum\ThemeCapability;
use Zhortein\DatatableBundle\Theme\BootstrapTheme;
use Zhortein\DatatableBundle\Theme\ThemeAssetRequirement;

final class BootstrapThemeTest extends TestCase
{
    public function test_it_declares_the_complete_builtin_theme_contract(): void
    {
        $theme = new BootstrapTheme();
        $metadata = $theme->getMetadata();

        self::assertSame('bootstrap', $metadata->getName());
        self::assertSame('@ZhorteinDatatable/bootstrap', $metadata->getTemplatePrefix());
        self::assertSame(ThemeCapability::cases(), $metadata->getCapabilities());
        self::assertCount(2, $metadata->getAssetRequirements());
        self::assertSame(
            [ThemeAssetOwner::HostApplication, ThemeAssetOwner::HostApplication],
            array_map(
                static fn (ThemeAssetRequirement $requirement): ThemeAssetOwner => $requirement->getOwner(),
                $metadata->getAssetRequirements(),
            ),
        );
        self::assertSame('text-end align-middle', $theme->getDefaultCellClassName(CellType::Numeric));
        self::assertSame('text-center align-middle', $theme->getDefaultCellClassName(CellType::Boolean));
        self::assertNull($theme->getDefaultCellClassName(CellType::String));
    }
}
