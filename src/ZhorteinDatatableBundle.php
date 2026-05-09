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
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->parameters()
            ->set('zhortein_datatable.default_provider', $config['default_provider'])
            ->set('zhortein_datatable.default_theme', $config['default_theme'])
            ->set('zhortein_datatable.default_page_size', $config['default_page_size'])
            ->set('zhortein_datatable.max_page_size', $config['max_page_size'])
            ->set('zhortein_datatable.search_enabled', $config['search_enabled'])
        ;

        $container->import('../config/services.php');
    }

    public function configure(DefinitionConfigurator $definition): void
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
}
