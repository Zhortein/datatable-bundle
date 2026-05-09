<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Kernel;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Zhortein\DatatableBundle\Provider\DataProviderRegistry;
use Zhortein\DatatableBundle\ZhorteinDatatableBundle;

final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new ZhorteinDatatableBundle();
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'test' => true,
            'secret' => 'zhortein-datatable-test-secret',
            'http_method_override' => false,
            'router' => [
                'utf8' => true,
            ],
        ]);

        $container->extension('twig', [
            'strict_variables' => true,
            'paths' => [
                __DIR__.'/../../../templates' => 'ZhorteinDatatable',
            ],
        ]);

        $services = $container->services()
            ->defaults()
            ->autowire()
            ->autoconfigure()
        ;

        $services->load(
            'Zhortein\\DatatableBundle\\Tests\\Functional\\Fixtures\\',
            __DIR__.'/../Fixtures',
        );

        $services
            ->alias('test.'.DataProviderRegistry::class, DataProviderRegistry::class)
            ->public()
        ;
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(__DIR__.'/../../../config/routes.php');
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/zhortein-datatable-bundle/cache/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/zhortein-datatable-bundle/logs';
    }
}
