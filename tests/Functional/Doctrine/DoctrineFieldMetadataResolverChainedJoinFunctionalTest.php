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
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineOrganizationGroup;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineUser;
use Zhortein\DatatableBundle\Tests\Functional\FunctionalTestCase;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class DoctrineFieldMetadataResolverChainedJoinFunctionalTest extends FunctionalTestCase
{
    public function test_it_resolves_chained_join_alias_metadata(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $definition = new DatatableDefinition('users');
        $definition
            ->addJoin('organization', 'e.organization', JoinType::Left)
            ->addJoin('group', 'organization.group', JoinType::Left)
        ;

        $metadata = new DoctrineFieldMetadataResolver()->resolveMetadataForAlias(
            entityManager: $entityManager,
            mainEntityClass: DoctrineUser::class,
            definition: $definition,
            alias: 'group',
        );

        self::assertSame(DoctrineOrganizationGroup::class, $metadata->getName());
    }

    public function test_it_lists_the_aliases_reached_through_a_collection_chained_ones_included(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $definition = new DatatableDefinition('organizations')
            ->addJoin('group', 'e.group', JoinType::Left)
            ->addJoin('member', 'e.users', JoinType::Left)
            ->addJoin('memberOrganization', 'member.organization', JoinType::Left)
        ;

        $aliases = new DoctrineFieldMetadataResolver()->collectionAliases(
            entityManager: $entityManager,
            mainEntityClass: DoctrineOrganization::class,
            definition: $definition,
        );

        self::assertSame(['member', 'memberOrganization'], $aliases);
    }

    public function test_it_checks_chained_join_field_existence(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $definition = new DatatableDefinition('users');
        $definition
            ->addJoin('organization', 'e.organization', JoinType::Left)
            ->addJoin('group', 'organization.group', JoinType::Left)
        ;

        $resolver = new DoctrineFieldMetadataResolver();

        self::assertTrue($resolver->hasField(
            entityManager: $entityManager,
            mainEntityClass: DoctrineUser::class,
            definition: $definition,
            reference: new DoctrineFieldReference('group', 'name'),
        ));

        self::assertSame('string', $resolver->getTypeOfField(
            entityManager: $entityManager,
            mainEntityClass: DoctrineUser::class,
            definition: $definition,
            reference: new DoctrineFieldReference('group', 'name'),
        ));
    }

    public function test_it_rejects_chained_join_when_source_alias_is_missing(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $definition = new DatatableDefinition('users');
        $definition->addJoin('group', 'organization.group', JoinType::Left);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The Doctrine alias "organization" is not declared.');

        new DoctrineFieldMetadataResolver()->resolveMetadataForAlias(
            entityManager: $entityManager,
            mainEntityClass: DoctrineUser::class,
            definition: $definition,
            alias: 'group',
        );
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }
}
