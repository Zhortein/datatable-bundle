<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Definition;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\JoinType;

final class DatatableDefinitionJoinsTest extends TestCase
{
    public function test_it_stores_declared_joins(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addJoin('organization', 'e.organization', JoinType::Left)
            ->addJoin('profile', 'e.profile', JoinType::Inner)
        ;

        $joins = $definition->getJoins();

        self::assertArrayHasKey('organization', $joins);
        self::assertArrayHasKey('profile', $joins);

        self::assertSame('organization', $joins['organization']->getAlias());
        self::assertSame('e.organization', $joins['organization']->getJoin());
        self::assertSame(JoinType::Left, $joins['organization']->getType());

        self::assertSame('profile', $joins['profile']->getAlias());
        self::assertSame('e.profile', $joins['profile']->getJoin());
        self::assertSame(JoinType::Inner, $joins['profile']->getType());
    }

    public function test_join_alias_can_be_replaced_by_declaring_it_again(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addJoin('organization', 'e.organization', JoinType::Left)
            ->addJoin('organization', 'e.company', JoinType::Inner)
        ;

        $joins = $definition->getJoins();

        self::assertCount(1, $joins);
        self::assertSame('e.company', $joins['organization']->getJoin());
        self::assertSame(JoinType::Inner, $joins['organization']->getType());
    }
}
