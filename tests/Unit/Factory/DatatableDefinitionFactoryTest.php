<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Factory;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Exception\InvalidDatatableException;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\Provider\DataProviderRegistry;
use Zhortein\DatatableBundle\Registry\DatatableRegistry;
use Zhortein\DatatableBundle\Sorting\SortCriterion;

final class DatatableDefinitionFactoryTest extends TestCase
{
    public function test_it_creates_definition_from_registered_datatable(): void
    {
        $factory = new DatatableDefinitionFactory($this->createRegistry());

        $definition = $factory->create('users');

        self::assertSame('users', $definition->getName());
        self::assertSame(\stdClass::class, $definition->getEntityClass());
        self::assertSame('user', $definition->getTranslationDomain());
        self::assertSame('array', $definition->getOption(DataProviderRegistry::OPTION_PROVIDER));

        $columns = $definition->getColumns();

        self::assertArrayHasKey('e.id', $columns);
        self::assertArrayHasKey('e.email', $columns);
        self::assertFalse($columns['e.id']->isVisible());
        self::assertSame('Email', $columns['e.email']->getLabel());
    }

    public function test_it_rejects_a_default_sort_on_a_column_it_cannot_sort(): void
    {
        $cases = [
            'undeclared column' => 'e.missing',
            'unsortable column' => 'e.id',
        ];

        foreach ($cases as $case => $field) {
            $datatable = new FactoryTestDatatable(SortCriterion::create($field));
            $factory = new DatatableDefinitionFactory($this->createRegistry($datatable));

            try {
                $factory->create('users');
                self::fail(sprintf('No exception for the %s.', $case));
            } catch (InvalidDatatableException $exception) {
                self::assertStringContainsString(sprintf('"%s"', $field), $exception->getMessage(), $case);
            }
        }
    }

    private function createRegistry(FactoryTestDatatable $datatable = new FactoryTestDatatable()): DatatableRegistry
    {
        return new DatatableRegistry(
            new ServiceLocator([
                'users' => static fn (): FactoryTestDatatable => $datatable,
            ]),
            ['users' => FactoryTestDatatable::class],
            ['users' => 'array'],
        );
    }
}

final readonly class FactoryTestDatatable implements DatatableInterface
{
    public function __construct(
        private ?SortCriterion $defaultSort = null,
    ) {
    }

    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setEntityClass(\stdClass::class)
            ->setTranslationDomain('user')
            ->addColumn('e.id', visible: false, sortable: false)
            ->addColumn('e.email', label: 'Email')
        ;

        if (null !== $this->defaultSort) {
            $definition->setDefaultSorts($this->defaultSort);
        }
    }
}
