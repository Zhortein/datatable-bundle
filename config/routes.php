<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Zhortein\DatatableBundle\Controller\DatatableController;

return static function (RoutingConfigurator $routes): void {
    $routes
        ->add('zhortein_datatable_fragments', '/_zhortein/datatable/{name}/fragments')
        ->controller([DatatableController::class, 'fragments'])
        ->methods(['GET', 'POST'])
    ;

    $routes
        ->add('zhortein_datatable_export', '/_zhortein/datatable/{name}/export/{format}')
        ->controller([DatatableController::class, 'export'])
        ->methods(['GET', 'POST'])
        ->defaults([
            'format' => 'csv',
        ])
        ->requirements([
            'format' => 'csv',
        ])
    ;
};
