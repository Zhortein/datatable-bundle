<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\JoinType;
use Zhortein\DatatableBundle\Provider\DoctrineOrmDataProvider;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineOrganization;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineUser;
use Zhortein\DatatableBundle\Tests\Functional\FunctionalTestCase;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;
use Zhortein\DatatableBundle\Tests\Unit\Renderer\TranslatableRendererTestTrait;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class DoctrineJoinedColumnsFunctionalTest extends FunctionalTestCase
{
    use DoctrineSchemaMetadataTrait;
    use TranslatableRendererTestTrait;

    private ?EntityManagerInterface $entityManager = null;

    public function test_it_returns_joined_entity_column_values(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $result = $this->createProvider()->getData(
            $this->createDefinitionWithOrganizationJoin(),
            DatatableRequest::create(pageSize: 10),
        );

        self::assertSame(3, $result->getTotalItems());
        self::assertSame(3, $result->getFilteredItems());

        $rows = $result->getRows();

        self::assertSame('alice@example.test', $rows[0]['e_email']);
        self::assertSame('Acme Corp', $rows[0]['organization_name']);

        self::assertSame('bob@example.test', $rows[1]['e_email']);
        self::assertSame('Beta Ltd', $rows[1]['organization_name']);

        self::assertSame('charlie@example.test', $rows[2]['e_email']);
        self::assertArrayHasKey('organization_name', $rows[2]);
        self::assertNull($rows[2]['organization_name']);
    }

    public function test_renderer_displays_joined_entity_column_values(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        $definition = $this->createDefinitionWithOrganizationJoin();

        $result = $this->createProvider()->getData(
            $definition,
            DatatableRequest::create(pageSize: 10),
        );

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->renderBody($definition, $result);

        self::assertStringContainsString('alice@example.test', $html);
        self::assertStringContainsString('Acme Corp', $html);
        self::assertStringContainsString('bob@example.test', $html);
        self::assertStringContainsString('Beta Ltd', $html);
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

        $entityManager->persist($acme);
        $entityManager->persist($beta);

        $entityManager->persist(new DoctrineUser(
            email: 'alice@example.test',
            displayName: 'Alice',
            organization: $acme,
        ));
        $entityManager->persist(new DoctrineUser(
            email: 'bob@example.test',
            displayName: 'Bob',
            organization: $beta,
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

    private function createTwigEnvironment(): Environment
    {
        $loader = new FilesystemLoader();
        $loader->addPath(__DIR__.'/../../../templates', 'ZhorteinDatatable');

        $twig = new Environment($loader, [
            'strict_variables' => true,
            'autoescape' => 'html',
        ]);

        $this->addTranslationExtension($twig);

        return $twig;
    }
}
