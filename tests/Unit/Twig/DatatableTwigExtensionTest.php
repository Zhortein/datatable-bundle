<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Twig;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Twig\Attribute\AsTwigFunction;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\Registry\DatatableRegistry;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Tests\Unit\Renderer\TranslatableRendererTestTrait;
use Zhortein\DatatableBundle\Twig\DatatableTwigExtension;

final class DatatableTwigExtensionTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_render_method_is_exposed_as_twig_function(): void
    {
        $reflectionMethod = new \ReflectionMethod(DatatableTwigExtension::class, 'renderDatatable');
        $attributes = $reflectionMethod->getAttributes(AsTwigFunction::class);

        self::assertCount(1, $attributes);

        $attribute = $attributes[0]->newInstance();

        self::assertSame('zhortein_datatable', $attribute->name);
        self::assertSame(['html'], $attribute->isSafe);
    }

    public function test_it_renders_datatable_by_name(): void
    {
        $extension = $this->createExtension();

        $html = $extension->renderDatatable('users');

        self::assertStringContainsString('id="zhortein-datatable-users"', $html);
        self::assertStringContainsString('data-controller="zhortein-datatable"', $html);
        self::assertStringContainsString('Email', $html);
        self::assertStringContainsString('No data available.', $html);
    }

    public function test_it_renders_datatable_with_runtime_options(): void
    {
        $extension = $this->createExtension();

        $html = $extension->renderDatatable('users', [
            'search' => true,
        ]);

        self::assertStringContainsString('id="zhortein-datatable-users"', $html);
        self::assertStringContainsString('type="search"', $html);
        self::assertStringContainsString('data-zhortein-datatable-target="searchInput"', $html);
        self::assertStringContainsString('Email', $html);
    }

    private function createExtension(?Environment $twig = null): DatatableTwigExtension
    {
        $twig ??= $this->createTwigEnvironment();

        $datatable = new TwigExtensionTestDatatable();

        $registry = new DatatableRegistry(
            new ServiceLocator([
                'users' => static fn (): TwigExtensionTestDatatable => $datatable,
            ]),
            ['users' => TwigExtensionTestDatatable::class],
        );

        return new DatatableTwigExtension(
            definitionFactory: new DatatableDefinitionFactory($registry),
            renderer: new DatatableRenderer($twig),
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

final class TwigExtensionTestDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setEntityClass(\stdClass::class)
            ->addColumn('e.id', visible: false)
            ->addColumn('e.email', label: 'Email')
        ;
    }
}
