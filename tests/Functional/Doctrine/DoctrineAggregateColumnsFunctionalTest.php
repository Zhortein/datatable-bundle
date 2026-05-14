<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\AggregateFunction;
use Zhortein\DatatableBundle\Enum\JoinType;
use Zhortein\DatatableBundle\Provider\DoctrineOrmDataProvider;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineAuditLog;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineUser;
use Zhortein\DatatableBundle\Tests\Functional\FunctionalTestCase;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class DoctrineAggregateColumnsFunctionalTest extends FunctionalTestCase
{
    use DoctrineSchemaMetadataTrait;

    private ?EntityManagerInterface $entityManager = null;

    public function test_it_returns_count_aggregate_column(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $result = $this->createProvider()->getData(
            $this->createDefinitionWithAggregateColumn(),
            DatatableRequest::create(pageSize: 10),
        );

        self::assertSame(2, $result->getTotalItems());

        $rows = $result->getRows();

        self::assertSame('alice@example.test', $rows[0]['e_email']);
        $this->assertIntegerLikeValue(2, $rows[0], 'auditCount');

        self::assertSame('bob@example.test', $rows[1]['e_email']);
        $this->assertIntegerLikeValue(1, $rows[1], 'auditCount');
    }

    public function test_it_keeps_pagination_with_aggregate_columns(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $result = $this->createProvider()->getData(
            $this->createDefinitionWithAggregateColumn(),
            DatatableRequest::create(page: 1, pageSize: 1),
        );

        self::assertSame(2, $result->getTotalItems());
        self::assertCount(1, $result->getRows());
        self::assertSame('alice@example.test', $result->getRows()[0]['e_email']);
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

        self::assertInstanceOf(ManagerRegistry::class, $managerRegistry);

        return new DoctrineOrmDataProvider($managerRegistry);
    }

    private function createDefinitionWithAggregateColumn(): DatatableDefinition
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

        $alice = new DoctrineUser(email: 'alice@example.test', displayName: 'Alice');
        $bob = new DoctrineUser(email: 'bob@example.test', displayName: 'Bob');

        $entityManager->persist($alice);
        $entityManager->persist($bob);
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

    /**
     * @param array<string, mixed> $row
     */
    private function assertIntegerLikeValue(int $expected, array $row, string $key): void
    {
        self::assertArrayHasKey($key, $row);
        self::assertTrue(is_int($row[$key]) || is_string($row[$key]));

        self::assertSame($expected, (int) $row[$key]);
    }
}
