<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Registry;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Exception\DatatableNotFoundException;
use Zhortein\DatatableBundle\Exception\InvalidDatatableException;
use Zhortein\DatatableBundle\Registry\DatatableRegistry;

final class DatatableRegistryTest extends TestCase
{
    public function test_it_resolves_registered_datatable(): void
    {
        $datatable = new RegistryTestDatatable();

        $registry = new DatatableRegistry(
            new ServiceLocator([
                'users' => static fn (): RegistryTestDatatable => $datatable,
            ]),
            ['users' => RegistryTestDatatable::class],
        );

        self::assertTrue($registry->has('users'));
        self::assertSame($datatable, $registry->get('users'));
        self::assertSame(['users'], $registry->getNames());
        self::assertSame(['users' => RegistryTestDatatable::class], $registry->getServiceIds());
    }

    public function test_it_throws_when_datatable_is_missing(): void
    {
        $registry = new DatatableRegistry(new ServiceLocator([]), []);

        $this->expectException(DatatableNotFoundException::class);
        $this->expectExceptionMessage('The datatable "missing" is not registered.');

        $registry->get('missing');
    }

    public function test_it_throws_when_service_does_not_implement_datatable_interface(): void
    {
        $registry = new DatatableRegistry(
            new ServiceLocator([
                'invalid' => static fn (): \stdClass => new \stdClass(),
            ]),
            ['invalid' => \stdClass::class],
        );

        $this->expectException(InvalidDatatableException::class);
        $this->expectExceptionMessage(sprintf(
            'The datatable "invalid" must implement "%s".',
            DatatableInterface::class,
        ));

        $registry->get('invalid');
    }
}

final class RegistryTestDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
    }
}
