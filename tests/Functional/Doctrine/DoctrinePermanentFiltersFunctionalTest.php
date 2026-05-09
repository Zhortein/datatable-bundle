<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterOperator;
use Zhortein\DatatableBundle\Provider\DoctrineOrmDataProvider;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineUser;
use Zhortein\DatatableBundle\Tests\Functional\FunctionalTestCase;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class DoctrinePermanentFiltersFunctionalTest extends FunctionalTestCase
{
    private ?EntityManagerInterface $entityManager = null;

    public function test_it_applies_equals_filter(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $definition = $this->createDefinition();
        $definition->addPermanentFilter('e.enabled', FilterOperator::Equals, true);

        $result = $this->createProvider()->getData($definition, DatatableRequest::create(pageSize: 10));

        self::assertSame(2, $result->getTotalItems());
        self::assertSame(2, $result->getFilteredItems());
        self::assertSame([
            'alice@example.test',
            'charlie@example.test',
        ], array_column($result->getRows(), 'e_email'));
    }

    public function test_it_applies_in_filter(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $definition = $this->createDefinition();
        $definition->addPermanentFilter('e.email', FilterOperator::In, [
            'bob@example.test',
            'charlie@example.test',
        ]);

        $result = $this->createProvider()->getData($definition, DatatableRequest::create(pageSize: 10));

        self::assertSame(2, $result->getTotalItems());
        self::assertSame([
            'bob@example.test',
            'charlie@example.test',
        ], array_column($result->getRows(), 'e_email'));
    }

    public function test_it_applies_between_filter(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $definition = $this->createDefinition();
        $definition->addPermanentFilter(
            'e.createdAt',
            FilterOperator::Between,
            new \DateTimeImmutable('2026-01-02 00:00:00'),
            new \DateTimeImmutable('2026-01-03 23:59:59'),
        );

        $result = $this->createProvider()->getData($definition, DatatableRequest::create(pageSize: 10));

        self::assertSame(2, $result->getTotalItems());
        self::assertSame([
            'bob@example.test',
            'charlie@example.test',
        ], array_column($result->getRows(), 'e_email'));
    }

    public function test_it_applies_like_filter(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $definition = $this->createDefinition();
        $definition->addPermanentFilter('e.email', FilterOperator::Like, '%alice%');

        $result = $this->createProvider()->getData($definition, DatatableRequest::create(pageSize: 10));

        self::assertSame(1, $result->getTotalItems());
        self::assertSame([
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

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('doctrine-users');

        $definition
            ->setEntityClass(DoctrineUser::class)
            ->addColumn('e.id', visible: false, sortable: false, searchable: false)
            ->addColumn('e.email', label: 'Email')
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
        $entityManager->persist(new DoctrineUser(
            email: 'charlie@example.test',
            displayName: 'Charlie',
            enabled: true,
            createdAt: new \DateTimeImmutable('2026-01-03 10:00:00'),
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

    /**
     * @return list<ClassMetadata<object>>
     */
    private function getMetadata(): array
    {
        $entityManager = $this->getStoredEntityManager();

        return [
            $entityManager->getClassMetadata(DoctrineUser::class),
        ];
    }

    private function getStoredEntityManager(): EntityManagerInterface
    {
        if (!$this->entityManager instanceof EntityManagerInterface) {
            throw new \LogicException('The entity manager is not initialized.');
        }

        return $this->entityManager;
    }
}
