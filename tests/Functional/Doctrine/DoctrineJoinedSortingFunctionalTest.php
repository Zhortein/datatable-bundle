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
use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Provider\DoctrineOrmDataProvider;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Sorting\SortCriterion;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineOrganization;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineUser;
use Zhortein\DatatableBundle\Tests\Functional\FunctionalTestCase;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class DoctrineJoinedSortingFunctionalTest extends FunctionalTestCase
{
    use DoctrineSchemaMetadataTrait;

    private ?EntityManagerInterface $entityManager = null;

    public function test_it_sorts_joined_field_ascending(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $result = $this->createProvider()->getData(
            $this->createDefinitionWithOrganizationJoin(),
            DatatableRequest::create(
                pageSize: 10,
                sortField: 'organization.name',
                sortDirection: SortDirection::Asc,
            ),
        );

        self::assertSame([
            'alice@example.test',
            'charlie@example.test',
            'bob@example.test',
        ], array_column($result->getRows(), 'e_email'));
    }

    public function test_it_sorts_joined_field_descending(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $result = $this->createProvider()->getData(
            $this->createDefinitionWithOrganizationJoin(),
            DatatableRequest::create(
                pageSize: 10,
                sortField: 'organization.name',
                sortDirection: SortDirection::Desc,
            ),
        );

        self::assertSame([
            'bob@example.test',
            'charlie@example.test',
            'alice@example.test',
        ], array_column($result->getRows(), 'e_email'));
    }

    public function test_it_ignores_non_sortable_joined_column(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $definition = $this->createDefinitionWithOrganizationJoin();
        $definition->addColumn('organization.enabled', label: 'Organization enabled', sortable: false);

        $result = $this->createProvider()->getData(
            $definition,
            DatatableRequest::create(
                pageSize: 10,
                sortField: 'organization.enabled',
                sortDirection: SortDirection::Desc,
            ),
        );

        self::assertSame(3, $result->getTotalItems());
        self::assertCount(3, $result->getRows());
    }

    public function test_it_combines_joined_and_main_entity_sort_criteria(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $definition = $this->createDefinitionWithOrganizationJoin();
        $definition->addColumn('organization.enabled', label: 'Organization enabled');

        $result = $this->createProvider()->getData(
            $definition,
            DatatableRequest::create(
                pageSize: 10,
                sorts: [
                    SortCriterion::create('organization.enabled'),
                    SortCriterion::create('e.email', SortDirection::Desc),
                ],
            ),
        );

        self::assertSame([
            'charlie@example.test',
            'bob@example.test',
            'alice@example.test',
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

    private function createDefinitionWithOrganizationJoin(): DatatableDefinition
    {
        $definition = new DatatableDefinition('doctrine-users');

        $definition
            ->setEntityClass(DoctrineUser::class)
            ->addJoin('organization', 'e.organization', JoinType::Left)
            ->addColumn('e.email', label: 'Email')
            ->addColumn('organization.name', label: 'Organization')
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
        $alpha = new DoctrineOrganization('Alpha Org', true);

        $entityManager->persist($acme);
        $entityManager->persist($beta);
        $entityManager->persist($alpha);

        $entityManager->persist(new DoctrineUser(
            email: 'bob@example.test',
            displayName: 'Bob',
            organization: $beta,
        ));
        $entityManager->persist(new DoctrineUser(
            email: 'alice@example.test',
            displayName: 'Alice',
            organization: $acme,
        ));
        $entityManager->persist(new DoctrineUser(
            email: 'charlie@example.test',
            displayName: 'Charlie',
            organization: $alpha,
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
