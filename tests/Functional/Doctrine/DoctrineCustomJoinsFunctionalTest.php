<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\JoinType;
use Zhortein\DatatableBundle\Provider\DoctrineOrmDataProvider;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineAuditLog;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineUser;
use Zhortein\DatatableBundle\Tests\Functional\FunctionalTestCase;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class DoctrineCustomJoinsFunctionalTest extends FunctionalTestCase
{
    use DoctrineSchemaMetadataTrait;

    private ?EntityManagerInterface $entityManager = null;

    public function test_it_selects_field_from_custom_join(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $result = $this->createProvider()->getData(
            $this->createDefinitionWithCustomJoin(),
            DatatableRequest::create(pageSize: 10),
        );

        self::assertSame(2, $result->getTotalItems());
        self::assertSame('alice@example.test', $result->getRows()[0]['e_email']);
        self::assertSame('created', $result->getRows()[0]['audit_eventName']);
        self::assertSame('bob@example.test', $result->getRows()[1]['e_email']);
        self::assertNull($result->getRows()[1]['audit_eventName']);
    }

    public function test_it_searches_field_from_custom_join(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $result = $this->createProvider()->getData(
            $this->createDefinitionWithCustomJoin(),
            DatatableRequest::create(searchQuery: 'created', pageSize: 10),
        );

        self::assertSame(1, $result->getFilteredItems());
        self::assertSame(['alice@example.test'], array_column($result->getRows(), 'e_email'));
    }

    public function test_it_selects_a_field_from_a_custom_join_referencing_an_earlier_custom_alias(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $definition = $this->createDefinitionWithCustomJoin();
        $definition
            ->addCustomJoin(
                alias: 'matchingAudit',
                targetEntityClass: DoctrineAuditLog::class,
                condition: 'matchingAudit.objectId = audit.objectId AND matchingAudit.className = audit.className AND matchingAudit.eventName = audit.eventName',
                type: JoinType::Left,
            )
            ->addColumn('matchingAudit.eventName', label: 'Matching audit event')
        ;

        $result = $this->createProvider()->getData(
            $definition,
            DatatableRequest::create(pageSize: 10),
        );

        self::assertSame(2, $result->getTotalItems());
        self::assertSame('created', $result->getRows()[0]['matchingAudit_eventName']);
        self::assertNull($result->getRows()[1]['matchingAudit_eventName']);
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

    private function createDefinitionWithCustomJoin(): DatatableDefinition
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

        $entityManager->persist(new DoctrineAuditLog(
            className: DoctrineUser::class,
            objectId: $alice->getId(),
            eventName: 'created',
        ));

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
