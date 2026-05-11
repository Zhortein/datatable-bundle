<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Zhortein\DatatableBundle\ZhorteinDatatableBundle;

final class BundleBootstrapConfigurationTest extends TestCase
{
    public function test_it_registers_default_bootstrap_rendering_parameters(): void
    {
        $container = $this->loadBundleConfiguration([]);

        self::assertTrue($container->getParameter('zhortein_datatable.bootstrap.table_striped'));
        self::assertTrue($container->getParameter('zhortein_datatable.bootstrap.table_hover'));
        self::assertFalse($container->getParameter('zhortein_datatable.bootstrap.table_bordered'));
        self::assertFalse($container->getParameter('zhortein_datatable.bootstrap.table_borderless'));
        self::assertFalse($container->getParameter('zhortein_datatable.bootstrap.table_small'));
        self::assertTrue($container->getParameter('zhortein_datatable.bootstrap.table_responsive'));
    }

    public function test_it_accepts_custom_bootstrap_rendering_parameters(): void
    {
        $container = $this->loadBundleConfiguration([
            'bootstrap' => [
                'table' => [
                    'striped' => false,
                    'hover' => false,
                    'bordered' => true,
                    'borderless' => true,
                    'small' => true,
                    'responsive' => false,
                ],
            ],
        ]);

        self::assertFalse($container->getParameter('zhortein_datatable.bootstrap.table_striped'));
        self::assertFalse($container->getParameter('zhortein_datatable.bootstrap.table_hover'));
        self::assertTrue($container->getParameter('zhortein_datatable.bootstrap.table_bordered'));
        self::assertTrue($container->getParameter('zhortein_datatable.bootstrap.table_borderless'));
        self::assertTrue($container->getParameter('zhortein_datatable.bootstrap.table_small'));
        self::assertFalse($container->getParameter('zhortein_datatable.bootstrap.table_responsive'));
    }

    public function test_it_rejects_unknown_bootstrap_table_option(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->loadBundleConfiguration([
            'bootstrap' => [
                'table' => [
                    'unknown' => true,
                ],
            ],
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
