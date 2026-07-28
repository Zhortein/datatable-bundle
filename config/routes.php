<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Zhortein\DatatableBundle\Controller\DatatableController;
use Zhortein\DatatableBundle\Controller\DatatableExportJobController;
use Zhortein\DatatableBundle\Controller\DatatableViewController;

return static function (RoutingConfigurator $routes): void {
    $routes
        ->add('zhortein_datatable_fragments', '/_zhortein/datatable/{name}/fragments')
        ->controller([DatatableController::class, 'fragments'])
        ->methods(['GET', 'POST'])
    ;

    $routes
        ->add('zhortein_datatable_child', '/_zhortein/datatable/{name}/child')
        ->controller([DatatableController::class, 'child'])
        ->methods(['GET'])
    ;

    $routes
        ->add('zhortein_datatable_export', '/_zhortein/datatable/{name}/export/{format}')
        ->controller([DatatableController::class, 'export'])
        ->methods(['GET', 'POST'])
        ->defaults([
            'format' => 'csv',
        ])
        ->requirements([
            'format' => 'csv|xlsx',
        ])
    ;

    $routes
        ->add('zhortein_datatable_export_job_submit', '/_zhortein/datatable/{name}/export-jobs/{format}')
        ->controller([DatatableExportJobController::class, 'submit'])
        ->methods(['POST'])
        ->defaults([
            'format' => 'csv',
        ])
        ->requirements([
            'format' => 'csv|xlsx',
        ])
    ;

    $routes
        ->add('zhortein_datatable_export_job_status', '/_zhortein/datatable/export-jobs/{jobIdentifier}')
        ->controller([DatatableExportJobController::class, 'status'])
        ->methods(['GET'])
        ->requirements([
            'jobIdentifier' => '[A-Za-z0-9_-]{16,128}',
        ])
    ;

    $routes
        ->add('zhortein_datatable_export_job_download', '/_zhortein/datatable/export-jobs/{jobIdentifier}/download')
        ->controller([DatatableExportJobController::class, 'download'])
        ->methods(['GET'])
        ->requirements([
            'jobIdentifier' => '[A-Za-z0-9_-]{16,128}',
        ])
    ;

    $routes
        ->add('zhortein_datatable_views_list', '/_zhortein/datatable/{name}/views')
        ->controller([DatatableViewController::class, 'list'])
        ->methods(['GET'])
    ;

    $routes
        ->add('zhortein_datatable_views_create', '/_zhortein/datatable/{name}/views')
        ->controller([DatatableViewController::class, 'create'])
        ->methods(['POST'])
    ;

    $routes
        ->add('zhortein_datatable_views_load', '/_zhortein/datatable/{name}/views/{viewIdentifier}')
        ->controller([DatatableViewController::class, 'load'])
        ->methods(['GET'])
        ->requirements([
            'viewIdentifier' => '[^/]+',
        ])
    ;

    $routes
        ->add('zhortein_datatable_views_mutate', '/_zhortein/datatable/{name}/views/{viewIdentifier}')
        ->controller([DatatableViewController::class, 'mutate'])
        ->methods(['PATCH'])
        ->requirements([
            'viewIdentifier' => '[^/]+',
        ])
    ;

    $routes
        ->add('zhortein_datatable_views_delete', '/_zhortein/datatable/{name}/views/{viewIdentifier}')
        ->controller([DatatableViewController::class, 'delete'])
        ->methods(['DELETE'])
        ->requirements([
            'viewIdentifier' => '[^/]+',
        ])
    ;
};
