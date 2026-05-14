<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Doctrine;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Doctrine\DoctrineFieldReferenceResolver;
use Zhortein\DatatableBundle\Enum\JoinType;

final class DoctrineFieldReferenceResolverTest extends TestCase
{
    public function test_it_normalizes_unqualified_field_to_main_alias(): void
    {
        $reference = new DoctrineFieldReferenceResolver()->normalize('email', new DatatableDefinition('users'));

        self::assertSame('e', $reference->getAlias());
        self::assertSame('email', $reference->getField());
        self::assertSame('e.email', $reference->toString());
    }

    public function test_it_keeps_main_alias_field_reference(): void
    {
        $reference = new DoctrineFieldReferenceResolver()->normalize('e.email', new DatatableDefinition('users'));

        self::assertSame('e.email', $reference->toString());
    }

    public function test_it_accepts_declared_join_alias(): void
    {
        $definition = new DatatableDefinition('users');
        $definition->addJoin('organization', 'e.organization', JoinType::Left);

        $reference = new DoctrineFieldReferenceResolver()->normalize('organization.name', $definition);

        self::assertSame('organization', $reference->getAlias());
        self::assertSame('name', $reference->getField());
    }

    public function test_it_rejects_undeclared_join_alias(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The Doctrine alias "organization" is not declared for field "organization.name".');

        new DoctrineFieldReferenceResolver()->normalize('organization.name', new DatatableDefinition('users'));
    }
}
