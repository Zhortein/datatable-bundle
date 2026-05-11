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
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineOrganization;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineUser;
use Zhortein\DatatableBundle\Tests\Functional\FunctionalTestCase;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class DoctrineExplicitJoinsFunctionalTest extends FunctionalTestCase
{
    use DoctrineSchemaMetadataTrait;

    private ?EntityManagerInterface $entityManager = null;

    public function test_left_join_keeps_rows_without_association(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $definition = $this->createDefinitionWithJoin(JoinType::Left);

        $result = $this->createProvider()->getData(
            $definition,
            DatatableRequest::create(pageSize: 10),
        );

        self::assertSame(3, $result->getTotalItems());
        self::assertCount(3, $result->getRows());
    }

    public function test_inner_join_excludes_rows_without_association(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $definition = $this->createDefinitionWithJoin(JoinType::Inner);

        $result = $this->createProvider()->getData(
            $definition,
            DatatableRequest::create(pageSize: 10),
        );

        self::assertSame(2, $result->getTotalItems());
        self::assertCount(2, $result->getRows());
    }

    public function test_it_rejects_columns_using_undeclared_alias(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $definition = new DatatableDefinition('invalid');
        $definition
            ->setEntityClass(DoctrineUser::class)
            ->addColumn('organization.name', label: 'Organization')
        ;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The Doctrine alias "organization" is not declared for field "organization.name".');

        $this->createProvider()->getData($definition, DatatableRequest::create(pageSize: 10));
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

    private function createDefinitionWithJoin(JoinType $joinType): DatatableDefinition
    {
        $definition = new DatatableDefinition('doctrine-users');

        $definition
            ->setEntityClass(DoctrineUser::class)
            ->addJoin('organization', 'e.organization', $joinType)
            ->addColumn('e.id', visible: false, sortable: false, searchable: true)
            ->addColumn('e.email', label: 'Email')
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

        $enabledOrganization = new DoctrineOrganization('Enabled Org', true);
        $disabledOrganization = new DoctrineOrganization('Disabled Org', false);

        $entityManager->persist($enabledOrganization);
        $entityManager->persist($disabledOrganization);

        $entityManager->persist(new DoctrineUser(
            email: 'alice@example.test',
            displayName: 'Alice',
            organization: $enabledOrganization,
        ));
        $entityManager->persist(new DoctrineUser(
            email: 'bob@example.test',
            displayName: 'Bob',
            organization: $disabledOrganization,
        ));
        $entityManager->persist(new DoctrineUser(
            email: 'charlie@example.test',
            displayName: 'Charlie',
            organization: null,
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
