<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Kernel;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Zhortein\DatatableBundle\Doctrine\DoctrineDatatableDefinitionEnricher;
use Zhortein\DatatableBundle\Doctrine\DoctrineFieldTypeGuesser;
use Zhortein\DatatableBundle\Export\ExportWriterRegistry;
use Zhortein\DatatableBundle\Preference\DatatablePreferenceProviderInterface;
use Zhortein\DatatableBundle\Provider\DataProviderRegistry;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineUser;
use Zhortein\DatatableBundle\ZhorteinDatatableBundle;

final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new DoctrineBundle();
        yield new ZhorteinDatatableBundle();
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $doctrineUserFile = new \ReflectionClass(DoctrineUser::class)->getFileName();

        if (false === $doctrineUserFile) {
            throw new \LogicException(sprintf('Unable to locate file for "%s".', DoctrineUser::class));
        }

        $doctrineUserDirectory = dirname($doctrineUserFile);

        $container->extension('framework', [
            'test' => true,
            'secret' => 'zhortein-datatable-test-secret',
            'http_method_override' => false,
            'default_locale' => 'en',
            'translator' => [
                'default_path' => __DIR__.'/../../../translations',
                'fallbacks' => ['en'],
            ],
            'router' => [
                'utf8' => true,
            ],
        ]);

        $container->extension('twig', [
            'strict_variables' => true,
            'paths' => [
                __DIR__.'/../../../templates' => 'ZhorteinDatatable',
            ],
        ]);

        $container->extension('doctrine', [
            'dbal' => [
                'driver' => 'pdo_sqlite',
                'memory' => true,
            ],
            'orm' => [
                'auto_mapping' => false,
                'mappings' => [
                    'ZhorteinDatatableBundleTests' => [
                        'is_bundle' => false,
                        'type' => 'attribute',
                        'dir' => $doctrineUserDirectory,
                        'prefix' => 'Zhortein\\DatatableBundle\\Tests\\Functional\\Fixtures\\Entity',
                        'alias' => 'ZhorteinDatatableBundleTests',
                    ],
                ],
            ],
        ]);

        $services = $container->services()
            ->defaults()
            ->autowire()
            ->autoconfigure()
        ;

        $services
            ->load(
                'Zhortein\\DatatableBundle\\Tests\\Functional\\Fixtures\\',
                __DIR__.'/../Fixtures',
            )
            ->exclude(__DIR__.'/../Fixtures/Entity')
        ;

        $services
            ->alias('test.'.DataProviderRegistry::class, DataProviderRegistry::class)
            ->public()
        ;

        $services
            ->alias('test.'.DoctrineFieldTypeGuesser::class, DoctrineFieldTypeGuesser::class)
            ->public()
        ;

        $services
            ->alias('test.'.DoctrineDatatableDefinitionEnricher::class, DoctrineDatatableDefinitionEnricher::class)
            ->public()
        ;

        $services
            ->alias('test.'.DatatablePreferenceProviderInterface::class, DatatablePreferenceProviderInterface::class)
            ->public()
        ;

        $services
            ->alias('test.'.ExportWriterRegistry::class, ExportWriterRegistry::class)
            ->public()
        ;
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(__DIR__.'/../../../config/routes.php');
    }

    public function getCacheDir(): string
    {
        return sprintf(
            '%s/zhortein-datatable-bundle/cache/%s_%s',
            sys_get_temp_dir(),
            $this->environment,
            $this->debug ? 'debug' : 'nodebug',
        );
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/zhortein-datatable-bundle/logs';
    }
}
