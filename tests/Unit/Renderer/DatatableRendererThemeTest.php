<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Contract\ThemeInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\CellType;
use Zhortein\DatatableBundle\Exception\ThemeNotFoundException;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Theme\ThemeMetadata;
use Zhortein\DatatableBundle\Theme\ThemeRegistry;

final class DatatableRendererThemeTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_resolves_templates_and_presentation_through_the_selected_theme(): void
    {
        $loader = new FilesystemLoader();
        $loader->addPath(__DIR__.'/../Theme/templates', 'TestTheme');
        $twig = new Environment($loader, [
            'strict_variables' => true,
            'autoescape' => 'html',
        ]);
        $this->addTranslationExtension($twig);

        $theme = new readonly class implements ThemeInterface {
            public function getMetadata(): ThemeMetadata
            {
                return new ThemeMetadata('test', '@TestTheme', []);
            }

            public function getDefaultCellClassName(CellType $cellType): ?string
            {
                return CellType::Numeric === $cellType ? 'test-numeric' : null;
            }
        };
        $definition = new DatatableDefinition('values');
        $definition->addColumn('value', type: CellType::Numeric->value);
        $renderer = new DatatableRenderer(
            $twig,
            theme: 'test',
            themeRegistry: new ThemeRegistry([$theme]),
        );

        self::assertSame('test|test-numeric', trim($renderer->render($definition)));
    }

    public function test_it_fails_fast_when_the_selected_theme_is_not_registered(): void
    {
        $this->expectException(ThemeNotFoundException::class);

        new DatatableRenderer(
            new Environment(new FilesystemLoader()),
            theme: 'missing',
            themeRegistry: new ThemeRegistry([]),
        );
    }
}
