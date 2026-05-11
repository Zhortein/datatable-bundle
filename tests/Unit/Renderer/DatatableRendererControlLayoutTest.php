<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final class DatatableRendererControlLayoutTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_default_layout_keeps_page_size_selector_in_toolbar(): void
    {
        $html = $this->createRenderer()->render($this->createDefinition());

        self::assertStringContainsString('zhortein-datatable__toolbar', $html);
        self::assertStringNotContainsString('zhortein-datatable__bottom-controls', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-target="pageSizeInput"', $html);
    }

    public function test_split_layout_moves_page_size_and_column_visibility_to_bottom_controls(): void
    {
        $html = $this->createRenderer()->render($this->createDefinition(), [
            'controlsLayout' => 'split',
        ]);

        self::assertStringContainsString('zhortein-datatable__bottom-controls', $html);
        self::assertStringContainsString('zhortein-datatable__column-visibility', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-target="pageSizeInput"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-target="summary"', $html);
    }

    public function test_split_layout_keeps_export_and_global_actions_in_top_toolbar(): void
    {
        $definition = $this->createDefinition();
        $definition->addGlobalAction(
            name: 'create',
            route: 'app_user_create',
            label: 'Create user',
        );

        $html = $this->createRenderer(new ControlLayoutTestUrlGenerator())->render($definition, [
            'controlsLayout' => 'split',
        ]);

        self::assertStringContainsString('zhortein-datatable__toolbar', $html);
        self::assertStringContainsString('CSV current view', $html);
        self::assertStringContainsString('Create user', $html);
    }

    public function test_split_layout_can_disable_bottom_controls(): void
    {
        $html = $this->createRenderer()->render($this->createDefinition(), [
            'controlsLayout' => 'split',
            'columnVisibility' => false,
            'pageSizeSelector' => false,
        ]);

        self::assertStringContainsString('zhortein-datatable__bottom-controls', $html);
        self::assertStringNotContainsString('zhortein-datatable__column-visibility', $html);
        self::assertStringNotContainsString('data-zhortein--datatable-bundle--datatable-target="pageSizeInput"', $html);
    }

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.displayName', label: 'Display name')
        ;

        return $definition;
    }

    private function createRenderer(?UrlGeneratorInterface $urlGenerator = null): DatatableRenderer
    {
        return new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            urlGenerator: $urlGenerator,
        );
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

final class ControlLayoutTestUrlGenerator implements UrlGeneratorInterface
{
    /**
     * @param array<mixed> $parameters
     */
    public function generate(
        string $name,
        array $parameters = [],
        int $referenceType = self::ABSOLUTE_PATH,
    ): string {
        return match ($name) {
            'app_user_create' => '/users/create',
            default => '/'.$name,
        };
    }

    public function setContext(RequestContext $context): void
    {
    }

    public function getContext(): RequestContext
    {
        return new RequestContext();
    }
}
