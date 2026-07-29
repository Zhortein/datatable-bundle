<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Zhortein\DatatableBundle\Contract\DatatableExportAuthorizationCheckerInterface;
use Zhortein\DatatableBundle\Contract\DatatablePreferenceIdentityResolverInterface;
use Zhortein\DatatableBundle\Contract\ExportCancellationInterface;
use Zhortein\DatatableBundle\Contract\ExportJobClockInterface;
use Zhortein\DatatableBundle\Contract\ExportJobDispatcherInterface;
use Zhortein\DatatableBundle\Contract\ExportJobExpiryPolicyInterface;
use Zhortein\DatatableBundle\Contract\ExportJobOwnerResolverInterface;
use Zhortein\DatatableBundle\Contract\ExportJobRepositoryInterface;
use Zhortein\DatatableBundle\Contract\ExportJobResultStorageInterface;
use Zhortein\DatatableBundle\Contract\ThemeInterface;
use Zhortein\DatatableBundle\Export\AllowAllDatatableExportAuthorizationChecker;
use Zhortein\DatatableBundle\Export\ConnectionAbortedExportCancellation;
use Zhortein\DatatableBundle\Export\ExportLimitResolver;
use Zhortein\DatatableBundle\Export\Job\InMemoryExportJobRepository;
use Zhortein\DatatableBundle\Export\Job\InMemoryExportJobResultStorage;
use Zhortein\DatatableBundle\Export\Job\NullExportJobOwnerResolver;
use Zhortein\DatatableBundle\Export\Job\UnavailableExportJobDispatcher;
use Zhortein\DatatableBundle\Preference\CacheDatatablePreferenceProvider;
use Zhortein\DatatableBundle\Preference\DatatablePreferenceProviderInterface;
use Zhortein\DatatableBundle\Preference\NullDatatablePreferenceIdentityResolver;
use Zhortein\DatatableBundle\Preference\NullDatatablePreferenceProvider;
use Zhortein\DatatableBundle\Theme\BootstrapTheme;
use Zhortein\DatatableBundle\Theme\ThemeRegistry;
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
        self::assertSame('css', $container->getParameter('zhortein_datatable.icon_provider'));
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
        self::assertFalse($container->getParameter('zhortein_datatable.export.async.enabled'));
        self::assertSame(250000, $container->getParameter('zhortein_datatable.export.async.max_rows'));
        self::assertSame(86400, $container->getParameter('zhortein_datatable.export.async.ttl'));
        self::assertSame(3, $container->getParameter('zhortein_datatable.export.async.max_attempts'));
        self::assertSame(
            ['csv' => null, 'xlsx' => null],
            $container->getParameter('zhortein_datatable.export.async.format_limits'),
        );
        self::assertFalse($container->getParameter('zhortein_datatable.preferences.enabled'));
        self::assertSame(31536000, $container->getParameter('zhortein_datatable.preferences.ttl'));
        self::assertSame('1', $container->getParameter('zhortein_datatable.preferences.schema_version'));
        self::assertSame(
            NullDatatablePreferenceProvider::class,
            (string) $container->getAlias(DatatablePreferenceProviderInterface::class),
        );
        self::assertSame(
            NullDatatablePreferenceIdentityResolver::class,
            (string) $container->getAlias(DatatablePreferenceIdentityResolverInterface::class),
        );
    }

    public function test_it_registers_the_bootstrap_theme_and_theme_registry(): void
    {
        $container = $this->loadBundleConfiguration([]);

        self::assertTrue($container->hasDefinition(BootstrapTheme::class));
        self::assertTrue($container->getDefinition(BootstrapTheme::class)->hasTag(ThemeRegistry::SERVICE_TAG));
        self::assertTrue($container->hasDefinition(ThemeRegistry::class));
    }

    public function test_it_autoconfigures_theme_services(): void
    {
        $container = new ContainerBuilder();
        new ZhorteinDatatableBundle()->build($container);

        $autoconfiguration = $container->getAutoconfiguredInstanceof();

        self::assertArrayHasKey(ThemeInterface::class, $autoconfiguration);
        self::assertTrue($autoconfiguration[ThemeInterface::class]->hasTag(ThemeRegistry::SERVICE_TAG));
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
        self::assertSame(
            InMemoryExportJobRepository::class,
            (string) $container->getAlias(ExportJobRepositoryInterface::class),
        );
        self::assertSame(
            InMemoryExportJobResultStorage::class,
            (string) $container->getAlias(ExportJobResultStorageInterface::class),
        );
        self::assertTrue($container->hasAlias(ExportJobClockInterface::class));
        self::assertTrue($container->hasAlias(ExportJobExpiryPolicyInterface::class));
        self::assertSame(
            NullExportJobOwnerResolver::class,
            (string) $container->getAlias(ExportJobOwnerResolverInterface::class),
        );
        self::assertSame(
            UnavailableExportJobDispatcher::class,
            (string) $container->getAlias(ExportJobDispatcherInterface::class),
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
            'icon_provider' => 'ux_icons',
            'icons' => [
                'action_view' => 'smoke-icon-view',
            ],
            'preferences' => [
                'enabled' => true,
                'cache_pool' => 'cache.custom',
                'ttl' => 7200,
                'schema_version' => '2026-07',
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
                'async' => [
                    'enabled' => true,
                    'max_rows' => 500000,
                    'ttl' => 7200,
                    'max_attempts' => 5,
                    'format_limits' => [
                        'csv' => 400000,
                        'xlsx' => 100000,
                    ],
                ],
            ],
        ]);

        self::assertSame('array', $container->getParameter('zhortein_datatable.default_provider'));
        self::assertSame('bootstrap', $container->getParameter('zhortein_datatable.default_theme'));
        self::assertSame(50, $container->getParameter('zhortein_datatable.default_page_size'));
        self::assertSame(250, $container->getParameter('zhortein_datatable.max_page_size'));
        self::assertTrue($container->getParameter('zhortein_datatable.search_enabled'));
        self::assertTrue($container->getParameter('zhortein_datatable.search_builder_enabled'));
        self::assertSame('ux_icons', $container->getParameter('zhortein_datatable.icon_provider'));
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
        self::assertTrue($container->getParameter('zhortein_datatable.export.async.enabled'));
        self::assertSame(500000, $container->getParameter('zhortein_datatable.export.async.max_rows'));
        self::assertSame(7200, $container->getParameter('zhortein_datatable.export.async.ttl'));
        self::assertSame(5, $container->getParameter('zhortein_datatable.export.async.max_attempts'));
        self::assertSame(
            ['csv' => 400000, 'xlsx' => 100000],
            $container->getParameter('zhortein_datatable.export.async.format_limits'),
        );
        self::assertTrue($container->getParameter('zhortein_datatable.preferences.enabled'));
        self::assertSame(7200, $container->getParameter('zhortein_datatable.preferences.ttl'));
        self::assertSame('2026-07', $container->getParameter('zhortein_datatable.preferences.schema_version'));
        self::assertSame(
            CacheDatatablePreferenceProvider::class,
            (string) $container->getAlias(DatatablePreferenceProviderInterface::class),
        );
        $cachePool = $container
            ->getDefinition(CacheDatatablePreferenceProvider::class)
            ->getArgument('$cachePool');

        self::assertInstanceOf(Reference::class, $cachePool);
        self::assertSame('cache.custom', (string) $cachePool);
    }

    public function test_it_rejects_invalid_default_provider(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->loadBundleConfiguration([
            'default_provider' => 'invalid',
        ]);
    }

    public function test_it_accepts_http_as_default_provider(): void
    {
        $container = $this->loadBundleConfiguration([
            'default_provider' => 'http',
        ]);

        self::assertSame('http', $container->getParameter('zhortein_datatable.default_provider'));
    }

    public function test_it_accepts_a_custom_default_theme_name(): void
    {
        $container = $this->loadBundleConfiguration([
            'default_theme' => 'tailwind',
        ]);

        self::assertSame('tailwind', $container->getParameter('zhortein_datatable.default_theme'));
    }

    public function test_it_rejects_an_empty_default_theme_name(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->loadBundleConfiguration([
            'default_theme' => '',
        ]);
    }

    public function test_it_rejects_a_non_string_default_theme_name(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->loadBundleConfiguration([
            'default_theme' => 42,
        ]);
    }

    public function test_it_rejects_invalid_icon_provider(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->loadBundleConfiguration([
            'icon_provider' => 'font_awesome',
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

    public function test_it_rejects_invalid_preference_ttl(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->loadBundleConfiguration([
            'preferences' => [
                'ttl' => 0,
            ],
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
