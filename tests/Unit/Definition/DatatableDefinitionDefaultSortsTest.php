<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Definition;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Sorting\SortCriterion;

final class DatatableDefinitionDefaultSortsTest extends TestCase
{
    public function test_it_has_no_default_sort_until_one_is_declared(): void
    {
        self::assertSame([], new DatatableDefinition('orders')->getDefaultSorts());
    }

    public function test_it_keeps_the_declared_order_once_per_field(): void
    {
        $definition = new DatatableDefinition('orders')->setDefaultSorts(
            SortCriterion::create('e.status'),
            SortCriterion::create('e.orderDate', SortDirection::Desc),
            SortCriterion::create('e.status', SortDirection::Desc),
        );

        self::assertEquals(
            [SortCriterion::create('e.status'), SortCriterion::create('e.orderDate', SortDirection::Desc)],
            $definition->getDefaultSorts(),
        );
    }
}
