<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final class DatatableRendererConfirmationModalTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_renders_confirmation_modal_by_default(): void
    {
        $html = $this->createRenderer()->render($this->createDefinition(), [
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringContainsString('zhortein-datatable__confirmation-modal', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-target="confirmationModal"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-target="confirmationMessage"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-target="confirmationConfirmButton"', $html);
        self::assertStringContainsString('zhortein--datatable-bundle--datatable#confirmPendingAction', $html);
    }

    public function test_it_can_disable_confirmation_modal_rendering(): void
    {
        $html = $this->createRenderer()->render($this->createDefinition(), [
            'confirmationModal' => false,
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringNotContainsString('zhortein-datatable__confirmation-modal', $html);
        self::assertStringNotContainsString('data-zhortein--datatable-bundle--datatable-target="confirmationModal"', $html);
    }

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('users');
        $definition->addColumn('e.email', label: 'Email');

        return $definition;
    }

    private function createRenderer(): DatatableRenderer
    {
        return new DatatableRenderer($this->createTwigEnvironment());
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
