<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Context\DatatableContextTransport;
use Zhortein\DatatableBundle\Definition\ContextFilterValue;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterOperator;
use Zhortein\DatatableBundle\Provider\DoctrineOrmDataProvider;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineAuditLog;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineOrganization;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineUser;
use Zhortein\DatatableBundle\Tests\Functional\FunctionalTestCase;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class DoctrineLegacyMigrationFunctionalTest extends FunctionalTestCase
{
    use DoctrineSchemaMetadataTrait;

    private ?EntityManagerInterface $entityManager = null;

    public function test_it_scopes_rows_with_non_null_and_allowed_association_filters(): void
    {
        [$alice, $bob, $acme] = $this->bootDoctrineAndLoadFixtures();

        $definition = new DatatableDefinition('scoped-users');
        $definition
            ->setEntityClass(DoctrineUser::class)
            ->addColumn('e.email', label: 'Email')
            ->addPermanentFilter('e.organization', FilterOperator::IsNotNull)
            ->addPermanentFilter('e.organization', FilterOperator::In, [$acme])
        ;

        $result = $this->createProvider()->getData(
            $definition,
            DatatableRequest::create(pageSize: 10),
        );

        self::assertNotNull($alice->getId());
        self::assertNotNull($bob->getId());
        self::assertSame(1, $result->getTotalItems());
        self::assertSame(['alice@example.test'], array_column($result->getRows(), 'e_email'));
    }

    public function test_it_scopes_history_rows_with_signed_context_values(): void
    {
        [$alice] = $this->bootDoctrineAndLoadFixtures();

        self::assertNotNull($alice->getId());

        $declaredContext = new DatatableContext(
            values: [
                'subjectClass' => '',
                'subjectId' => 0,
            ],
            browserSafeKeys: [
                'subjectClass',
                'subjectId',
            ],
        );
        $runtimeContext = $declaredContext->withBrowserValues([
            'subjectClass' => DoctrineUser::class,
            'subjectId' => $alice->getId(),
        ]);
        $transport = new DatatableContextTransport('legacy-migration-test-secret');
        $token = $transport->createRequiredToken(
            datatableName: 'subject-history',
            instance: 'alice-history',
            context: $runtimeContext,
        );

        $definition = new DatatableDefinition('subject-history');
        $definition
            ->setEntityClass(DoctrineAuditLog::class)
            ->setContext($transport->restore(
                token: $token,
                datatableName: 'subject-history',
                instance: 'alice-history',
                context: $declaredContext,
            ))
            ->addColumn('e.eventName', label: 'Event')
            ->addPermanentFilter(
                'e.className',
                FilterOperator::Equals,
                ContextFilterValue::from('subjectClass'),
            )
            ->addPermanentFilter(
                'e.objectId',
                FilterOperator::Equals,
                ContextFilterValue::from('subjectId'),
            )
        ;

        $result = $this->createProvider()->getData(
            $definition,
            DatatableRequest::create(pageSize: 10),
        );

        self::assertSame(1, $result->getTotalItems());
        self::assertSame(['created'], array_column($result->getRows(), 'e_eventName'));
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

    /**
     * @return array{DoctrineUser, DoctrineUser, DoctrineOrganization}
     */
    private function bootDoctrineAndLoadFixtures(): array
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $this->entityManager = $entityManager;
        $this->recreateSchema();

        $acme = new DoctrineOrganization('Acme Corp');
        $beta = new DoctrineOrganization('Beta Ltd');
        $alice = new DoctrineUser(
            email: 'alice@example.test',
            displayName: 'Alice',
            organization: $acme,
        );
        $bob = new DoctrineUser(
            email: 'bob@example.test',
            displayName: 'Bob',
            organization: $beta,
        );
        $unassigned = new DoctrineUser(
            email: 'unassigned@example.test',
            displayName: 'Unassigned',
        );

        $entityManager->persist($acme);
        $entityManager->persist($beta);
        $entityManager->persist($alice);
        $entityManager->persist($bob);
        $entityManager->persist($unassigned);
        $entityManager->flush();

        self::assertNotNull($alice->getId());
        self::assertNotNull($bob->getId());

        $entityManager->persist(new DoctrineAuditLog(
            className: DoctrineUser::class,
            objectId: $alice->getId(),
            eventName: 'created',
        ));
        $entityManager->persist(new DoctrineAuditLog(
            className: DoctrineUser::class,
            objectId: $bob->getId(),
            eventName: 'updated',
        ));
        $entityManager->persist(new DoctrineAuditLog(
            className: DoctrineOrganization::class,
            objectId: $alice->getId(),
            eventName: 'organization-updated',
        ));
        $entityManager->flush();

        return [$alice, $bob, $acme];
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
