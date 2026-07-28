<?php

declare(strict_types=1);

use Doctrine\Persistence\ManagerRegistry;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Messenger\MessageBusInterface;
use Zhortein\DatatableBundle\Contract\ChildDatatableAuthorizationCheckerInterface;
use Zhortein\DatatableBundle\Contract\DatatableExportAuthorizationCheckerInterface;
use Zhortein\DatatableBundle\Contract\ExportCancellationInterface;
use Zhortein\DatatableBundle\Contract\ExportJobClockInterface;
use Zhortein\DatatableBundle\Contract\ExportJobDispatcherInterface;
use Zhortein\DatatableBundle\Contract\ExportJobExpiryPolicyInterface;
use Zhortein\DatatableBundle\Contract\ExportJobIdentifierGeneratorInterface;
use Zhortein\DatatableBundle\Contract\ExportJobOwnerResolverInterface;
use Zhortein\DatatableBundle\Contract\ExportJobRepositoryInterface;
use Zhortein\DatatableBundle\Contract\ExportJobResultStorageInterface;
use Zhortein\DatatableBundle\Doctrine\DoctrineCountExpressionFactory;
use Zhortein\DatatableBundle\Doctrine\DoctrineFieldMetadataResolver;
use Zhortein\DatatableBundle\Doctrine\DoctrineFieldReferenceResolver;
use Zhortein\DatatableBundle\Doctrine\DoctrineJoinApplier;
use Zhortein\DatatableBundle\Doctrine\DoctrinePaginationApplier;
use Zhortein\DatatableBundle\Export\XlsxExportWriter;
use Zhortein\DatatableBundle\Export\AllowAllDatatableExportAuthorizationChecker;
use Zhortein\DatatableBundle\Export\ConnectionAbortedExportCancellation;
use Zhortein\DatatableBundle\Icon\IconResolver;
use Zhortein\DatatableBundle\Contract\IconResolverInterface;
use Zhortein\DatatableBundle\Contract\DatatableViewAuthorizationCheckerInterface;
use Zhortein\DatatableBundle\Contract\DatatableViewOwnerResolverInterface;
use Zhortein\DatatableBundle\Contract\DatatableViewProviderInterface;
use Zhortein\DatatableBundle\Contract\EnumPresentationResolverInterface;
use Zhortein\DatatableBundle\Context\DatatableContextRequestResolver;
use Zhortein\DatatableBundle\Context\DatatableContextTransport;
use Zhortein\DatatableBundle\Renderer\DatatableSummaryRenderer;
use Zhortein\DatatableBundle\State\DatatableStateUrlSerializer;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use Zhortein\DatatableBundle\Action\ActionVisibilityCheckerInterface;
use Zhortein\DatatableBundle\Action\AllowAllActionVisibilityChecker;
use Zhortein\DatatableBundle\Action\RowActionRouteParameterResolver;
use Zhortein\DatatableBundle\Cell\CellContextFactory;
use Zhortein\DatatableBundle\Cell\CellValueResolverRegistry;
use Zhortein\DatatableBundle\Controller\DatatableController;
use Zhortein\DatatableBundle\Controller\DatatableExportJobController;
use Zhortein\DatatableBundle\Controller\DatatableViewController;
use Zhortein\DatatableBundle\DateTime\DateTimeFormatterInterface;
use Zhortein\DatatableBundle\DateTime\DefaultDateTimeFormatter;
use Zhortein\DatatableBundle\Doctrine\DoctrineDatatableDefinitionEnricher;
use Zhortein\DatatableBundle\Doctrine\DoctrineFieldTypeGuesser;
use Zhortein\DatatableBundle\Export\CsvExportWriter;
use Zhortein\DatatableBundle\Export\ExportColumnLabelResolver;
use Zhortein\DatatableBundle\Export\ExportableColumnResolver;
use Zhortein\DatatableBundle\Export\ExportLimitResolver;
use Zhortein\DatatableBundle\Export\ExportWriterRegistry;
use Zhortein\DatatableBundle\Export\Job\ExportJobCleanup;
use Zhortein\DatatableBundle\Export\Job\ExportJobRunner;
use Zhortein\DatatableBundle\Export\Job\FixedExportJobExpiryPolicy;
use Zhortein\DatatableBundle\Export\Job\InMemoryExportJobRepository;
use Zhortein\DatatableBundle\Export\Job\InMemoryExportJobResultStorage;
use Zhortein\DatatableBundle\Export\Job\MessengerExportJobDispatcher;
use Zhortein\DatatableBundle\Export\Job\NullExportJobOwnerResolver;
use Zhortein\DatatableBundle\Export\Job\RandomExportJobIdentifierGenerator;
use Zhortein\DatatableBundle\Export\Job\RunExportJobHandler;
use Zhortein\DatatableBundle\Export\Job\SystemExportJobClock;
use Zhortein\DatatableBundle\Export\Job\UnavailableExportJobDispatcher;
use Zhortein\DatatableBundle\EnumPresentation\DefaultEnumPresentationResolver;
use Zhortein\DatatableBundle\Factory\AdvancedFilterExpressionFactory;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\Factory\DatatableRequestFactory;
use Zhortein\DatatableBundle\Hierarchy\AllowAllChildDatatableAuthorizationChecker;
use Zhortein\DatatableBundle\Hierarchy\ChildDatatableContextResolver;
use Zhortein\DatatableBundle\Hierarchy\ChildDatatableInstanceFactory;
use Zhortein\DatatableBundle\Hierarchy\ChildDatatableResolver;
use Zhortein\DatatableBundle\Hierarchy\ChildDatatableRequestResolver;
use Zhortein\DatatableBundle\Hierarchy\DenyAllChildDatatableAuthorizationChecker;
use Zhortein\DatatableBundle\Hierarchy\RowValueAccessor;
use Zhortein\DatatableBundle\Preference\DatatablePreferenceProviderInterface;
use Zhortein\DatatableBundle\Preference\NullDatatablePreferenceProvider;
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;
use Zhortein\DatatableBundle\Provider\DataProviderRegistry;
use Zhortein\DatatableBundle\Provider\DoctrineOrmDataProvider;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Twig\DeclarativeTranslationExtension;
use Zhortein\DatatableBundle\Twig\DatatableTwigExtension;
use Zhortein\DatatableBundle\Twig\DateTimeTwigExtension;
use Zhortein\DatatableBundle\View\AllowAllDatatableViewAuthorizationChecker;
use Zhortein\DatatableBundle\View\DatatableViewManager;
use Zhortein\DatatableBundle\View\DenyDatatableViewAuthorizationChecker;
use Zhortein\DatatableBundle\View\InMemoryDatatableViewProvider;
use Zhortein\DatatableBundle\View\NullDatatableViewOwnerResolver;
use Zhortein\DatatableBundle\View\NullDatatableViewProvider;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
    ;

    $services->set(RowActionRouteParameterResolver::class);
    $services->set(RowValueAccessor::class);
    $services->set(ChildDatatableContextResolver::class);
    $services->set(ChildDatatableInstanceFactory::class);
    $services->set(ChildDatatableResolver::class);
    $services->set(ChildDatatableRequestResolver::class);
    $services->set(AllowAllChildDatatableAuthorizationChecker::class);
    $services->set(DenyAllChildDatatableAuthorizationChecker::class);
    $services->alias(
        ChildDatatableAuthorizationCheckerInterface::class,
        AllowAllChildDatatableAuthorizationChecker::class,
    );

    $services
        ->set(CellValueResolverRegistry::class)
        ->arg('$resolvers', tagged_iterator(CellValueResolverRegistry::SERVICE_TAG))
    ;

    $services->set(CellContextFactory::class);

    $services->set(DefaultEnumPresentationResolver::class);
    $services->alias(EnumPresentationResolverInterface::class, DefaultEnumPresentationResolver::class);

    $services->set(AllowAllDatatableExportAuthorizationChecker::class);
    $services->alias(
        DatatableExportAuthorizationCheckerInterface::class,
        AllowAllDatatableExportAuthorizationChecker::class,
    );

    $services
        ->set(DatatableContextTransport::class)
        ->arg('$secret', param('kernel.secret'))
    ;

    $services->set(DatatableContextRequestResolver::class);

    $services->set(DatatableStateUrlSerializer::class);

    $services->set(AllowAllActionVisibilityChecker::class);

    $services->alias(ActionVisibilityCheckerInterface::class, AllowAllActionVisibilityChecker::class);

    $services->set(AdvancedFilterExpressionFactory::class);

    $definitionFactory = $services
        ->set(DatatableDefinitionFactory::class)
        ->arg('$doctrineDefinitionEnricher', null)
        ->arg('$dataProviderRegistry', service(DataProviderRegistry::class))
        ->arg('$defaultProvider', param('zhortein_datatable.default_provider'))
    ;

    $services
        ->set(DatatableRequestFactory::class)
        ->arg('$defaultPageSize', param('zhortein_datatable.default_page_size'))
        ->arg('$maxPageSize', param('zhortein_datatable.max_page_size'))
    ;

    $services->set(DefaultDateTimeFormatter::class);

    $services->alias(DateTimeFormatterInterface::class, DefaultDateTimeFormatter::class);

    $services->set(NullDatatablePreferenceProvider::class);

    $services->alias(DatatablePreferenceProviderInterface::class, NullDatatablePreferenceProvider::class);

    $services->set(NullDatatableViewProvider::class);
    $services->set(InMemoryDatatableViewProvider::class);
    $services->alias(DatatableViewProviderInterface::class, NullDatatableViewProvider::class);

    $services->set(NullDatatableViewOwnerResolver::class);
    $services->alias(DatatableViewOwnerResolverInterface::class, NullDatatableViewOwnerResolver::class);

    $services->set(DenyDatatableViewAuthorizationChecker::class);
    $services->set(AllowAllDatatableViewAuthorizationChecker::class);
    $services->alias(
        DatatableViewAuthorizationCheckerInterface::class,
        DenyDatatableViewAuthorizationChecker::class,
    );

    $services->set(DatatableViewManager::class);

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

        $definitionFactory->arg('$doctrineDefinitionEnricher', service(DoctrineDatatableDefinitionEnricher::class));

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
        ->set(ExportColumnLabelResolver::class)
        ->arg('$translator', service('translator'))
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
        ->set(ExportLimitResolver::class)
        ->arg('$maxRows', param('zhortein_datatable.export.max_rows'))
        ->arg('$formatLimits', param('zhortein_datatable.export.format_limits'))
    ;

    $services
        ->set('zhortein_datatable.export.async_limit_resolver', ExportLimitResolver::class)
        ->arg('$maxRows', param('zhortein_datatable.export.async.max_rows'))
        ->arg('$formatLimits', param('zhortein_datatable.export.async.format_limits'))
    ;

    $services->set(InMemoryExportJobRepository::class);
    $services->alias(ExportJobRepositoryInterface::class, InMemoryExportJobRepository::class);

    $services->set(InMemoryExportJobResultStorage::class);
    $services->alias(ExportJobResultStorageInterface::class, InMemoryExportJobResultStorage::class);

    $services->set(SystemExportJobClock::class);
    $services->alias(ExportJobClockInterface::class, SystemExportJobClock::class);

    $services
        ->set(FixedExportJobExpiryPolicy::class)
        ->arg('$ttl', param('zhortein_datatable.export.async.ttl'))
    ;
    $services->alias(ExportJobExpiryPolicyInterface::class, FixedExportJobExpiryPolicy::class);

    $services->set(RandomExportJobIdentifierGenerator::class);
    $services->alias(ExportJobIdentifierGeneratorInterface::class, RandomExportJobIdentifierGenerator::class);

    $services->set(NullExportJobOwnerResolver::class);
    $services->alias(ExportJobOwnerResolverInterface::class, NullExportJobOwnerResolver::class);

    if (interface_exists(MessageBusInterface::class)) {
        $services
            ->set(MessengerExportJobDispatcher::class)
            ->arg('$messageBus', service('messenger.default_bus'))
        ;
        $services->alias(ExportJobDispatcherInterface::class, MessengerExportJobDispatcher::class);
    } else {
        $services->set(UnavailableExportJobDispatcher::class);
        $services->alias(ExportJobDispatcherInterface::class, UnavailableExportJobDispatcher::class);
    }

    $services
        ->set(ExportJobRunner::class)
        ->arg('$batchSize', param('zhortein_datatable.export.batch_size'))
        ->arg('$maxAttempts', param('zhortein_datatable.export.async.max_attempts'))
        ->arg('$localeAware', service('translator'))
    ;

    $services
        ->set(RunExportJobHandler::class)
        ->tag('messenger.message_handler')
    ;

    $services->set(ExportJobCleanup::class);

    $services->set(ConnectionAbortedExportCancellation::class);
    $services->alias(
        ExportCancellationInterface::class,
        ConnectionAbortedExportCancellation::class,
    );

    $services
        ->set(DatatableRenderer::class)
        ->arg('$theme', param('zhortein_datatable.default_theme'))
        ->arg('$defaultPageSize', param('zhortein_datatable.default_page_size'))
        ->arg('$searchEnabled', param('zhortein_datatable.search_enabled'))
        ->arg('$searchBuilderEnabled', param('zhortein_datatable.search_builder_enabled'))
        ->arg('$actionVisibilityChecker', service(ActionVisibilityCheckerInterface::class))
        ->arg('$contextTransport', service(DatatableContextTransport::class))
        ->arg('$stateUrlSerializer', service(DatatableStateUrlSerializer::class))
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
        ->arg('$exportAuthorizationChecker', service(DatatableExportAuthorizationCheckerInterface::class))
        ->arg('$exportLimitResolver', service(ExportLimitResolver::class))
        ->arg('$translator', service('translator'))
        ->arg('$exportBatchSize', param('zhortein_datatable.export.batch_size'))
        ->arg('$exportCancellation', service(ExportCancellationInterface::class))
        ->tag('controller.service_arguments')
    ;

    $services
        ->set(DatatableExportJobController::class)
        ->arg('$enabled', param('zhortein_datatable.export.async.enabled'))
        ->arg('$limitResolver', service('zhortein_datatable.export.async_limit_resolver'))
        ->tag('controller.service_arguments')
    ;

    $services
        ->set(DatatableViewController::class)
        ->tag('controller.service_arguments')
    ;
};
