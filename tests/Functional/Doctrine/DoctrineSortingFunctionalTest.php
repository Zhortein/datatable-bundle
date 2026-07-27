<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Provider\DoctrineOrmDataProvider;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Sorting\SortCriterion;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineUser;
use Zhortein\DatatableBundle\Tests\Functional\FunctionalTestCase;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class DoctrineSortingFunctionalTest extends FunctionalTestCase
{
    use DoctrineSchemaMetadataTrait;

    private ?EntityManagerInterface $entityManager = null;

    public function test_it_sorts_by_string_column_ascending(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $result = $this->createProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(sortField: 'e.email', sortDirection: SortDirection::Asc, pageSize: 10),
        );

        self::assertSame([
            'alice@example.test',
            'bob@example.test',
            'charlie@example.test',
        ], array_column($result->getRows(), 'e_email'));
    }

    public function test_it_sorts_by_string_column_descending(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $result = $this->createProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(sortField: 'e.email', sortDirection: SortDirection::Desc, pageSize: 10),
        );

        self::assertSame([
            'charlie@example.test',
            'bob@example.test',
            'alice@example.test',
        ], array_column($result->getRows(), 'e_email'));
    }

    public function test_it_ignores_unknown_sort_field(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $result = $this->createProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(sortField: 'e.unknown', sortDirection: SortDirection::Desc, pageSize: 10),
        );

        self::assertSame(3, $result->getTotalItems());
        self::assertCount(3, $result->getRows());
    }

    public function test_it_ignores_non_sortable_column(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $definition = $this->createDefinitionWithNonSortableEmail();

        $result = $this->createProvider()->getData(
            $definition,
            DatatableRequest::create(sortField: 'e.email', sortDirection: SortDirection::Desc, pageSize: 10),
        );

        self::assertSame(3, $result->getTotalItems());
        self::assertCount(3, $result->getRows());
    }

    public function test_it_combines_search_and_sorting(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $result = $this->createProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(
                searchQuery: 'example.test',
                sortField: 'e.email',
                sortDirection: SortDirection::Desc,
                pageSize: 10,
            ),
        );

        self::assertSame([
            'charlie@example.test',
            'bob@example.test',
            'alice@example.test',
        ], array_column($result->getRows(), 'e_email'));
    }

    public function test_it_sorts_by_multiple_columns_in_declared_priority_order(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $result = $this->createProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(
                pageSize: 10,
                sorts: [
                    SortCriterion::create('e.enabled', SortDirection::Desc),
                    SortCriterion::create('e.email', SortDirection::Desc),
                ],
            ),
        );

        self::assertSame([
            'charlie@example.test',
            'alice@example.test',
            'bob@example.test',
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

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('doctrine-users');

        $definition
            ->setEntityClass(DoctrineUser::class)
            ->addColumn('e.id', visible: false, sortable: false, searchable: true)
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.displayName', label: 'Display name')
            ->addColumn('e.enabled', label: 'Enabled')
        ;

        return $definition;
    }

    private function createDefinitionWithNonSortableEmail(): DatatableDefinition
    {
        $definition = new DatatableDefinition('doctrine-users');

        $definition
            ->setEntityClass(DoctrineUser::class)
            ->addColumn('e.id', visible: false, sortable: false, searchable: true)
            ->addColumn('e.email', label: 'Email', sortable: false)
            ->addColumn('e.displayName', label: 'Display name')
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

        $entityManager->persist(new DoctrineUser(
            email: 'charlie@example.test',
            displayName: 'Charlie',
            enabled: true,
            createdAt: new \DateTimeImmutable('2026-01-03 10:00:00'),
        ));
        $entityManager->persist(new DoctrineUser(
            email: 'alice@example.test',
            displayName: 'Alice',
            enabled: true,
            createdAt: new \DateTimeImmutable('2026-01-01 10:00:00'),
        ));
        $entityManager->persist(new DoctrineUser(
            email: 'bob@example.test',
            displayName: 'Bob',
            enabled: false,
            createdAt: new \DateTimeImmutable('2026-01-02 10:00:00'),
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
        $entityManager = $this->getStoredEntityManager();

        return new SchemaTool($entityManager);
    }

    private function getStoredEntityManager(): EntityManagerInterface
    {
        if (!$this->entityManager instanceof EntityManagerInterface) {
            throw new \LogicException('The entity manager is not initialized.');
        }

        return $this->entityManager;
    }
}
