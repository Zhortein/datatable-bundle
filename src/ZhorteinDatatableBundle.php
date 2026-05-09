<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle;

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\DependencyInjection\Compiler\DatatableCompilerPass;

final class ZhorteinDatatableBundle extends AbstractBundle
{
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
