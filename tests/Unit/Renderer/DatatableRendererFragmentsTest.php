<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final class DatatableRendererFragmentsTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_renders_empty_body_fragment(): void
    {
        $definition = new DatatableDefinition('users');
        $definition->addColumn('e.email', label: 'Email');

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->renderEmptyBody($definition);

        self::assertStringContainsString('No data available.', $html);
        self::assertStringContainsString('colspan="1"', $html);
    }

    public function test_it_renders_pagination_placeholder_fragment(): void
    {
        $definition = new DatatableDefinition('users');
        $definition->addColumn('e.email', label: 'Email');

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->renderPaginationPlaceholder($definition);

        self::assertStringContainsString('zhortein-datatable__pagination', $html);
        self::assertStringContainsString('data-zhortein-datatable-target="pagination"', $html);
    }

    private function createTwigEnvironment(): Environment
    {
        $loader = new FilesystemLoader();
        $loader->addPath(__DIR__.'/../../../templates', 'ZhorteinDatatable');

        $twig = new Environment($loader, [
            'strict_variables' => true,
            'autoescape' => 'html',
        ]);

        $this->addTranslationExtension($twig);

        return $twig;
    }
}
