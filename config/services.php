<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Zhortein\DatatableBundle\Controller\DatatableController;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Twig\DatatableTwigExtension;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
    ;

    $services->set(DatatableDefinitionFactory::class);

    $services->set(DatatableRenderer::class);

    $services->set(DatatableTwigExtension::class);

    $services
        ->set(DatatableController::class)
        ->tag('controller.service_arguments')
    ;
};
