<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterType;
use Zhortein\DatatableBundle\Provider\DoctrineOrmDataProvider;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineOrganization;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineUser;
use Zhortein\DatatableBundle\Tests\Functional\FunctionalTestCase;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class DoctrineUserFiltersFunctionalTest extends FunctionalTestCase
{
    use DoctrineSchemaMetadataTrait;

    private ?EntityManagerInterface $entityManager = null;

    public function test_it_applies_text_filter(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $result = $this->createProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(filters: [
                'email' => 'alice',
            ]),
        );

        self::assertSame(3, $result->getTotalItems());
        self::assertSame(1, $result->getFilteredItems());
        self::assertSame(['alice@example.test'], array_column($result->getRows(), 'e_email'));
    }

    public function test_it_applies_choice_filter(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $result = $this->createProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(filters: [
                'email_choice' => 'bob@example.test',
            ]),
        );

        self::assertSame(1, $result->getFilteredItems());
        self::assertSame(['bob@example.test'], array_column($result->getRows(), 'e_email'));
    }

    public function test_it_applies_boolean_filter(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $result = $this->createProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(filters: [
                'enabled' => '1',
            ]),
        );

        self::assertSame(2, $result->getFilteredItems());
        self::assertSame([
            'alice@example.test',
            'charlie@example.test',
        ], array_column($result->getRows(), 'e_email'));
    }

    public function test_it_ignores_unknown_filter_input(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $result = $this->createProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(filters: [
                'unknown' => 'alice',
            ]),
        );

        self::assertSame(3, $result->getTotalItems());
        self::assertSame(3, $result->getFilteredItems());
        self::assertCount(3, $result->getRows());
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
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.enabled', label: 'Enabled')
            ->addFilter('email', 'e.email', type: FilterType::Text)
            ->addFilter('email_choice', 'e.email', type: FilterType::Choice)
            ->addFilter('enabled', 'e.enabled', type: FilterType::Boolean)
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

        $organization = new DoctrineOrganization('Acme Corp', true);
        $entityManager->persist($organization);

        $entityManager->persist(new DoctrineUser(
            email: 'alice@example.test',
            displayName: 'Alice',
            enabled: true,
            organization: $organization,
        ));
        $entityManager->persist(new DoctrineUser(
            email: 'bob@example.test',
            displayName: 'Bob',
            enabled: false,
            organization: $organization,
        ));
        $entityManager->persist(new DoctrineUser(
            email: 'charlie@example.test',
            displayName: 'Charlie',
            enabled: true,
            organization: $organization,
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
