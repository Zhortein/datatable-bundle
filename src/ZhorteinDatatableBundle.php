<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\DependencyInjection\Compiler\DatatableCompilerPass;

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
            ->set('zhortein_datatable.bootstrap.table_striped', $tableConfig['striped'])
            ->set('zhortein_datatable.bootstrap.table_hover', $tableConfig['hover'])
            ->set('zhortein_datatable.bootstrap.table_bordered', $tableConfig['bordered'])
            ->set('zhortein_datatable.bootstrap.table_borderless', $tableConfig['borderless'])
            ->set('zhortein_datatable.bootstrap.table_small', $tableConfig['small'])
            ->set('zhortein_datatable.bootstrap.table_responsive', $tableConfig['responsive'])
        ;

        $configurator->import('../config/services.php');
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
                    ->enumNode('default_provider')
                        ->values(['array', 'doctrine'])
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
