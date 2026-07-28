<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Cell\CellValueResolverRegistry;
use Zhortein\DatatableBundle\Contract\CellValueResolverInterface;
use Zhortein\DatatableBundle\DependencyInjection\Compiler\DatatableCompilerPass;
use Zhortein\DatatableBundle\Preference\CacheDatatablePreferenceProvider;
use Zhortein\DatatableBundle\Preference\DatatablePreferenceProviderInterface;

final class ZhorteinDatatableBundle extends AbstractBundle
{
    /**
     * @param array<array-key, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $bootstrapConfig = $config['bootstrap'] ?? [];

        if (!is_array($bootstrapConfig)) {
            throw new \LogicException('The bootstrap configuration must be an array.');
        }

        $tableConfig = $bootstrapConfig['table'] ?? [];

        if (!is_array($tableConfig)) {
            throw new \LogicException('The bootstrap table configuration must be an array.');
        }

        $exportConfig = $config['export'] ?? [];

        if (!is_array($exportConfig)) {
            throw new \LogicException('The export configuration must be an array.');
        }

        $csvConfig = $exportConfig['csv'] ?? [];

        if (!is_array($csvConfig)) {
            throw new \LogicException('The CSV export configuration must be an array.');
        }

        $asyncConfig = $exportConfig['async'] ?? [];

        if (!is_array($asyncConfig)) {
            throw new \LogicException('The asynchronous export configuration must be an array.');
        }

        $preferencesConfig = $config['preferences'] ?? [];

        if (!is_array($preferencesConfig)) {
            throw new \LogicException('The datatable preferences configuration must be an array.');
        }

        /** @var array{
         *     enabled: bool,
         *     cache_pool: string,
         *     ttl: int,
         *     schema_version: string
         * } $preferencesConfig */

        /** @var array{
         *     max_rows: int,
         *     batch_size: int,
         *     format_limits: array{csv: int|null, xlsx: int|null},
         *     csv: array{
         *         delimiter: string,
         *         enclosure: string,
         *         escape: string,
         *         bom: bool
         *     },
         *     async: array{
         *         enabled: bool,
         *         max_rows: int,
         *         ttl: int,
         *         max_attempts: int,
         *         format_limits: array{csv: int|null, xlsx: int|null}
         *     }
         * } $exportConfig */

        /** @var array{
         *     delimiter: string,
         *     enclosure: string,
         *     escape: string,
         *     bom: bool
         * } $csvConfig */

        /** @var array{
         *     striped: bool,
         *     hover: bool,
         *     bordered: bool,
         *     borderless: bool,
         *     small: bool,
         *     responsive: bool
         * } $tableConfig
         */
        $configurator->parameters()
            ->set('zhortein_datatable.default_provider', $config['default_provider'])
            ->set('zhortein_datatable.default_theme', $config['default_theme'])
            ->set('zhortein_datatable.default_page_size', $config['default_page_size'])
            ->set('zhortein_datatable.max_page_size', $config['max_page_size'])
            ->set('zhortein_datatable.search_enabled', $config['search_enabled'])
            ->set('zhortein_datatable.search_builder_enabled', $config['search_builder_enabled'])
            ->set('zhortein_datatable.icons', $config['icons'] ?? [])
            ->set('zhortein_datatable.bootstrap.table_striped', $tableConfig['striped'])
            ->set('zhortein_datatable.bootstrap.table_hover', $tableConfig['hover'])
            ->set('zhortein_datatable.bootstrap.table_bordered', $tableConfig['bordered'])
            ->set('zhortein_datatable.bootstrap.table_borderless', $tableConfig['borderless'])
            ->set('zhortein_datatable.bootstrap.table_small', $tableConfig['small'])
            ->set('zhortein_datatable.bootstrap.table_responsive', $tableConfig['responsive'])
            ->set('zhortein_datatable.export.max_rows', $exportConfig['max_rows'])
            ->set('zhortein_datatable.export.batch_size', $exportConfig['batch_size'])
            ->set('zhortein_datatable.export.format_limits', $exportConfig['format_limits'])
            ->set('zhortein_datatable.export.csv.delimiter', $csvConfig['delimiter'])
            ->set('zhortein_datatable.export.csv.enclosure', $csvConfig['enclosure'])
            ->set('zhortein_datatable.export.csv.escape', $csvConfig['escape'])
            ->set('zhortein_datatable.export.csv.bom', $csvConfig['bom'])
            ->set('zhortein_datatable.export.async.enabled', $asyncConfig['enabled'])
            ->set('zhortein_datatable.export.async.max_rows', $asyncConfig['max_rows'])
            ->set('zhortein_datatable.export.async.ttl', $asyncConfig['ttl'])
            ->set('zhortein_datatable.export.async.max_attempts', $asyncConfig['max_attempts'])
            ->set('zhortein_datatable.export.async.format_limits', $asyncConfig['format_limits'])
            ->set('zhortein_datatable.preferences.enabled', $preferencesConfig['enabled'])
            ->set('zhortein_datatable.preferences.ttl', $preferencesConfig['ttl'])
            ->set('zhortein_datatable.preferences.schema_version', $preferencesConfig['schema_version'])
        ;

        $configurator->import('../config/services.php');

        if ($preferencesConfig['enabled']) {
            $container
                ->getDefinition(CacheDatatablePreferenceProvider::class)
                ->setArgument('$cachePool', new Reference($preferencesConfig['cache_pool']))
            ;
            $container->setAlias(
                DatatablePreferenceProviderInterface::class,
                CacheDatatablePreferenceProvider::class,
            );
        }
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $this->configureRootNode($definition);
    }

    /**
     * Symfony Config builder generics differ across supported dependency versions.
     */
    private function configureRootNode(DefinitionConfigurator $definition): void
    {
        $definition
            ->rootNode()
                ->children()
                    ->arrayNode('preferences')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->booleanNode('enabled')
                                ->defaultFalse()
                            ->end()
                            ->scalarNode('cache_pool')
                                ->defaultValue('cache.app')
                                ->cannotBeEmpty()
                            ->end()
                            ->integerNode('ttl')
                                ->min(1)
                                ->defaultValue(31536000)
                            ->end()
                            ->scalarNode('schema_version')
                                ->defaultValue('1')
                                ->cannotBeEmpty()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('export')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->integerNode('max_rows')
                                ->min(1)
                                ->defaultValue(10000)
                            ->end()
                            ->integerNode('batch_size')
                                ->min(1)
                                ->max(10000)
                                ->defaultValue(500)
                            ->end()
                            ->arrayNode('format_limits')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->integerNode('csv')
                                        ->min(1)
                                        ->defaultNull()
                                    ->end()
                                    ->integerNode('xlsx')
                                        ->min(1)
                                        ->defaultNull()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('csv')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('delimiter')
                                        ->defaultValue(',')
                                    ->end()
                                    ->scalarNode('enclosure')
                                        ->defaultValue('"')
                                    ->end()
                                    ->scalarNode('escape')
                                        ->defaultValue('\\')
                                    ->end()
                                    ->booleanNode('bom')
                                        ->defaultFalse()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('async')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->booleanNode('enabled')
                                        ->defaultFalse()
                                    ->end()
                                    ->integerNode('max_rows')
                                        ->min(1)
                                        ->defaultValue(250000)
                                    ->end()
                                    ->integerNode('ttl')
                                        ->min(1)
                                        ->defaultValue(86400)
                                    ->end()
                                    ->integerNode('max_attempts')
                                        ->min(1)
                                        ->max(100)
                                        ->defaultValue(3)
                                    ->end()
                                    ->arrayNode('format_limits')
                                        ->addDefaultsIfNotSet()
                                        ->children()
                                            ->integerNode('csv')
                                                ->min(1)
                                                ->defaultNull()
                                            ->end()
                                            ->integerNode('xlsx')
                                                ->min(1)
                                                ->defaultNull()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->enumNode('default_provider')
                        ->values(['array', 'doctrine', 'http'])
                        ->defaultValue('doctrine')
                    ->end()
                    ->enumNode('default_theme')
                        ->values(['bootstrap'])
                        ->defaultValue('bootstrap')
                    ->end()
                        ->integerNode('default_page_size')
                        ->min(1)
                        ->defaultValue(25)
                    ->end()
                    ->integerNode('max_page_size')
                        ->min(1)
                        ->defaultValue(500)
                    ->end()
                        ->booleanNode('search_enabled')
                        ->defaultFalse()
                    ->end()
                    ->booleanNode('search_builder_enabled')
                        ->defaultFalse()
                    ->end()
                    ->arrayNode('icons')
                        ->useAttributeAsKey('name')
                        ->scalarPrototype()->end()
                    ->end()
                    ->arrayNode('bootstrap')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->arrayNode('table')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->booleanNode('striped')
                                        ->defaultTrue()
                                    ->end()
                                    ->booleanNode('hover')
                                        ->defaultTrue()
                                    ->end()
                                    ->booleanNode('bordered')
                                        ->defaultFalse()
                                    ->end()
                                    ->booleanNode('borderless')
                                        ->defaultFalse()
                                    ->end()
                                    ->booleanNode('small')
                                        ->defaultFalse()
                                    ->end()
                                    ->booleanNode('responsive')
                                        ->defaultTrue()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container
            ->registerForAutoconfiguration(CellValueResolverInterface::class)
            ->addTag(CellValueResolverRegistry::SERVICE_TAG)
        ;

        $container->registerAttributeForAutoconfiguration(
            AsDatatable::class,
            static function (ChildDefinition $definition, AsDatatable $attribute, \Reflector $reflector): void {
                if (!$reflector instanceof \ReflectionClass) {
                    return;
                }

                $tag = [
                    'name' => $attribute->name ?? self::normalizeDatatableName($reflector->getShortName()),
                ];

                if (null !== $attribute->label) {
                    $tag['label'] = $attribute->label;
                }

                if (null !== $attribute->provider) {
                    $tag['provider'] = $attribute->provider;
                }

                $definition->addTag(DatatableCompilerPass::DATATABLE_TAG, $tag);
            }
        );

        $container->addCompilerPass(new DatatableCompilerPass());
    }

    private static function normalizeDatatableName(string $shortClassName): string
    {
        $name = preg_replace('/Datatable$/', '', $shortClassName) ?? $shortClassName;
        $name = preg_replace('/(?<!^)[A-Z]/', '-$0', $name) ?? $name;

        return strtolower($name);
    }

    public function prependExtension(ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        if (!$this->isAssetMapperAvailable($container)) {
            return;
        }

        $container->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    dirname(__DIR__).'/assets' => '@zhortein/datatable-bundle',
                ],
            ],
        ]);
    }

    /**
     * AssetMapper is optional for host applications.
     */
    private function isAssetMapperAvailable(ContainerBuilder $builder): bool
    {
        if (!interface_exists('Symfony\Component\AssetMapper\AssetMapperInterface')) {
            return false;
        }

        if (!$builder->hasParameter('kernel.bundles_metadata')) {
            return false;
        }

        $bundlesMetadata = $builder->getParameter('kernel.bundles_metadata');

        if (!is_array($bundlesMetadata)) {
            return false;
        }

        $frameworkBundle = $bundlesMetadata['FrameworkBundle'] ?? null;

        if (!is_array($frameworkBundle)) {
            return false;
        }

        $frameworkBundlePath = $frameworkBundle['path'] ?? null;

        if (!is_string($frameworkBundlePath)) {
            return false;
        }

        return is_file($frameworkBundlePath.'/Resources/config/asset_mapper.php');
    }
}
