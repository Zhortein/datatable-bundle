<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Doctrine;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Doctrine\DoctrineDatatableDefinitionEnricher;
use Zhortein\DatatableBundle\Enum\JoinType;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineAuditLog;
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
            ->addColumn('e.status')
        ;

        $this->getEnricher()->enrich($definition);

        $columns = $definition->getColumns();

        self::assertSame('string', $columns['e.email']->getType());
        self::assertSame('boolean', $columns['e.enabled']->getType());
        self::assertSame('datetime', $columns['e.createdAt']->getType());
        self::assertSame('enum', $columns['e.status']->getType());
    }

    public function test_it_enriches_declared_mapped_chained_and_custom_join_columns(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->setEntityClass(DoctrineUser::class)
            ->addJoin('organization', 'e.organization')
            ->addJoin('group', 'organization.group')
            ->addCustomJoin(
                alias: 'audit',
                targetEntityClass: DoctrineAuditLog::class,
                condition: 'audit.objectId = e.id',
                type: JoinType::Left,
            )
            ->addColumn('organization.enabled')
            ->addColumn('group.name')
            ->addColumn('audit.objectId')
        ;

        $this->getEnricher()->enrich($definition);

        $columns = $definition->getColumns();

        self::assertSame('boolean', $columns['organization.enabled']->getType());
        self::assertSame('string', $columns['group.name']->getType());
        self::assertSame('numeric', $columns['audit.objectId']->getType());
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
            ->addComputedColumn('summary', 'summary')
        ;

        $this->getEnricher()->enrich($definition);

        self::assertNull($definition->getColumns()['e.unknown']->getType());
        self::assertNull($definition->getColumns()['profile.name']->getType());
        self::assertNull($definition->getColumns()['summary']->getType());
    }

    public function test_definition_factory_applies_enrichment_in_the_runtime_flow(): void
    {
        self::bootKernel();

        $factory = self::getContainer()->get('test.'.DatatableDefinitionFactory::class);

        self::assertInstanceOf(DatatableDefinitionFactory::class, $factory);

        $definition = $factory->create('doctrine-users');
        $columns = $definition->getColumns();

        self::assertSame('numeric', $columns['e.id']->getType());
        self::assertSame('string', $columns['e.email']->getType());
        self::assertSame('boolean', $columns['e.enabled']->getType());
        self::assertSame('string', $columns['organization.name']->getType());
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
