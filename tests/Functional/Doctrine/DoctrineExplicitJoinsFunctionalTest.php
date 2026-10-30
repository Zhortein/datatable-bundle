<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterOperator;
use Zhortein\DatatableBundle\Enum\FilterType;
use Zhortein\DatatableBundle\Enum\JoinType;
use Zhortein\DatatableBundle\Filter\Expression\AdvancedFilterExpression;
use Zhortein\DatatableBundle\Filter\Expression\ComparisonOperator;
use Zhortein\DatatableBundle\Filter\Expression\Condition;
use Zhortein\DatatableBundle\Filter\Expression\Group;
use Zhortein\DatatableBundle\Filter\Expression\LogicOperator;
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

    public function test_a_filter_through_a_to_many_join_lists_each_entity_once(): void
    {
        $this->bootDoctrineAndLoadFixtures();
        $entityManager = $this->getStoredEntityManager();
        $organizations = $entityManager->getRepository(DoctrineOrganization::class);
        $enabledOrganization = $organizations->findOneBy(['name' => 'Enabled Org']);
        $entityManager->persist(new DoctrineUser('dave@example.test', 'Dave', organization: $enabledOrganization));
        $entityManager->persist(new DoctrineUser('erin@example.test', 'Erin', organization: $enabledOrganization));
        $entityManager->flush();
        $everyUser = new AdvancedFilterExpression(new Group(LogicOperator::And, [
            new Condition('user', ComparisonOperator::EndsWith, '@example.test'),
        ]));
        $cases = [
            'user filter' => [
                $this->createOrganizationDefinition()->addFilter('user', 'users.email', type: FilterType::Text),
                ['user' => '@example.test'],
                null,
            ],
            'advanced filter' => [
                $this->createOrganizationDefinition()->addAdvancedFilterField('user', 'users.email'),
                [],
                $everyUser,
            ],
            'permanent filter' => [
                $this->createOrganizationDefinition()
                    ->addPermanentFilter('users.email', FilterOperator::Like, '%@example.test'),
                [],
                null,
            ],
        ];

        $distinctSortedOnIdentifier = '/^SELECT DISTINCT .*\b\w+\.id AS \w+ FROM .* ORDER BY \w+\.id ASC/';

        foreach ($cases as $case => [$definition, $filters, $expression]) {
            $provider = $this->createProvider();
            $pageRequest = static fn (int $page): DatatableRequest => DatatableRequest::create(
                filters: $filters,
                advancedFilterExpression: $expression,
                page: $page,
                pageSize: 1,
            );
            $firstPage = $provider->getData($definition, $pageRequest(1));
            self::assertQueryExecuted($distinctSortedOnIdentifier, $case);
            $secondPage = $provider->getData($definition, $pageRequest(2));
            self::assertQueryExecuted($distinctSortedOnIdentifier, $case);

            self::assertSame(2, $firstPage->getTotalItems(), $case);
            self::assertSame(2, $firstPage->getFilteredItems(), $case);
            self::assertSame(['Enabled Org'], array_column($firstPage->getRows(), 'e_name'), $case);
            self::assertSame(['Disabled Org'], array_column($secondPage->getRows(), 'e_name'), $case);
        }
    }

    public function test_a_column_of_a_to_many_join_lists_and_counts_a_row_per_related_entity(): void
    {
        $this->bootDoctrineAndLoadFixtures();
        $entityManager = $this->getStoredEntityManager();
        $organizations = $entityManager->getRepository(DoctrineOrganization::class);
        $enabledOrganization = $organizations->findOneBy(['name' => 'Enabled Org']);
        $entityManager->persist(new DoctrineUser('dave@example.test', 'Dave', organization: $enabledOrganization));
        $entityManager->flush();
        $definition = $this->createOrganizationDefinition()->addColumn('users.email', label: 'Member');

        $result = $this->createProvider()->getData($definition, DatatableRequest::create(pageSize: 10));

        self::assertSame(3, $result->getTotalItems());
        self::assertSame(
            ['alice@example.test', 'dave@example.test', 'bob@example.test'],
            array_column($result->getRows(), 'users_email'),
        );
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

    private function createOrganizationDefinition(): DatatableDefinition
    {
        return new DatatableDefinition('doctrine-organizations')
            ->setEntityClass(DoctrineOrganization::class)
            ->addJoin('users', 'e.users', JoinType::Inner)
            ->addColumn('e.name', label: 'Name')
        ;
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
