<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Theme;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Contract\ThemeInterface;
use Zhortein\DatatableBundle\Enum\CellType;
use Zhortein\DatatableBundle\Exception\DuplicateThemeException;
use Zhortein\DatatableBundle\Exception\ThemeNotFoundException;
use Zhortein\DatatableBundle\Theme\ThemeMetadata;
use Zhortein\DatatableBundle\Theme\ThemeRegistry;

final class ThemeRegistryTest extends TestCase
{
    public function test_it_resolves_registered_themes(): void
    {
        $theme = $this->createTheme('example');
        $registry = new ThemeRegistry([$theme]);

        self::assertTrue($registry->has('example'));
        self::assertSame($theme, $registry->get('example'));
        self::assertSame(['example'], $registry->getNames());
    }

    public function test_it_rejects_duplicate_theme_names(): void
    {
        $this->expectException(DuplicateThemeException::class);

        new ThemeRegistry([
            $this->createTheme('example'),
            $this->createTheme('example'),
        ]);
    }

    public function test_it_reports_available_themes_when_a_theme_is_missing(): void
    {
        $registry = new ThemeRegistry([$this->createTheme('bootstrap')]);

        $this->expectException(ThemeNotFoundException::class);
        $this->expectExceptionMessage('Available themes: bootstrap.');

        $registry->get('missing');
    }

    private function createTheme(string $name): ThemeInterface
    {
        return new readonly class($name) implements ThemeInterface {
            private ThemeMetadata $metadata;

            public function __construct(string $name)
            {
                $this->metadata = new ThemeMetadata($name, '@Theme', []);
            }

            public function getMetadata(): ThemeMetadata
            {
                return $this->metadata;
            }

            public function getDefaultCellClassName(CellType $cellType): ?string
            {
                return null;
            }
        };
    }
}
