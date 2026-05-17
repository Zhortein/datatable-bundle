<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Filter\Expression;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Exception\InvalidExpressionException;
use Zhortein\DatatableBundle\Filter\Expression\ComparisonOperator;
use Zhortein\DatatableBundle\Filter\Expression\Condition;
use Zhortein\DatatableBundle\Filter\Expression\Group;
use Zhortein\DatatableBundle\Filter\Expression\LogicOperator;

final class GroupTest extends TestCase
{
    public function test_valid_group(): void
    {
        $condition = new Condition('email', ComparisonOperator::Contains, 'gmail.com');
        $group = new Group(LogicOperator::And, [$condition]);

        self::assertSame(LogicOperator::And, $group->logic);
        self::assertCount(1, $group->children);
        self::assertSame($condition, $group->children[0]);
        self::assertSame(1, $group->getDepth());
    }

    public function test_empty_children_throws_exception(): void
    {
        $this->expectException(InvalidExpressionException::class);
        $this->expectExceptionMessage('Group must have at least one child.');
        new Group(LogicOperator::And, []);
    }

    public function test_max_depth_enforced(): void
    {
        $c = new Condition('f', ComparisonOperator::Equals, 'v');
        $g1 = new Group(LogicOperator::And, [$c]); // depth 1
        $g2 = new Group(LogicOperator::And, [$g1]); // depth 2
        $g3 = new Group(LogicOperator::And, [$g2]); // depth 3

        self::assertSame(3, $g3->getDepth());

        $this->expectException(InvalidExpressionException::class);
        $this->expectExceptionMessage('Expression tree depth exceeds maximum allowed depth of 3.');
        new Group(LogicOperator::And, [$g3]); // depth 4
    }

    public function test_nested_groups_depth(): void
    {
        $c1 = new Condition('f1', ComparisonOperator::Equals, 'v1');
        $c2 = new Condition('f2', ComparisonOperator::Equals, 'v2');

        $g1 = new Group(LogicOperator::Or, [$c1, $c2]); // depth 1
        $g2 = new Group(LogicOperator::And, [$g1, $c1]); // depth 2

        self::assertSame(2, $g2->getDepth());
    }
}
