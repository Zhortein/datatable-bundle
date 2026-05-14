<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Doctrine\DoctrineFieldMetadataResolver;
use Zhortein\DatatableBundle\Doctrine\DoctrineFieldReference;
use Zhortein\DatatableBundle\Enum\JoinType;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineOrganization;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineUser;
use Zhortein\DatatableBundle\Tests\Functional\FunctionalTestCase;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class DoctrineFieldMetadataResolverFunctionalTest extends FunctionalTestCase
{
    public function test_it_resolves_main_alias_metadata(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $metadata = new DoctrineFieldMetadataResolver()->resolveMetadataForAlias(
            entityManager: $entityManager,
            mainEntityClass: DoctrineUser::class,
            definition: new DatatableDefinition('users'),
            alias: 'e',
        );

        self::assertSame(DoctrineUser::class, $metadata->getName());
    }

    public function test_it_resolves_join_alias_metadata(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $definition = new DatatableDefinition('users');
        $definition->addJoin('organization', 'e.organization', JoinType::Left);

        $metadata = new DoctrineFieldMetadataResolver()->resolveMetadataForAlias(
            entityManager: $entityManager,
            mainEntityClass: DoctrineUser::class,
            definition: $definition,
            alias: 'organization',
        );

        self::assertSame(DoctrineOrganization::class, $metadata->getName());
    }

    public function test_it_checks_field_existence(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $definition = new DatatableDefinition('users');
        $definition->addJoin('organization', 'e.organization', JoinType::Left);

        $resolver = new DoctrineFieldMetadataResolver();

        self::assertTrue($resolver->hasField(
            entityManager: $entityManager,
            mainEntityClass: DoctrineUser::class,
            definition: $definition,
            reference: new DoctrineFieldReference('e', 'email'),
        ));

        self::assertTrue($resolver->hasField(
            entityManager: $entityManager,
            mainEntityClass: DoctrineUser::class,
            definition: $definition,
            reference: new DoctrineFieldReference('organization', 'name'),
        ));

        self::assertFalse($resolver->hasField(
            entityManager: $entityManager,
            mainEntityClass: DoctrineUser::class,
            definition: $definition,
            reference: new DoctrineFieldReference('organization', 'missing'),
        ));
    }

    public function test_it_returns_field_type(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $definition = new DatatableDefinition('users');
        $definition->addJoin('organization', 'e.organization', JoinType::Left);

        $resolver = new DoctrineFieldMetadataResolver();

        self::assertSame('string', $resolver->getTypeOfField(
            entityManager: $entityManager,
            mainEntityClass: DoctrineUser::class,
            definition: $definition,
            reference: new DoctrineFieldReference('e', 'email'),
        ));

        self::assertSame('string', $resolver->getTypeOfField(
            entityManager: $entityManager,
            mainEntityClass: DoctrineUser::class,
            definition: $definition,
            reference: new DoctrineFieldReference('organization', 'name'),
        ));

        self::assertNull($resolver->getTypeOfField(
            entityManager: $entityManager,
            mainEntityClass: DoctrineUser::class,
            definition: $definition,
            reference: new DoctrineFieldReference('organization', 'missing'),
        ));
    }

    public function test_it_rejects_unknown_alias(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The Doctrine alias "organization" is not declared.');

        new DoctrineFieldMetadataResolver()->resolveMetadataForAlias(
            entityManager: $entityManager,
            mainEntityClass: DoctrineUser::class,
            definition: new DatatableDefinition('users'),
            alias: 'organization',
        );
    }

    public function test_it_rejects_unknown_association(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $definition = new DatatableDefinition('users');
        $definition->addJoin('missingAssociation', 'e.missingAssociation', JoinType::Left);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'The Doctrine association "missingAssociation" does not exist on "%s".',
            DoctrineUser::class,
        ));

        new DoctrineFieldMetadataResolver()->resolveMetadataForAlias(
            entityManager: $entityManager,
            mainEntityClass: DoctrineUser::class,
            definition: $definition,
            alias: 'missingAssociation',
        );
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }
}
