<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Doctrine;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Doctrine\DoctrineDatatableDefinitionEnricher;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineUser;
use Zhortein\DatatableBundle\Tests\Functional\FunctionalTestCase;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class DoctrineDatatableDefinitionEnricherFunctionalTest extends FunctionalTestCase
{
    public function test_it_enriches_columns_without_explicit_type(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->setEntityClass(DoctrineUser::class)
            ->addColumn('e.email')
            ->addColumn('e.enabled')
            ->addColumn('e.createdAt')
        ;

        $this->getEnricher()->enrich($definition);

        $columns = $definition->getColumns();

        self::assertSame('string', $columns['e.email']->getType());
        self::assertSame('boolean', $columns['e.enabled']->getType());
        self::assertSame('datetime', $columns['e.createdAt']->getType());
    }

    public function test_it_preserves_explicit_column_type(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->setEntityClass(DoctrineUser::class)
            ->addColumn('e.email', type: 'custom')
        ;

        $this->getEnricher()->enrich($definition);

        self::assertSame('custom', $definition->getColumns()['e.email']->getType());
    }

    public function test_it_preserves_boolean_negation_when_guessing_the_column_type(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->setEntityClass(DoctrineUser::class)
            ->addColumn('e.enabled', negate: true)
        ;

        $this->getEnricher()->enrich($definition);

        $column = $definition->getColumns()['e.enabled'];

        self::assertSame('boolean', $column->getType());
        self::assertTrue($column->isNegated());
    }

    public function test_it_ignores_definitions_without_entity_class(): void
    {
        $definition = new DatatableDefinition('users');

        $definition->addColumn('email');

        $this->getEnricher()->enrich($definition);

        self::assertNull($definition->getColumns()['email']->getType());
    }

    public function test_it_ignores_unknown_or_unsupported_fields(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->setEntityClass(DoctrineUser::class)
            ->addColumn('e.unknown')
            ->addColumn('profile.name')
        ;

        $this->getEnricher()->enrich($definition);

        self::assertNull($definition->getColumns()['e.unknown']->getType());
        self::assertNull($definition->getColumns()['profile.name']->getType());
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    private function getEnricher(): DoctrineDatatableDefinitionEnricher
    {
        self::bootKernel();

        $enricher = self::getContainer()->get('test.'.DoctrineDatatableDefinitionEnricher::class);

        self::assertInstanceOf(DoctrineDatatableDefinitionEnricher::class, $enricher);

        return $enricher;
    }
}
