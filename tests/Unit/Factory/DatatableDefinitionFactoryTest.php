<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Factory;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\Registry\DatatableRegistry;

final class DatatableDefinitionFactoryTest extends TestCase
{
    public function test_it_creates_definition_from_registered_datatable(): void
    {
        $factory = new DatatableDefinitionFactory($this->createRegistry());

        $definition = $factory->create('users');

        self::assertSame('users', $definition->getName());
        self::assertSame(\stdClass::class, $definition->getEntityClass());
        self::assertSame('user', $definition->getTranslationDomain());

        $columns = $definition->getColumns();

        self::assertArrayHasKey('e.id', $columns);
        self::assertArrayHasKey('e.email', $columns);
        self::assertFalse($columns['e.id']->isVisible());
        self::assertSame('Email', $columns['e.email']->getLabel());
    }

    private function createRegistry(): DatatableRegistry
    {
        $datatable = new FactoryTestDatatable();

        return new DatatableRegistry(
            new ServiceLocator([
                'users' => static fn (): FactoryTestDatatable => $datatable,
            ]),
            ['users' => FactoryTestDatatable::class],
        );
    }
}

final class FactoryTestDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setEntityClass(\stdClass::class)
            ->setTranslationDomain('user')
            ->addColumn('e.id', visible: false)
            ->addColumn('e.email', label: 'Email')
        ;
    }
}
