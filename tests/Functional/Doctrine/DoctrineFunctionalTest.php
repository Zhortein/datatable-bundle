<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\After;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineUser;
use Zhortein\DatatableBundle\Tests\Functional\FunctionalTestCase;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

final class DoctrineFunctionalTest extends FunctionalTestCase
{
    private ?EntityManagerInterface $entityManager = null;

    public function test_it_can_persist_and_fetch_test_entity(): void
    {
        self::bootKernel();

        $entityManager = $this->getEntityManager();
        $this->entityManager = $entityManager;

        $this->recreateSchema();

        $user = new DoctrineUser(
            email: 'alice@example.test',
            displayName: 'Alice',
            createdAt: new \DateTimeImmutable('2026-01-01 10:00:00'),
        );

        $entityManager->persist($user);
        $entityManager->flush();
        $entityManager->clear();

        $repository = $entityManager->getRepository(DoctrineUser::class);
        $foundUser = $repository->findOneBy([
            'email' => 'alice@example.test',
        ]);

        self::assertInstanceOf(DoctrineUser::class, $foundUser);
        self::assertSame('Alice', $foundUser->getDisplayName());
        self::assertTrue($foundUser->isEnabled());
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

        $metadata = $entityManager->getClassMetadata(DoctrineUser::class);

        return [$metadata];
    }

    private function getEntityManager(): EntityManagerInterface
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        return $entityManager;
    }

    private function getStoredEntityManager(): EntityManagerInterface
    {
        if (!$this->entityManager instanceof EntityManagerInterface) {
            throw new \LogicException('The entity manager is not initialized.');
        }

        return $this->entityManager;
    }
}
