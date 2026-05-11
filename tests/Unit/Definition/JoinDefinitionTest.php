<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Definition;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\JoinDefinition;
use Zhortein\DatatableBundle\Enum\JoinType;

final class JoinDefinitionTest extends TestCase
{
    public function test_it_stores_join_metadata(): void
    {
        $join = new JoinDefinition(
            alias: 'organization',
            join: 'e.organization',
            type: JoinType::Left,
        );

        self::assertSame('organization', $join->getAlias());
        self::assertSame('e.organization', $join->getJoin());
        self::assertSame(JoinType::Left, $join->getType());
    }

    public function test_it_uses_left_join_by_default(): void
    {
        $join = new JoinDefinition(
            alias: 'organization',
            join: 'e.organization',
        );

        self::assertSame(JoinType::Left, $join->getType());
    }

    public function test_it_rejects_empty_alias(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The datatable join alias cannot be empty.');

        new JoinDefinition(
            alias: ' ',
            join: 'e.organization',
        );
    }

    public function test_it_rejects_empty_join_expression(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The datatable join expression cannot be empty.');

        new JoinDefinition(
            alias: 'organization',
            join: ' ',
        );
    }
}
