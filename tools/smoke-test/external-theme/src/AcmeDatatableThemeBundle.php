<?php

declare(strict_types=1);

namespace Zhortein\AcmeDatatableTheme;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Zhortein\AcmeDatatableTheme\Theme\AcmeTheme;

final class AcmeDatatableThemeBundle extends AbstractBundle
{
    /**
     * @param array<array-key, mixed> $config
     */
    public function loadExtension(
        array $config,
        ContainerConfigurator $container,
        ContainerBuilder $builder,
    ): void {
        $container->services()
            ->set(AcmeTheme::class)
            ->autoconfigure()
        ;
    }
}
