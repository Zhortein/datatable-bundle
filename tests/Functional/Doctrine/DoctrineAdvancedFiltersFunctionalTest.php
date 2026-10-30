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
final class DoctrineAdvancedFiltersFunctionalTest extends FunctionalTestCase
{
    use DoctrineSchemaMetadataTrait;

    private ?EntityManagerInterface $entityManager = null;

    public function test_it_applies_simple_condition(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $expression = new AdvancedFilterExpression(
            new Group(LogicOperator::And, [
                new Condition('email', ComparisonOperator::Equals, 'alice@example.test'),
            ])
        );

        $result = $this->createProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(advancedFilterExpression: $expression),
        );

        self::assertSame(3, $result->getTotalItems());
        self::assertSame(1, $result->getFilteredItems());
        self::assertSame(['alice@example.test'], array_column($result->getRows(), 'e_email'));
    }

    public function test_it_applies_and_condition(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $expression = new AdvancedFilterExpression(
            new Group(LogicOperator::And, [
                new Condition('enabled', ComparisonOperator::Equals, true),
                new Condition('displayName', ComparisonOperator::Contains, 'Charlie'),
            ])
        );

        $result = $this->createProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(advancedFilterExpression: $expression),
        );

        self::assertSame(1, $result->getFilteredItems());
        self::assertSame(['charlie@example.test'], array_column($result->getRows(), 'e_email'));
    }

    public function test_it_applies_or_condition(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $expression = new AdvancedFilterExpression(
            new Group(LogicOperator::Or, [
                new Condition('email', ComparisonOperator::Equals, 'alice@example.test'),
                new Condition('email', ComparisonOperator::Equals, 'bob@example.test'),
            ])
        );

        $result = $this->createProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(advancedFilterExpression: $expression),
        );

        self::assertSame(2, $result->getFilteredItems());
        $emails = array_column($result->getRows(), 'e_email');
        sort($emails);
        self::assertSame(['alice@example.test', 'bob@example.test'], $emails);
    }

    public function test_it_applies_nested_conditions(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $expression = new AdvancedFilterExpression(
            new Group(LogicOperator::And, [
                new Condition('enabled', ComparisonOperator::Equals, true),
                new Group(LogicOperator::Or, [
                    new Condition('displayName', ComparisonOperator::Equals, 'Alice'),
                    new Condition('displayName', ComparisonOperator::Equals, 'Charlie'),
                ]),
            ])
        );

        $result = $this->createProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(advancedFilterExpression: $expression),
        );

        self::assertSame(2, $result->getFilteredItems());
    }

    public function test_it_applies_various_operators(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $cases = [
            [ComparisonOperator::NotEquals, 'alice@example.test', 2],
            [ComparisonOperator::StartsWith, 'ali', 1],
            [ComparisonOperator::EndsWith, '.test', 3],
            [ComparisonOperator::Contains, 'example', 3],
            [ComparisonOperator::NotContains, 'alice', 2],
            [ComparisonOperator::In, ['alice@example.test', 'bob@example.test'], 2],
            [ComparisonOperator::NotIn, ['alice@example.test'], 2],
            [ComparisonOperator::IsNull, null, 0],
            [ComparisonOperator::IsNotNull, null, 3],
        ];

        foreach ($cases as [$operator, $value, $expectedCount]) {
            $expression = new AdvancedFilterExpression(
                new Group(LogicOperator::And, [
                    new Condition('email', $operator, $value),
                ])
            );

            $result = $this->createProvider()->getData(
                $this->createDefinition(),
                DatatableRequest::create(advancedFilterExpression: $expression),
            );

            self::assertSame($expectedCount, $result->getFilteredItems(), sprintf('Failed for operator %s', $operator->value));
        }
    }

    public function test_it_applies_between_operator(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $expression = new AdvancedFilterExpression(
            new Group(LogicOperator::And, [
                new Condition('id', ComparisonOperator::Between, [1, 2]),
            ])
        );

        $result = $this->createProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(advancedFilterExpression: $expression),
        );

        self::assertSame(2, $result->getFilteredItems());
    }

    public function test_it_applies_joined_field_condition(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $expression = new AdvancedFilterExpression(
            new Group(LogicOperator::And, [
                new Condition('organization.name', ComparisonOperator::Equals, 'Acme Corp'),
            ])
        );

        $result = $this->createProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(advancedFilterExpression: $expression),
        );

        self::assertSame(3, $result->getFilteredItems());
    }

    public function test_it_reads_the_field_an_advanced_filter_field_declares(): void
    {
        $this->bootDoctrineAndLoadFixtures();
        $definition = $this->createDefinition()
            ->addAdvancedFilterField('mail', 'e.email')
            ->addAdvancedFilterField('company', 'organization.name')
        ;
        $cases = [
            ['mail', ComparisonOperator::Equals, 'alice@example.test', 1],
            ['company', ComparisonOperator::NotEquals, 'Acme Corp', 0],
        ];

        foreach ($cases as [$field, $operator, $value, $expectedCount]) {
            $result = $this->createProvider()->getData(
                $definition,
                DatatableRequest::create(advancedFilterExpression: new AdvancedFilterExpression(
                    new Group(LogicOperator::And, [new Condition($field, $operator, $value)]),
                )),
            );

            self::assertSame($expectedCount, $result->getFilteredItems(), sprintf('Failed for field %s', $field));
        }
    }

    public function test_it_compares_a_date_with_the_whole_day_of_a_date_time_field(): void
    {
        $this->bootDoctrineAndLoadFixtures();
        $definition = $this->createDefinition();
        $definition->addAdvancedFilterField('created', 'e.createdAt', type: FilterType::Date);
        $cases = [
            [ComparisonOperator::Equals, '2026-06-11', ['bob@example.test']],
            [ComparisonOperator::NotEquals, '2026-06-11', ['alice@example.test', 'charlie@example.test']],
            [ComparisonOperator::GreaterThan, '2026-06-10', ['bob@example.test', 'charlie@example.test']],
            [ComparisonOperator::GreaterThanOrEquals, '2026-06-11', ['bob@example.test', 'charlie@example.test']],
            [ComparisonOperator::LessThan, '2026-06-11', ['alice@example.test']],
            [ComparisonOperator::LessThanOrEquals, '2026-06-11', ['alice@example.test', 'bob@example.test']],
            [ComparisonOperator::Between, ['2026-06-10', '2026-06-11'], ['alice@example.test', 'bob@example.test']],
        ];

        foreach ($cases as [$operator, $value, $expectedEmails]) {
            $result = $this->createProvider()->getData(
                $definition,
                DatatableRequest::create(advancedFilterExpression: new AdvancedFilterExpression(
                    new Group(LogicOperator::And, [new Condition('created', $operator, $value)]),
                )),
            );

            $emails = array_column($result->getRows(), 'e_email');
            self::assertSame($expectedEmails, $emails, sprintf('Failed for operator %s', $operator->value));
        }
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
            ->addColumn('e.id', label: 'ID')
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.enabled', label: 'Enabled')
            ->addColumn('e.displayName', label: 'Display Name')
            ->addJoin('organization', 'e.organization')
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

        $organization = new DoctrineOrganization('Acme Corp', true);
        $entityManager->persist($organization);

        $entityManager->persist(new DoctrineUser(
            email: 'alice@example.test',
            displayName: 'Alice',
            enabled: true,
            createdAt: new \DateTimeImmutable('2026-06-10 09:30'),
            organization: $organization,
        ));
        $entityManager->persist(new DoctrineUser(
            email: 'bob@example.test',
            displayName: 'Bob',
            enabled: false,
            createdAt: new \DateTimeImmutable('2026-06-11 18:00'),
            organization: $organization,
        ));
        $entityManager->persist(new DoctrineUser(
            email: 'charlie@example.test',
            displayName: 'Charlie',
            enabled: true,
            createdAt: new \DateTimeImmutable('2026-06-12 08:00'),
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
