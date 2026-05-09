<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Zhortein\DatatableBundle\Attribute\AsDatatable;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Exception\DuplicateDatatableException;
use Zhortein\DatatableBundle\Exception\InvalidDatatableException;
use Zhortein\DatatableBundle\Registry\DatatableRegistry;
use Zhortein\DatatableBundle\ZhorteinDatatableBundle;

final class DatatableCompilerPassTest extends TestCase
{
    public function test_it_registers_datatable_services_from_attribute_autoconfiguration(): void
    {
        $container = new ContainerBuilder();

        $bundle = new ZhorteinDatatableBundle();
        $bundle->build($container);
        $container
            ->register(DatatableRegistry::class, DatatableRegistry::class)
            ->setAutowired(false)
            ->setAutoconfigured(false)
            ->setPublic(true)
        ;

        $container
            ->register(CompilerPassTestDatatable::class, CompilerPassTestDatatable::class)
            ->setAutoconfigured(true)
        ;

        $container->compile();

        $registry = $container->get(DatatableRegistry::class);

        self::assertInstanceOf(DatatableRegistry::class, $registry);
        self::assertTrue($registry->has('compiler-pass-test'));
        self::assertInstanceOf(CompilerPassTestDatatable::class, $registry->get('compiler-pass-test'));
    }

    public function test_it_detects_duplicate_datatable_names(): void
    {
        $container = new ContainerBuilder();

        $bundle = new ZhorteinDatatableBundle();
        $bundle->build($container);

        $container
            ->register(FirstDuplicateDatatable::class, FirstDuplicateDatatable::class)
            ->setAutoconfigured(true)
        ;

        $container
            ->register(SecondDuplicateDatatable::class, SecondDuplicateDatatable::class)
            ->setAutoconfigured(true)
        ;

        $this->expectException(DuplicateDatatableException::class);
        $this->expectExceptionMessage('A datatable named "duplicate" is already registered.');

        $container->compile();
    }

    public function test_it_rejects_invalid_datatable_services(): void
    {
        $container = new ContainerBuilder();

        $bundle = new ZhorteinDatatableBundle();
        $bundle->build($container);

        $container
            ->register(InvalidCompilerPassTestDatatable::class, InvalidCompilerPassTestDatatable::class)
            ->addTag('zhortein_datatable.datatable', ['name' => 'invalid'])
        ;

        $this->expectException(InvalidDatatableException::class);
        $this->expectExceptionMessage(sprintf(
            'The datatable service "%s" must implement "%s".',
            InvalidCompilerPassTestDatatable::class,
            DatatableInterface::class,
        ));

        $container->compile();
    }
}

#[AsDatatable]
final class CompilerPassTestDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
    }
}

#[AsDatatable(name: 'duplicate')]
final class FirstDuplicateDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
    }
}

#[AsDatatable(name: 'duplicate')]
final class SecondDuplicateDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
    }
}

final class InvalidCompilerPassTestDatatable
{
}
