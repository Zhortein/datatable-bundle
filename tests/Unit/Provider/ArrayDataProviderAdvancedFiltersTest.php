<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Filter\Expression\AdvancedFilterExpression;
use Zhortein\DatatableBundle\Filter\Expression\ComparisonOperator;
use Zhortein\DatatableBundle\Filter\Expression\Condition;
use Zhortein\DatatableBundle\Filter\Expression\Group;
use Zhortein\DatatableBundle\Filter\Expression\LogicOperator;
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;
use Zhortein\DatatableBundle\Request\DatatableRequest;

final class ArrayDataProviderAdvancedFiltersTest extends TestCase
{
    public function test_it_applies_advanced_filter_expression(): void
    {
        $expression = new AdvancedFilterExpression(
            new Group(LogicOperator::And, [
                new Condition('age', ComparisonOperator::GreaterThan, 23),
                new Condition('name', ComparisonOperator::StartsWith, 'J'),
            ])
        );

        $result = new ArrayDataProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(advancedFilterExpression: $expression),
        );

        self::assertSame(4, $result->getTotalItems());
        self::assertSame(1, $result->getFilteredItems());
        self::assertSame([
            [
                'id' => 3,
                'name' => 'John',
                'age' => 25,
            ],
        ], $result->getRows());
    }

    public function test_it_applies_nested_and_or_groups(): void
    {
        // (age > 20) AND ((name starts_with 'J') OR (name eq 'Alice'))
        $expression = new AdvancedFilterExpression(
            new Group(LogicOperator::And, [
                new Condition('age', ComparisonOperator::GreaterThan, 20),
                new Group(LogicOperator::Or, [
                    new Condition('name', ComparisonOperator::StartsWith, 'J'),
                    new Condition('name', ComparisonOperator::Equals, 'Alice'),
                ]),
            ])
        );

        $result = new ArrayDataProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(advancedFilterExpression: $expression),
        );

        $names = [];
        foreach ($result->getRows() as $row) {
            $name = $row['name'] ?? null;
            if (is_string($name)) {
                $names[] = $name;
            }
        }
        sort($names);

        self::assertSame(['Jane', 'John'], $names);
    }

    public function test_it_combines_with_simple_search(): void
    {
        $expression = new AdvancedFilterExpression(
            new Group(LogicOperator::And, [
                new Condition('age', ComparisonOperator::GreaterThan, 20),
            ])
        );

        $result = new ArrayDataProvider()->getData(
            $this->createDefinition(),
            DatatableRequest::create(
                searchQuery: 'jane',
                advancedFilterExpression: $expression,
            ),
        );

        self::assertSame(1, $result->getFilteredItems());
        self::assertSame([
            [
                'id' => 4,
                'name' => 'Jane',
                'age' => 22,
            ],
        ], $result->getRows());
    }

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('id')
            ->addColumn('name')
            ->addColumn('age')
            ->setOption(ArrayDataProvider::OPTION_ROWS, [
                [
                    'id' => 1,
                    'name' => 'Alice',
                    'age' => 18,
                ],
                [
                    'id' => 2,
                    'name' => 'Bob',
                    'age' => 30,
                ],
                [
                    'id' => 3,
                    'name' => 'John',
                    'age' => 25,
                ],
                [
                    'id' => 4,
                    'name' => 'Jane',
                    'age' => 22,
                ],
            ])
        ;

        return $definition;
    }
}
