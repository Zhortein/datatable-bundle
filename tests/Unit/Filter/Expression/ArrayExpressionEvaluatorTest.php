<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Filter\Expression;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Filter\Expression\AdvancedFilterExpression;
use Zhortein\DatatableBundle\Filter\Expression\ArrayExpressionEvaluator;
use Zhortein\DatatableBundle\Filter\Expression\ComparisonOperator;
use Zhortein\DatatableBundle\Filter\Expression\Condition;
use Zhortein\DatatableBundle\Filter\Expression\ExpressionInterface;
use Zhortein\DatatableBundle\Filter\Expression\Group;
use Zhortein\DatatableBundle\Filter\Expression\LogicOperator;

final class ArrayExpressionEvaluatorTest extends TestCase
{
    private ArrayExpressionEvaluator $evaluator;

    protected function setUp(): void
    {
        $this->evaluator = new ArrayExpressionEvaluator();
    }

    /**
     * @param array<string, mixed> $row
     */
    #[DataProvider('provideEvaluateData')]
    public function test_evaluate(ExpressionInterface $root, array $row, bool $expected): void
    {
        if ($root instanceof Condition) {
            $root = new Group(children: [$root]);
        }

        /** @var Group $root */
        $expression = new AdvancedFilterExpression($root);

        self::assertSame($expected, $this->evaluator->evaluate($expression, $row));
    }

    /**
     * @return iterable<string, array{0: ExpressionInterface, 1: array<string, mixed>, 2: bool}>
     */
    public static function provideEvaluateData(): iterable
    {
        yield 'equals string' => [
            new Condition('name', ComparisonOperator::Equals, 'John'),
            ['name' => 'John'],
            true,
        ];

        yield 'equals string case insensitive' => [
            new Condition('name', ComparisonOperator::Equals, 'john'),
            ['name' => 'John'],
            true,
        ];

        yield 'not equals string' => [
            new Condition('name', ComparisonOperator::NotEquals, 'John'),
            ['name' => 'Jane'],
            true,
        ];

        yield 'contains' => [
            new Condition('name', ComparisonOperator::Contains, 'oh'),
            ['name' => 'John'],
            true,
        ];

        yield 'not contains' => [
            new Condition('name', ComparisonOperator::NotContains, 'ax'),
            ['name' => 'John'],
            true,
        ];

        yield 'starts with' => [
            new Condition('name', ComparisonOperator::StartsWith, 'Jo'),
            ['name' => 'John'],
            true,
        ];

        yield 'ends with' => [
            new Condition('name', ComparisonOperator::EndsWith, 'hn'),
            ['name' => 'John'],
            true,
        ];

        yield 'greater than numeric' => [
            new Condition('age', ComparisonOperator::GreaterThan, 20),
            ['age' => 25],
            true,
        ];

        yield 'greater than or equals numeric' => [
            new Condition('age', ComparisonOperator::GreaterThanOrEquals, 25),
            ['age' => 25],
            true,
        ];

        yield 'less than numeric' => [
            new Condition('age', ComparisonOperator::LessThan, 30),
            ['age' => 25],
            true,
        ];

        yield 'less than or equals numeric' => [
            new Condition('age', ComparisonOperator::LessThanOrEquals, 25),
            ['age' => 25],
            true,
        ];

        yield 'between numeric' => [
            new Condition('age', ComparisonOperator::Between, [20, 30]),
            ['age' => 25],
            true,
        ];

        yield 'is null' => [
            new Condition('name', ComparisonOperator::IsNull),
            ['name' => null],
            true,
        ];

        yield 'is not null' => [
            new Condition('name', ComparisonOperator::IsNotNull),
            ['name' => 'John'],
            true,
        ];

        yield 'in array' => [
            new Condition('name', ComparisonOperator::In, ['John', 'Jane']),
            ['name' => 'John'],
            true,
        ];

        yield 'not in array' => [
            new Condition('name', ComparisonOperator::NotIn, ['Jane', 'Jack']),
            ['name' => 'John'],
            true,
        ];

        yield 'logic AND success' => [
            new Group(LogicOperator::And, [
                new Condition('name', ComparisonOperator::Equals, 'John'),
                new Condition('age', ComparisonOperator::GreaterThan, 20),
            ]),
            ['name' => 'John', 'age' => 25],
            true,
        ];

        yield 'logic AND failure' => [
            new Group(LogicOperator::And, [
                new Condition('name', ComparisonOperator::Equals, 'John'),
                new Condition('age', ComparisonOperator::GreaterThan, 30),
            ]),
            ['name' => 'John', 'age' => 25],
            false,
        ];

        yield 'logic OR success' => [
            new Group(LogicOperator::Or, [
                new Condition('name', ComparisonOperator::Equals, 'John'),
                new Condition('name', ComparisonOperator::Equals, 'Jane'),
            ]),
            ['name' => 'Jane'],
            true,
        ];

        yield 'nested group' => [
            new Group(LogicOperator::And, [
                new Condition('active', ComparisonOperator::Equals, true),
                new Group(LogicOperator::Or, [
                    new Condition('name', ComparisonOperator::Equals, 'John'),
                    new Condition('name', ComparisonOperator::Equals, 'Jane'),
                ]),
            ]),
            ['active' => true, 'name' => 'Jane'],
            true,
        ];

        yield 'boolean true' => [
            new Condition('active', ComparisonOperator::Equals, true),
            ['active' => 1],
            true,
        ];

        yield 'boolean false' => [
            new Condition('active', ComparisonOperator::Equals, false),
            ['active' => 'no'],
            true,
        ];

        yield 'date equals' => [
            new Condition('created_at', ComparisonOperator::Equals, '2023-05-01'),
            ['created_at' => new \DateTimeImmutable('2023-05-01')],
            true,
        ];

        yield 'date greater than' => [
            new Condition('created_at', ComparisonOperator::GreaterThan, '2023-01-01'),
            ['created_at' => '2023-05-01'],
            true,
        ];
    }
}
