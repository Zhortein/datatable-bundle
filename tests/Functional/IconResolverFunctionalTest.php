<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Zhortein\DatatableBundle\Contract\IconRendererInterface;
use Zhortein\DatatableBundle\Contract\IconResolverInterface;
use Zhortein\DatatableBundle\Icon\ConfiguredIconRenderer;
use Zhortein\DatatableBundle\Icon\CssClassIconRenderer;
use Zhortein\DatatableBundle\Icon\IconResolver;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class IconResolverFunctionalTest extends FunctionalTestCase
{
    public function test_icon_resolver_is_registered(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        self::assertTrue($container->has('test.'.IconResolver::class));
        self::assertTrue($container->has('test.'.IconResolverInterface::class));

        $resolver = $container->get('test.'.IconResolver::class);
        self::assertInstanceOf(IconResolver::class, $resolver);
    }

    public function test_icon_resolver_uses_default_icons(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        /** @var IconResolverInterface $resolver */
        $resolver = $container->get('test.'.IconResolverInterface::class);

        self::assertSame('bi bi-eye', $resolver->resolve('view'));
    }

    public function test_dependency_free_icon_renderer_is_registered_by_default(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        self::assertTrue($container->has('test.'.CssClassIconRenderer::class));
        self::assertTrue($container->has('test.'.ConfiguredIconRenderer::class));
        self::assertTrue($container->has('test.'.IconRendererInterface::class));

        /** @var IconRendererInterface $renderer */
        $renderer = $container->get('test.'.IconRendererInterface::class);

        self::assertSame(
            '<span class="bi bi-eye" aria-hidden="true"></span>',
            $renderer->render('bi bi-eye'),
        );
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }
}
