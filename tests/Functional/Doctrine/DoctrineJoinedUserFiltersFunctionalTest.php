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
use Zhortein\DatatableBundle\Enum\JoinType;
use Zhortein\DatatableBundle\Provider\DoctrineOrmDataProvider;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineOrganization;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineUser;
use Zhortein\DatatableBundle\Tests\Functional\FunctionalTestCase;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class DoctrineJoinedUserFiltersFunctionalTest extends FunctionalTestCase
{
    use DoctrineSchemaMetadataTrait;

    private ?EntityManagerInterface $entityManager = null;

    public function test_it_applies_text_filter_on_joined_field(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $result = $this->createProvider()->getData(
            $this->createDefinitionWithJoinedFilters(),
            DatatableRequest::create(pageSize: 10, filters: [
                'organization_name' => 'acme',
            ]),
        );

        self::assertSame(4, $result->getTotalItems());
        self::assertSame(1, $result->getFilteredItems());
        self::assertSame([
            'alice@example.test',
        ], array_column($result->getRows(), 'e_email'));
    }

    public function test_it_applies_boolean_filter_on_joined_field(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $result = $this->createProvider()->getData(
            $this->createDefinitionWithJoinedFilters(),
            DatatableRequest::create(pageSize: 10, filters: [
                'organization_enabled' => '1',
            ]),
        );

        self::assertSame(4, $result->getTotalItems());
        self::assertSame(2, $result->getFilteredItems());
        self::assertSame([
            'alice@example.test',
            'charlie@example.test',
        ], array_column($result->getRows(), 'e_email'));
    }

    public function test_it_applies_choice_filter_on_joined_field(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $result = $this->createProvider()->getData(
            $this->createDefinitionWithJoinedFilters(),
            DatatableRequest::create(pageSize: 10, filters: [
                'organization_choice' => 'Beta Ltd',
            ]),
        );

        self::assertSame(4, $result->getTotalItems());
        self::assertSame(1, $result->getFilteredItems());
        self::assertSame([
            'bob@example.test',
        ], array_column($result->getRows(), 'e_email'));
    }

    public function test_it_ignores_filter_on_undeclared_join_alias(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $definition = new DatatableDefinition('doctrine-users');

        $definition
            ->setEntityClass(DoctrineUser::class)
            ->addColumn('e.email', label: 'Email')
            ->addFilter(
                name: 'missing_organization_name',
                field: 'missingOrganization.name',
                type: FilterType::Text,
            )
        ;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The Doctrine alias "missingOrganization" is not declared for field "missingOrganization.name".');

        $this->createProvider()->getData(
            $definition,
            DatatableRequest::create(pageSize: 10, filters: [
                'missing_organization_name' => 'Acme',
            ]),
        );
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

    private function createDefinitionWithJoinedFilters(): DatatableDefinition
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
            ->addFilter(
                name: 'organization_enabled',
                field: 'organization.enabled',
                label: 'Organization enabled',
                type: FilterType::Boolean,
            )
            ->addFilter(
                name: 'organization_choice',
                field: 'organization.name',
                label: 'Organization choice',
                type: FilterType::Choice,
                choices: [
                    'Acme Corp' => 'Acme Corp',
                    'Beta Ltd' => 'Beta Ltd',
                    'Gamma Org' => 'Gamma Org',
                ],
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
        $beta = new DoctrineOrganization('Beta Ltd', false);
        $gamma = new DoctrineOrganization('Gamma Org', true);

        $entityManager->persist($acme);
        $entityManager->persist($beta);
        $entityManager->persist($gamma);

        $entityManager->persist(new DoctrineUser(
            email: 'alice@example.test',
            displayName: 'Alice',
            enabled: true,
            organization: $acme,
        ));
        $entityManager->persist(new DoctrineUser(
            email: 'bob@example.test',
            displayName: 'Bob',
            enabled: true,
            organization: $beta,
        ));
        $entityManager->persist(new DoctrineUser(
            email: 'charlie@example.test',
            displayName: 'Charlie',
            enabled: true,
            organization: $gamma,
        ));
        $entityManager->persist(new DoctrineUser(
            email: 'dave@example.test',
            displayName: 'Dave',
            enabled: true,
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
