<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Filter\Expression;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Filter\Expression\AdvancedFilterExpression;
use Zhortein\DatatableBundle\Filter\Expression\ComparisonOperator;
use Zhortein\DatatableBundle\Filter\Expression\Condition;
use Zhortein\DatatableBundle\Filter\Expression\Group;
use Zhortein\DatatableBundle\Filter\Expression\LogicOperator;

final class AdvancedFilterExpressionTest extends TestCase
{
    public function test_advanced_filter_expression(): void
    {
        $condition = new Condition('email', ComparisonOperator::Contains, 'gmail.com');
        $root = new Group(LogicOperator::And, [$condition]);
        $expression = new AdvancedFilterExpression($root);

        self::assertSame($root, $expression->root);
    }
}
