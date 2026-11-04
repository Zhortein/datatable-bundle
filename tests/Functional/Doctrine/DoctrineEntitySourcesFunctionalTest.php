<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Provider\DoctrineOrmDataProvider;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineOrganization;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineUser;
use Zhortein\DatatableBundle\Tests\Functional\FunctionalTestCase;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class DoctrineEntitySourcesFunctionalTest extends FunctionalTestCase
{
    use DoctrineSchemaMetadataTrait;

    private ?EntityManagerInterface $entityManager = null;

    public function test_it_attaches_each_row_its_entity_with_the_associations_asked_in_one_query(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $result = $this->createProvider()->getData(
            $this->createDefinition()->setEntitySources(['users']),
            DatatableRequest::create(page: 2, pageSize: 2),
        );

        self::assertQueryExecuted('/FROM doctrine_organizations \w+ LEFT JOIN doctrine_users \w+ .* IN \(/');
        self::assertSame([['e_name' => 'Gamma']], $result->getRows());
        $source = $result->getSource(0);
        self::assertInstanceOf(DoctrineOrganization::class, $source);
        self::assertSame('Gamma', $source->getName());
        $users = $this->getStoredEntityManager()->getClassMetadata(DoctrineOrganization::class)->getFieldValue($source, 'users');
        self::assertInstanceOf(PersistentCollection::class, $users);
        self::assertTrue($users->isInitialized());
        self::assertCount(2, $users);
    }

    public function test_it_attaches_no_source_unless_asked(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $result = $this->createProvider()->getData($this->createDefinition(), DatatableRequest::create());

        self::assertFalse($result->hasSources());
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
        $definition = new DatatableDefinition('doctrine-organizations');

        $definition
            ->setEntityClass(DoctrineOrganization::class)
            ->addColumn('e.name', label: 'Name')
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

        $gamma = new DoctrineOrganization('Gamma');
        foreach ([new DoctrineOrganization('Alpha'), new DoctrineOrganization('Beta'), $gamma] as $organization) {
            $entityManager->persist($organization);
        }
        $entityManager->persist(new DoctrineUser(email: 'alice@example.test', displayName: 'Alice', organization: $gamma));
        $entityManager->persist(new DoctrineUser(email: 'bob@example.test', displayName: 'Bob', organization: $gamma));

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
