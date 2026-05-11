<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Exception\DuplicateDatatableException;
use Zhortein\DatatableBundle\Exception\InvalidDatatableException;
use Zhortein\DatatableBundle\Registry\DatatableRegistry;

final class DatatableCompilerPass implements CompilerPassInterface
{
    public const string DATATABLE_TAG = 'zhortein_datatable.datatable';

    public function process(ContainerBuilder $container): void
    {
        $this->ensureRegistryDefinition($container);

        $datatableReferences = [];
        $datatableServiceIds = [];

        foreach ($container->findTaggedServiceIds(self::DATATABLE_TAG) as $serviceId => $tags) {
            $definition = $container->findDefinition($serviceId);
            $className = $this->resolveClassName($serviceId, $definition);

            if (!is_a($className, DatatableInterface::class, true)) {
                throw new InvalidDatatableException(sprintf('The datatable service "%s" must implement "%s".', $serviceId, DatatableInterface::class));
            }

            foreach ($tags as $tag) {
                if (!is_array($tag)) {
                    continue;
                }

                /** @var array<string, mixed> $tag */
                $name = $this->resolveDatatableName($serviceId, $definition, $tag);

                if (isset($datatableReferences[$name])) {
                    throw new DuplicateDatatableException(sprintf('A datatable named "%s" is already registered.', $name));
                }

                $datatableReferences[$name] = new Reference($serviceId);
                $datatableServiceIds[$name] = $serviceId;
            }
        }

        $registryDefinition = $container->findDefinition(DatatableRegistry::class);
        $registryDefinition->setArgument('$datatables', new ServiceLocatorArgument($datatableReferences));
        $registryDefinition->setArgument('$datatableServiceIds', $datatableServiceIds);
    }

    private function ensureRegistryDefinition(ContainerBuilder $container): void
    {
        if ($container->hasDefinition(DatatableRegistry::class)) {
            return;
        }

        $container
            ->register(DatatableRegistry::class, DatatableRegistry::class)
            ->setAutowired(false)
            ->setAutoconfigured(false)
            ->setPublic(false)
            ->setArgument('$datatables', new ServiceLocatorArgument([]))
            ->setArgument('$datatableServiceIds', [])
        ;
    }

    /**
     * @return class-string
     */
    private function resolveClassName(string $serviceId, Definition $definition): string
    {
        $className = $definition->getClass() ?? $serviceId;

        if (!class_exists($className)) {
            throw new InvalidDatatableException(sprintf('The datatable service "%s" has an invalid class "%s".', $serviceId, $className));
        }

        return $className;
    }

    /**
     * @param array<string, mixed> $tag
     */
    private function resolveDatatableName(string $serviceId, Definition $definition, array $tag): string
    {
        $name = $tag['name'] ?? null;

        if (is_string($name) && '' !== trim($name)) {
            return trim($name);
        }

        $className = $this->resolveClassName($serviceId, $definition);
        $reflectionClass = new \ReflectionClass($className);

        return $this->normalizeDatatableName($reflectionClass->getShortName());
    }

    private function normalizeDatatableName(string $shortClassName): string
    {
        $name = preg_replace('/Datatable$/', '', $shortClassName) ?? $shortClassName;
        $name = preg_replace('/(?<!^)[A-Z]/', '-$0', $name) ?? $name;

        return strtolower($name);
    }
}
