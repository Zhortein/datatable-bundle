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
use Zhortein\DatatableBundle\Provider\DoctrineOrmDataProvider;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineUser;
use Zhortein\DatatableBundle\Tests\Functional\FunctionalTestCase;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class DoctrineOrmDataProviderFunctionalTest extends FunctionalTestCase
{
    use DoctrineSchemaMetadataTrait;

    private ?EntityManagerInterface $entityManager = null;

    public function test_it_supports_definition_with_entity_class(): void
    {
        $provider = $this->createProvider();
        $definition = $this->createDefinition();

        self::assertTrue($provider->supports($definition));
    }

    public function test_it_returns_paginated_rows_from_doctrine(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $provider = $this->createProvider();
        $definition = $this->createDefinition();

        $result = $provider->getData(
            $definition,
            DatatableRequest::create(page: 1, pageSize: 2),
        );

        self::assertSame(1, $result->getPage());
        self::assertSame(2, $result->getPageSize());
        self::assertSame(3, $result->getTotalItems());
        self::assertSame(3, $result->getFilteredItems());
        self::assertSame(2, $result->getTotalPages());

        self::assertSame([
            [
                'e_id' => 1,
                'e_email' => 'alice@example.test',
                'e_displayName' => 'Alice',
            ],
            [
                'e_id' => 2,
                'e_email' => 'bob@example.test',
                'e_displayName' => 'Bob',
            ],
        ], $result->getRows());
    }

    public function test_it_returns_second_page(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $provider = $this->createProvider();
        $definition = $this->createDefinition();

        $result = $provider->getData(
            $definition,
            DatatableRequest::create(page: 2, pageSize: 2),
        );

        self::assertSame([
            [
                'e_id' => 3,
                'e_email' => 'charlie@example.test',
                'e_displayName' => 'Charlie',
            ],
        ], $result->getRows());
    }

    public function test_it_rejects_definition_without_entity_class(): void
    {
        $provider = $this->createProvider();
        $definition = new DatatableDefinition('invalid');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The datatable "invalid" must define an entity class to use the Doctrine ORM provider.');

        $provider->getData($definition, new DatatableRequest());
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
            createdAt: new \DateTimeImmutable('2026-01-01 10:00:00'),
        ));
        $entityManager->persist(new DoctrineUser(
            email: 'bob@example.test',
            displayName: 'Bob',
            createdAt: new \DateTimeImmutable('2026-01-02 10:00:00'),
        ));
        $entityManager->persist(new DoctrineUser(
            email: 'charlie@example.test',
            displayName: 'Charlie',
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

    private function getStoredEntityManager(): EntityManagerInterface
    {
        if (!$this->entityManager instanceof EntityManagerInterface) {
            throw new \LogicException('The entity manager is not initialized.');
        }

        return $this->entityManager;
    }
}
