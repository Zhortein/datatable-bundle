<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\AggregateFunction;
use Zhortein\DatatableBundle\Enum\FilterOperator;
use Zhortein\DatatableBundle\Enum\FilterType;
use Zhortein\DatatableBundle\Enum\JoinType;
use Zhortein\DatatableBundle\Provider\DoctrineOrmDataProvider;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineAuditLog;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineOrganization;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineUser;
use Zhortein\DatatableBundle\Tests\Functional\FunctionalTestCase;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class DoctrineCountStrategyFunctionalTest extends FunctionalTestCase
{
    use DoctrineSchemaMetadataTrait;

    private ?EntityManagerInterface $entityManager = null;

    public function test_it_counts_main_entities_with_simple_to_one_joins(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $result = $this->createProvider()->getData(
            $this->createSimpleJoinDefinition(),
            DatatableRequest::create(pageSize: 10),
        );

        self::assertSame(3, $result->getTotalItems());
        self::assertSame(3, $result->getFilteredItems());
        self::assertCount(3, $result->getRows());
    }

    public function test_it_counts_main_entities_with_custom_join_duplicates(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $result = $this->createProvider()->getData(
            $this->createCustomJoinDefinition(),
            DatatableRequest::create(pageSize: 10),
        );

        self::assertSame(3, $result->getTotalItems());
        self::assertSame(3, $result->getFilteredItems());
    }

    public function test_it_counts_filtered_main_entities_with_custom_join_duplicates(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $result = $this->createProvider()->getData(
            $this->createCustomJoinDefinition(),
            DatatableRequest::create(
                searchQuery: 'created',
                pageSize: 10,
            ),
        );

        self::assertSame(3, $result->getTotalItems());
        self::assertSame(2, $result->getFilteredItems());
        self::assertSame([
            'alice@example.test',
            'bob@example.test',
        ], array_column($result->getRows(), 'e_email'));
    }

    public function test_it_counts_main_entities_with_aggregate_columns(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $result = $this->createProvider()->getData(
            $this->createAggregateDefinition(),
            DatatableRequest::create(pageSize: 10),
        );

        self::assertSame(3, $result->getTotalItems());
        self::assertSame(3, $result->getFilteredItems());
        self::assertCount(3, $result->getRows());
    }

    public function test_it_counts_filtered_main_entities_with_user_filters(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $result = $this->createProvider()->getData(
            $this->createSimpleJoinDefinition(),
            DatatableRequest::create(
                filters: [
                    'organization_name' => 'Acme',
                ],
                pageSize: 10,
            ),
        );

        self::assertSame(3, $result->getTotalItems());
        self::assertSame(1, $result->getFilteredItems());
        self::assertSame(['alice@example.test'], array_column($result->getRows(), 'e_email'));
    }

    public function test_permanent_filters_reduce_the_visible_universe(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $definition = $this->createSimpleJoinDefinition();
        $definition->addPermanentFilter('e.enabled', FilterOperator::Equals, true);

        $result = $this->createProvider()->getData(
            $definition,
            DatatableRequest::create(pageSize: 10),
        );

        self::assertSame(2, $result->getTotalItems());
        self::assertSame(2, $result->getFilteredItems());
        self::assertSame([
            'alice@example.test',
            'charlie@example.test',
        ], array_column($result->getRows(), 'e_email'));
    }

    #[After]
    protected function cleanupDoctrine(): void
    {
        if (!$this->entityManager instanceof EntityManagerInterface) {
            return;
        }

        $entityManager = $this->entityManager;

        $this->dropSchema();
        $entityManager->close();
        $this->entityManager = null;
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    private function createProvider(): DoctrineOrmDataProvider
    {
        $managerRegistry = self::getContainer()->get('doctrine');

        self::assertInstanceOf(\Doctrine\Persistence\ManagerRegistry::class, $managerRegistry);

        return new DoctrineOrmDataProvider($managerRegistry);
    }

    private function createSimpleJoinDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('doctrine-users');

        $definition
            ->setEntityClass(DoctrineUser::class)
            ->addJoin('organization', 'e.organization', JoinType::Left)
            ->addColumn('e.email', label: 'Email')
            ->addColumn('organization.name', label: 'Organization')
            ->addFilter(
                name: 'organization_name',
                field: 'organization.name',
                label: 'Organization',
                type: FilterType::Text,
            )
        ;

        return $definition;
    }

    private function createCustomJoinDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('doctrine-users');

        $definition
            ->setEntityClass(DoctrineUser::class)
            ->addCustomJoin(
                alias: 'audit',
                targetEntityClass: DoctrineAuditLog::class,
                condition: 'audit.objectId = e.id AND audit.className = :audit_class_name',
                type: JoinType::Left,
            )
            ->setOption('customJoinParameters', [
                'audit_class_name' => DoctrineUser::class,
            ])
            ->addColumn('e.email', label: 'Email')
            ->addColumn('audit.eventName', label: 'Audit event')
        ;

        return $definition;
    }

    private function createAggregateDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('doctrine-users');

        $definition
            ->setEntityClass(DoctrineUser::class)
            ->addCustomJoin(
                alias: 'audit',
                targetEntityClass: DoctrineAuditLog::class,
                condition: 'audit.objectId = e.id AND audit.className = :audit_class_name',
                type: JoinType::Left,
            )
            ->setOption('customJoinParameters', [
                'audit_class_name' => DoctrineUser::class,
            ])
            ->addColumn('e.email', label: 'Email')
            ->addAggregateColumn(
                name: 'auditCount',
                field: 'audit.id',
                function: AggregateFunction::Count,
                label: 'Audit count',
            )
        ;

        return $definition;
    }

    private function bootDoctrineAndLoadFixtures(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $this->entityManager = $entityManager;
        $this->recreateSchema();

        $acme = new DoctrineOrganization('Acme Corp', true);
        $beta = new DoctrineOrganization('Beta Ltd', true);
        $gamma = new DoctrineOrganization('Gamma Org', true);

        $entityManager->persist($acme);
        $entityManager->persist($beta);
        $entityManager->persist($gamma);

        $alice = new DoctrineUser(
            email: 'alice@example.test',
            displayName: 'Alice',
            enabled: true,
            organization: $acme,
        );
        $bob = new DoctrineUser(
            email: 'bob@example.test',
            displayName: 'Bob',
            enabled: false,
            organization: $beta,
        );
        $charlie = new DoctrineUser(
            email: 'charlie@example.test',
            displayName: 'Charlie',
            enabled: true,
            organization: $gamma,
        );

        $entityManager->persist($alice);
        $entityManager->persist($bob);
        $entityManager->persist($charlie);
        $entityManager->flush();

        self::assertNotNull($alice->getId());
        self::assertNotNull($bob->getId());

        $entityManager->persist(new DoctrineAuditLog(DoctrineUser::class, $alice->getId(), 'created'));
        $entityManager->persist(new DoctrineAuditLog(DoctrineUser::class, $alice->getId(), 'updated'));
        $entityManager->persist(new DoctrineAuditLog(DoctrineUser::class, $bob->getId(), 'created'));

        $entityManager->flush();
        $entityManager->clear();
    }

    private function recreateSchema(): void
    {
        $this->dropSchema();

        $schemaTool = $this->createSchemaTool();
        $schemaTool->createSchema($this->getMetadata());
    }

    private function dropSchema(): void
    {
        if (!$this->entityManager instanceof EntityManagerInterface) {
            return;
        }

        $schemaTool = $this->createSchemaTool();

        try {
            $schemaTool->dropSchema($this->getMetadata());
        } catch (\Throwable) {
            // The schema may not exist yet.
        }
    }

    private function createSchemaTool(): SchemaTool
    {
        return new SchemaTool($this->getStoredEntityManager());
    }

    private function getStoredEntityManager(): EntityManagerInterface
    {
        if (!$this->entityManager instanceof EntityManagerInterface) {
            throw new \LogicException('The entity manager is not initialized.');
        }

        return $this->entityManager;
    }
}
