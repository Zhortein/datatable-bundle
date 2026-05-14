<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Doctrine;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\JoinDefinition;
use Zhortein\DatatableBundle\Doctrine\DoctrineJoinApplier;
use Zhortein\DatatableBundle\Enum\JoinType;

final class DoctrineJoinApplierTest extends TestCase
{
    public function test_it_accepts_valid_join(): void
    {
        $applier = new DoctrineJoinApplier();

        $applier->validate(new JoinDefinition(
            alias: 'organization',
            join: 'e.organization',
            type: JoinType::Left,
        ));

        self::addToAssertionCount(1);
    }

    public function test_it_rejects_reserved_main_alias(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The Doctrine join alias "e" is reserved for the main entity.');

        new DoctrineJoinApplier()->validate(new JoinDefinition(
            alias: 'e',
            join: 'e.organization',
        ));
    }

    public function test_it_rejects_invalid_alias(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The Doctrine join alias "invalid-alias" is invalid.');

        new DoctrineJoinApplier()->validate(new JoinDefinition(
            alias: 'invalid-alias',
            join: 'e.organization',
        ));
    }

    public function test_it_rejects_join_without_association_path(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The Doctrine join expression "organization" must reference an association path.');

        new DoctrineJoinApplier()->validate(new JoinDefinition(
            alias: 'organization',
            join: 'organization',
        ));
    }
}
