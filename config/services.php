<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;
use Zhortein\DatatableBundle\Controller\DatatableController;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\Factory\DatatableRequestFactory;
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;
use Zhortein\DatatableBundle\Provider\DataProviderRegistry;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Twig\DatatableTwigExtension;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
    ;

    $services->set(DatatableDefinitionFactory::class);

    $services->set(DatatableRequestFactory::class);

    $services
        ->set(ArrayDataProvider::class)
        ->tag('zhortein_datatable.data_provider', [
            'name' => ArrayDataProvider::PROVIDER_NAME,
        ])
    ;

    $services
        ->set(DataProviderRegistry::class)
        ->arg('$providers', tagged_iterator('zhortein_datatable.data_provider', 'name'))
    ;

    $services->set(DatatableRenderer::class);

    $services->set(DatatableTwigExtension::class);

    $services
        ->set(DatatableController::class)
        ->tag('controller.service_arguments')
    ;
};
