<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Kernel;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Security\FunctionalCsrfTokenManager;

final class LocalizedRoutesTestKernel extends TestKernel
{
    protected function configureContainer(ContainerConfigurator $container): void
    {
        parent::configureContainer($container);

        $container->services()
            ->alias(CsrfTokenManagerInterface::class, FunctionalCsrfTokenManager::class)
        ;
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes
            ->import(__DIR__.'/../../../config/routes.php')
            ->prefix([
                'en' => '/en',
                'fr' => '/fr',
            ])
        ;
    }

    public function getCacheDir(): string
    {
        return sprintf(
            '%s/zhortein-datatable-bundle/cache/localized_%s_%s',
            sys_get_temp_dir(),
            $this->environment,
            $this->debug ? 'debug' : 'nodebug',
        );
    }
}
