<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Factory;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterOperator;
use Zhortein\DatatableBundle\Enum\FilterType;
use Zhortein\DatatableBundle\Factory\AdvancedFilterExpressionFactory;
use Zhortein\DatatableBundle\Filter\Expression\ComparisonOperator;
use Zhortein\DatatableBundle\Filter\Expression\LogicOperator;

final class AdvancedFilterExpressionFactoryTest extends TestCase
{
    private AdvancedFilterExpressionFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new AdvancedFilterExpressionFactory();
    }

    public function test_it_returns_null_for_empty_payload(): void
    {
        self::assertNull($this->factory->createFromArray([]));
    }

    public function test_it_parses_simple_condition(): void
    {
        $payload = [
            'logic' => 'AND',
            'children' => [
                [
                    'field' => 'name',
                    'operator' => 'eq',
                    'value' => 'John',
                ],
            ],
        ];

        $expression = $this->factory->createFromArray($payload);

        self::assertNotNull($expression);
        self::assertSame(LogicOperator::And, $expression->root->logic);
        self::assertCount(1, $expression->root->children);

        /** @var \Zhortein\DatatableBundle\Filter\Expression\Condition $condition */
        $condition = $expression->root->children[0];
        self::assertSame('name', $condition->field);
        self::assertSame(ComparisonOperator::Equals, $condition->operator);
        self::assertSame('John', $condition->value);
    }

    public function test_it_parses_nested_groups(): void
    {
        $payload = [
            'logic' => 'AND',
            'children' => [
                [
                    'field' => 'name',
                    'operator' => 'contains',
                    'value' => 'John',
                ],
                [
                    'logic' => 'OR',
                    'children' => [
                        [
                            'field' => 'age',
                            'operator' => 'gt',
                            'value' => 20,
                        ],
                        [
                            'field' => 'age',
                            'operator' => 'lt',
                            'value' => 10,
                        ],
                    ],
                ],
            ],
        ];

        $expression = $this->factory->createFromArray($payload);

        self::assertNotNull($expression);
        self::assertCount(2, $expression->root->children);
        self::assertInstanceOf(\Zhortein\DatatableBundle\Filter\Expression\Group::class, $expression->root->children[1]);
        self::assertSame(LogicOperator::Or, $expression->root->children[1]->logic);
        self::assertCount(2, $expression->root->children[1]->children);
    }

    public function test_it_validates_against_definition(): void
    {
        $definition = new DatatableDefinition('test');
        $definition->addAdvancedFilterField('name', 'e.name', 'Name', FilterType::Text, [FilterOperator::Equals]);

        $payload = [
            'logic' => 'AND',
            'children' => [
                [
                    'field' => 'name',
                    'operator' => 'eq',
                    'value' => 'John',
                ],
                [
                    'field' => 'name',
                    'operator' => 'gt', // Not allowed for 'name'
                    'value' => 'John',
                ],
                [
                    'field' => 'email', // Not defined
                    'operator' => 'eq',
                    'value' => 'john@example.com',
                ],
            ],
        ];

        $expression = $this->factory->createFromArray($payload, $definition);

        self::assertNotNull($expression);
        // Only the first condition should be present
        self::assertCount(1, $expression->root->children);

        /** @var \Zhortein\DatatableBundle\Filter\Expression\Condition $condition */
        $condition = $expression->root->children[0];
        self::assertSame('name', $condition->field);
    }

    public function test_it_returns_null_on_max_depth_exceeded(): void
    {
        $payload = [
            'logic' => 'AND',
            'children' => [
                [
                    'logic' => 'AND',
                    'children' => [
                        [
                            'logic' => 'AND',
                            'children' => [
                                [
                                    'logic' => 'AND',
                                    'children' => [
                                        [
                                            'field' => 'too_deep',
                                            'operator' => 'eq',
                                            'value' => 'depth 4',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        self::assertNull($this->factory->createFromArray($payload));
    }
}
