<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Doctrine;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Doctrine\DoctrineFieldReference;
use Zhortein\DatatableBundle\Doctrine\DoctrineFieldReferenceResolver;

final class DoctrineFieldReferenceTest extends TestCase
{
    public function test_it_stores_field_reference_parts(): void
    {
        $reference = new DoctrineFieldReference('organization', 'name');

        self::assertSame('organization', $reference->getAlias());
        self::assertSame('name', $reference->getField());
        self::assertSame('organization.name', $reference->toString());
        self::assertSame('organization_name', $reference->toResultAlias());
    }

    public function test_it_creates_reference_from_string(): void
    {
        $reference = DoctrineFieldReference::fromString('e.email');

        self::assertSame('e', $reference->getAlias());
        self::assertSame('email', $reference->getField());
    }

    public function test_it_rejects_reference_without_alias(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The Doctrine field reference "email" must contain an alias and a field.');

        DoctrineFieldReference::fromString('email');
    }

    public function test_it_rejects_empty_alias(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The Doctrine field reference alias cannot be empty.');

        new DoctrineFieldReference('', 'email');
    }

    public function test_it_rejects_empty_field(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The Doctrine field reference field cannot be empty.');

        new DoctrineFieldReference('e', '');
    }

    public function test_it_accepts_declared_custom_join_alias(): void
    {
        $definition = new DatatableDefinition('users');
        $definition->addCustomJoin(
            alias: 'audit',
            targetEntityClass: \stdClass::class,
            condition: 'audit.objectId = e.id',
        );

        $reference = new DoctrineFieldReferenceResolver()->normalize('audit.eventName', $definition);

        self::assertSame('audit', $reference->getAlias());
        self::assertSame('eventName', $reference->getField());
        self::assertSame('audit.eventName', $reference->toString());
    }
}
