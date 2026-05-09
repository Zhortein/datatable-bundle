<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Zhortein\DatatableBundle\ZhorteinDatatableBundle;

final class BundleConfigurationTest extends TestCase
{
    public function test_it_registers_default_configuration_parameters(): void
    {
        $container = $this->loadBundleConfiguration([]);

        self::assertSame('doctrine', $container->getParameter('zhortein_datatable.default_provider'));
        self::assertSame('bootstrap', $container->getParameter('zhortein_datatable.default_theme'));
        self::assertSame(25, $container->getParameter('zhortein_datatable.default_page_size'));
        self::assertSame(500, $container->getParameter('zhortein_datatable.max_page_size'));
        self::assertFalse($container->getParameter('zhortein_datatable.search_enabled'));
    }

    public function test_it_accepts_custom_configuration_values(): void
    {
        $container = $this->loadBundleConfiguration([
            'default_provider' => 'array',
            'default_theme' => 'bootstrap',
            'default_page_size' => 50,
            'max_page_size' => 250,
            'search_enabled' => true,
        ]);

        self::assertSame('array', $container->getParameter('zhortein_datatable.default_provider'));
        self::assertSame('bootstrap', $container->getParameter('zhortein_datatable.default_theme'));
        self::assertSame(50, $container->getParameter('zhortein_datatable.default_page_size'));
        self::assertSame(250, $container->getParameter('zhortein_datatable.max_page_size'));
        self::assertTrue($container->getParameter('zhortein_datatable.search_enabled'));
    }

    public function test_it_rejects_invalid_default_provider(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->loadBundleConfiguration([
            'default_provider' => 'invalid',
        ]);
    }

    public function test_it_rejects_invalid_default_theme(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->loadBundleConfiguration([
            'default_theme' => 'tailwind',
        ]);
    }

    public function test_it_rejects_invalid_default_page_size(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->loadBundleConfiguration([
            'default_page_size' => 0,
        ]);
    }

    public function test_it_rejects_invalid_max_page_size(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->loadBundleConfiguration([
            'max_page_size' => 0,
        ]);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function loadBundleConfiguration(array $config): ContainerBuilder
    {
        $bundle = new ZhorteinDatatableBundle();
        $extension = $bundle->getContainerExtension();

        self::assertNotNull($extension);

        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.debug', true);
        $container->setParameter('kernel.project_dir', dirname(__DIR__, 3));

        $extension->load([$config], $container);

        return $container;
    }
}
