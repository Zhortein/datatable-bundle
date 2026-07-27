<?php

declare(strict_types=1);

use Doctrine\Persistence\ManagerRegistry;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Zhortein\DatatableBundle\Doctrine\DoctrineCountExpressionFactory;
use Zhortein\DatatableBundle\Doctrine\DoctrineFieldMetadataResolver;
use Zhortein\DatatableBundle\Doctrine\DoctrineFieldReferenceResolver;
use Zhortein\DatatableBundle\Doctrine\DoctrineJoinApplier;
use Zhortein\DatatableBundle\Doctrine\DoctrinePaginationApplier;
use Zhortein\DatatableBundle\Export\XlsxExportWriter;
use Zhortein\DatatableBundle\Icon\IconResolver;
use Zhortein\DatatableBundle\Contract\IconResolverInterface;
use Zhortein\DatatableBundle\Context\DatatableContextRequestResolver;
use Zhortein\DatatableBundle\Context\DatatableContextTransport;
use Zhortein\DatatableBundle\Renderer\DatatableSummaryRenderer;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use Zhortein\DatatableBundle\Action\ActionVisibilityCheckerInterface;
use Zhortein\DatatableBundle\Action\AllowAllActionVisibilityChecker;
use Zhortein\DatatableBundle\Action\RowActionRouteParameterResolver;
use Zhortein\DatatableBundle\Controller\DatatableController;
use Zhortein\DatatableBundle\DateTime\DateTimeFormatterInterface;
use Zhortein\DatatableBundle\DateTime\DefaultDateTimeFormatter;
use Zhortein\DatatableBundle\Doctrine\DoctrineDatatableDefinitionEnricher;
use Zhortein\DatatableBundle\Doctrine\DoctrineFieldTypeGuesser;
use Zhortein\DatatableBundle\Export\CsvExportWriter;
use Zhortein\DatatableBundle\Export\ExportableColumnResolver;
use Zhortein\DatatableBundle\Export\ExportWriterRegistry;
use Zhortein\DatatableBundle\Factory\AdvancedFilterExpressionFactory;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\Factory\DatatableRequestFactory;
use Zhortein\DatatableBundle\Preference\DatatablePreferenceProviderInterface;
use Zhortein\DatatableBundle\Preference\NullDatatablePreferenceProvider;
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;
use Zhortein\DatatableBundle\Provider\DataProviderRegistry;
use Zhortein\DatatableBundle\Provider\DoctrineOrmDataProvider;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Twig\DeclarativeTranslationExtension;
use Zhortein\DatatableBundle\Twig\DatatableTwigExtension;
use Zhortein\DatatableBundle\Twig\DateTimeTwigExtension;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
    ;

    $services->set(RowActionRouteParameterResolver::class);

    $services
        ->set(DatatableContextTransport::class)
        ->arg('$secret', param('kernel.secret'))
    ;

    $services->set(DatatableContextRequestResolver::class);

    $services->set(AllowAllActionVisibilityChecker::class);

    $services->alias(ActionVisibilityCheckerInterface::class, AllowAllActionVisibilityChecker::class);

    $services->set(AdvancedFilterExpressionFactory::class);

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

    $services
        ->set(IconResolver::class)
        ->arg('$icons', param('zhortein_datatable.icons'))
    ;

    $services->alias(IconResolverInterface::class, IconResolver::class);

    if (interface_exists(ManagerRegistry::class)) {
        $services->set(DoctrineFieldTypeGuesser::class);

        $services->set(DoctrineCountExpressionFactory::class);
        $services->set(DoctrineDatatableDefinitionEnricher::class);
        $services->set(DoctrineFieldReferenceResolver::class);
        $services->set(DoctrineJoinApplier::class);
        $services->set(DoctrinePaginationApplier::class);
        $services->set(DoctrineFieldMetadataResolver::class);

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
        ->arg('$defaultProvider', param('zhortein_datatable.default_provider'))
    ;

    $services
        ->set(ExportableColumnResolver::class)
    ;

    $services
        ->set(CsvExportWriter::class)
        ->arg('$delimiter', param('zhortein_datatable.export.csv.delimiter'))
        ->arg('$enclosure', param('zhortein_datatable.export.csv.enclosure'))
        ->arg('$escape', param('zhortein_datatable.export.csv.escape'))
        ->arg('$withBom', param('zhortein_datatable.export.csv.bom'))
        ->tag('zhortein_datatable.export_writer', [
            'name' => CsvExportWriter::WRITER_NAME,
        ])
    ;

    if (class_exists(Writer::class)) {
        $services
            ->set(XlsxExportWriter::class)
            ->tag('zhortein_datatable.export_writer', [
                'name' => XlsxExportWriter::WRITER_NAME,
            ])
        ;
    }

    $services
        ->set(ExportWriterRegistry::class)
        ->arg('$writers', tagged_iterator('zhortein_datatable.export_writer', 'name'))
    ;

    $services
        ->set(DatatableRenderer::class)
        ->arg('$theme', param('zhortein_datatable.default_theme'))
        ->arg('$defaultPageSize', param('zhortein_datatable.default_page_size'))
        ->arg('$searchEnabled', param('zhortein_datatable.search_enabled'))
        ->arg('$searchBuilderEnabled', param('zhortein_datatable.search_builder_enabled'))
        ->arg('$actionVisibilityChecker', service(ActionVisibilityCheckerInterface::class))
        ->arg('$contextTransport', service(DatatableContextTransport::class))
        ->arg('$defaultTableOptions', [
            'tableStriped' => param('zhortein_datatable.bootstrap.table_striped'),
            'tableHover' => param('zhortein_datatable.bootstrap.table_hover'),
            'tableBordered' => param('zhortein_datatable.bootstrap.table_bordered'),
            'tableBorderless' => param('zhortein_datatable.bootstrap.table_borderless'),
            'tableSmall' => param('zhortein_datatable.bootstrap.table_small'),
            'tableResponsive' => param('zhortein_datatable.bootstrap.table_responsive'),
        ])
    ;

    $services->set(DatatableSummaryRenderer::class);

    $services->set(DatatableTwigExtension::class);

    $services->set(DeclarativeTranslationExtension::class);

    $services->set(DateTimeTwigExtension::class);

    $services
        ->set(DatatableController::class)
        ->tag('controller.service_arguments')
    ;
};
