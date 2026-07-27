<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Zhortein\DatatableBundle\Contract\DatatableExportAuthorizationCheckerInterface;
use Zhortein\DatatableBundle\Contract\ExportCancellationInterface;
use Zhortein\DatatableBundle\Export\AllowAllDatatableExportAuthorizationChecker;
use Zhortein\DatatableBundle\Export\ConnectionAbortedExportCancellation;
use Zhortein\DatatableBundle\Export\ExportLimitResolver;
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
        self::assertFalse($container->getParameter('zhortein_datatable.search_builder_enabled'));
        self::assertSame([], $container->getParameter('zhortein_datatable.icons'));
        self::assertSame(10000, $container->getParameter('zhortein_datatable.export.max_rows'));
        self::assertSame(500, $container->getParameter('zhortein_datatable.export.batch_size'));
        self::assertSame(
            ['csv' => null, 'xlsx' => null],
            $container->getParameter('zhortein_datatable.export.format_limits'),
        );
        self::assertSame(',', $container->getParameter('zhortein_datatable.export.csv.delimiter'));
        self::assertSame('"', $container->getParameter('zhortein_datatable.export.csv.enclosure'));
        self::assertSame('\\', $container->getParameter('zhortein_datatable.export.csv.escape'));
        self::assertFalse($container->getParameter('zhortein_datatable.export.csv.bom'));
    }

    public function test_it_accepts_custom_icons(): void
    {
        $container = $this->loadBundleConfiguration([
            'icons' => [
                'view' => 'fa fa-eye',
                'custom' => 'fa fa-star',
            ],
        ]);

        self::assertSame([
            'view' => 'fa fa-eye',
            'custom' => 'fa fa-star',
        ], $container->getParameter('zhortein_datatable.icons'));
    }

    public function test_it_registers_export_guard_services_and_backward_compatible_authorization(): void
    {
        $container = $this->loadBundleConfiguration([]);

        self::assertTrue($container->hasDefinition(ExportLimitResolver::class));
        self::assertTrue($container->hasDefinition(ConnectionAbortedExportCancellation::class));
        self::assertTrue($container->hasAlias(ExportCancellationInterface::class));
        self::assertSame(
            ConnectionAbortedExportCancellation::class,
            (string) $container->getAlias(ExportCancellationInterface::class),
        );
        self::assertTrue($container->hasAlias(DatatableExportAuthorizationCheckerInterface::class));
        self::assertSame(
            AllowAllDatatableExportAuthorizationChecker::class,
            (string) $container->getAlias(DatatableExportAuthorizationCheckerInterface::class),
        );
    }

    public function test_it_accepts_custom_configuration_values(): void
    {
        $container = $this->loadBundleConfiguration([
            'default_provider' => 'array',
            'default_theme' => 'bootstrap',
            'default_page_size' => 50,
            'max_page_size' => 250,
            'search_enabled' => true,
            'search_builder_enabled' => true,
            'icons' => [
                'action_view' => 'smoke-icon-view',
            ],
            'export' => [
                'max_rows' => 5000,
                'batch_size' => 250,
                'format_limits' => [
                    'csv' => 2500,
                    'xlsx' => 1000,
                ],
                'csv' => [
                    'delimiter' => ';',
                    'enclosure' => '|',
                    'escape' => '!',
                    'bom' => true,
                ],
            ],
        ]);

        self::assertSame('array', $container->getParameter('zhortein_datatable.default_provider'));
        self::assertSame('bootstrap', $container->getParameter('zhortein_datatable.default_theme'));
        self::assertSame(50, $container->getParameter('zhortein_datatable.default_page_size'));
        self::assertSame(250, $container->getParameter('zhortein_datatable.max_page_size'));
        self::assertTrue($container->getParameter('zhortein_datatable.search_enabled'));
        self::assertTrue($container->getParameter('zhortein_datatable.search_builder_enabled'));
        self::assertSame(
            ['action_view' => 'smoke-icon-view'],
            $container->getParameter('zhortein_datatable.icons'),
        );
        self::assertSame(5000, $container->getParameter('zhortein_datatable.export.max_rows'));
        self::assertSame(250, $container->getParameter('zhortein_datatable.export.batch_size'));
        self::assertSame(
            ['csv' => 2500, 'xlsx' => 1000],
            $container->getParameter('zhortein_datatable.export.format_limits'),
        );
        self::assertSame(';', $container->getParameter('zhortein_datatable.export.csv.delimiter'));
        self::assertSame('|', $container->getParameter('zhortein_datatable.export.csv.enclosure'));
        self::assertSame('!', $container->getParameter('zhortein_datatable.export.csv.escape'));
        self::assertTrue($container->getParameter('zhortein_datatable.export.csv.bom'));
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

    public function test_it_rejects_invalid_export_row_limit(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->loadBundleConfiguration([
            'export' => [
                'max_rows' => 0,
            ],
        ]);
    }

    public function test_it_rejects_invalid_format_export_row_limit(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->loadBundleConfiguration([
            'export' => [
                'format_limits' => [
                    'xlsx' => 0,
                ],
            ],
        ]);
    }

    public function test_it_rejects_invalid_export_batch_size(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->loadBundleConfiguration([
            'export' => [
                'batch_size' => 0,
            ],
        ]);
    }

    public function test_it_rejects_export_batch_size_above_the_bound(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->loadBundleConfiguration([
            'export' => [
                'batch_size' => 10001,
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
