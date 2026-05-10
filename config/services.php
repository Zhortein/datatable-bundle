<?php

declare(strict_types=1);

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;
use Zhortein\DatatableBundle\Action\ActionVisibilityCheckerInterface;
use Zhortein\DatatableBundle\Action\AllowAllActionVisibilityChecker;
use Zhortein\DatatableBundle\Action\RowActionRouteParameterResolver;
use Zhortein\DatatableBundle\Controller\DatatableController;
use Zhortein\DatatableBundle\DateTime\DateTimeFormatterInterface;
use Zhortein\DatatableBundle\DateTime\DefaultDateTimeFormatter;
use Zhortein\DatatableBundle\Doctrine\DoctrineDatatableDefinitionEnricher;
use Zhortein\DatatableBundle\Doctrine\DoctrineFieldTypeGuesser;
use Zhortein\DatatableBundle\Export\CsvExportWriter;
use Zhortein\DatatableBundle\Export\ExportWriterRegistry;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\Factory\DatatableRequestFactory;
use Zhortein\DatatableBundle\Preference\DatatablePreferenceProviderInterface;
use Zhortein\DatatableBundle\Preference\NullDatatablePreferenceProvider;
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;
use Zhortein\DatatableBundle\Provider\DataProviderRegistry;
use Zhortein\DatatableBundle\Provider\DoctrineOrmDataProvider;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Twig\DatatableTwigExtension;
use Zhortein\DatatableBundle\Twig\DateTimeTwigExtension;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
    ;

    $services->set(RowActionRouteParameterResolver::class);

    $services->set(AllowAllActionVisibilityChecker::class);

    $services->alias(ActionVisibilityCheckerInterface::class, AllowAllActionVisibilityChecker::class);

    $services->set(DatatableDefinitionFactory::class);

    $services
        ->set(DatatableRequestFactory::class)
        ->arg('$defaultPageSize', param('zhortein_datatable.default_page_size'))
        ->arg('$maxPageSize', param('zhortein_datatable.max_page_size'))
    ;

    $services->set(DefaultDateTimeFormatter::class);

    $services->alias(DateTimeFormatterInterface::class, DefaultDateTimeFormatter::class);

    $services->set(NullDatatablePreferenceProvider::class);

    $services->alias(DatatablePreferenceProviderInterface::class, NullDatatablePreferenceProvider::class);

    if (interface_exists(ManagerRegistry::class)) {
        $services->set(DoctrineFieldTypeGuesser::class);

        $services->set(DoctrineDatatableDefinitionEnricher::class);

        $services
            ->set(DoctrineOrmDataProvider::class)
            ->tag('zhortein_datatable.data_provider', [
                'name' => DoctrineOrmDataProvider::PROVIDER_NAME,
            ])
        ;
    }

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

    $services
        ->set(CsvExportWriter::class)
        ->tag('zhortein_datatable.export_writer', [
            'name' => CsvExportWriter::WRITER_NAME,
        ])
    ;

    $services
        ->set(ExportWriterRegistry::class)
        ->arg('$writers', tagged_iterator('zhortein_datatable.export_writer', 'name'))
    ;

    $services
        ->set(DatatableRenderer::class)
        ->arg('$theme', param('zhortein_datatable.default_theme'))
        ->arg('$defaultPageSize', param('zhortein_datatable.default_page_size'))
        ->arg('$searchEnabled', param('zhortein_datatable.search_enabled'))
        ->arg('$defaultTableOptions', [
            'tableStriped' => param('zhortein_datatable.bootstrap.table_striped'),
            'tableHover' => param('zhortein_datatable.bootstrap.table_hover'),
            'tableBordered' => param('zhortein_datatable.bootstrap.table_bordered'),
            'tableBorderless' => param('zhortein_datatable.bootstrap.table_borderless'),
            'tableSmall' => param('zhortein_datatable.bootstrap.table_small'),
            'tableResponsive' => param('zhortein_datatable.bootstrap.table_responsive'),
        ])
    ;

    $services->set(DatatableTwigExtension::class);

    $services->set(DateTimeTwigExtension::class);

    $services
        ->set(DatatableController::class)
        ->tag('controller.service_arguments')
    ;
};
