<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Factory;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterOperator;
use Zhortein\DatatableBundle\Enum\FilterType;
use Zhortein\DatatableBundle\Factory\AdvancedFilterExpressionFactory;
use Zhortein\DatatableBundle\Filter\Expression\ComparisonOperator;
use Zhortein\DatatableBundle\Filter\Expression\Condition;
use Zhortein\DatatableBundle\Filter\Expression\Group;
use Zhortein\DatatableBundle\Filter\Expression\LogicOperator;
use Zhortein\DatatableBundle\Tests\Unit\Factory\Fixtures\StatusEnumFixture;

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

        /** @var Condition $condition */
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
        self::assertInstanceOf(Group::class, $expression->root->children[1]);
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

        /** @var Condition $condition */
        $condition = $expression->root->children[0];
        self::assertSame('name', $condition->field);
    }

    public function test_it_rejects_operators_incompatible_with_field_type(): void
    {
        $definition = new DatatableDefinition('test');
        $definition->addAdvancedFilterField('name', 'e.name', 'Name', FilterType::Text);
        $definition->addAdvancedFilterField('age', 'e.age', 'Age', FilterType::Number);

        $payload = [
            'logic' => 'AND',
            'children' => [
                ['field' => 'name', 'operator' => 'gt', 'value' => 'John'],
                ['field' => 'age', 'operator' => 'contains', 'value' => '5'],
                ['field' => 'age', 'operator' => 'gt', 'value' => 18],
            ],
        ];

        $expression = $this->factory->createFromArray($payload, $definition);

        self::assertNotNull($expression);
        self::assertCount(1, $expression->root->children);

        /** @var Condition $condition */
        $condition = $expression->root->children[0];
        self::assertSame('age', $condition->field);
        self::assertSame(ComparisonOperator::GreaterThan, $condition->operator);
    }

    public function test_it_normalizes_between_with_named_keys(): void
    {
        $definition = new DatatableDefinition('test');
        $definition->addAdvancedFilterField('age', 'e.age', 'Age', FilterType::Number);

        $payload = [
            'logic' => 'AND',
            'children' => [
                ['field' => 'age', 'operator' => 'between', 'value' => ['from' => 18, 'to' => 65]],
            ],
        ];

        $expression = $this->factory->createFromArray($payload, $definition);

        self::assertNotNull($expression);
        /** @var Condition $condition */
        $condition = $expression->root->children[0];
        self::assertSame([18, 65], $condition->value);
    }

    public function test_it_supports_grouped_expressions(): void
    {
        $payload = [
            'logic' => 'OR',
            'children' => [
                [
                    'logic' => 'AND',
                    'children' => [
                        ['field' => 'a', 'operator' => 'eq', 'value' => 'x'],
                        ['field' => 'b', 'operator' => 'contains', 'value' => 'y'],
                    ],
                ],
                ['field' => 'c', 'operator' => 'eq', 'value' => 1],
            ],
        ];

        $expression = $this->factory->createFromArray($payload);

        self::assertNotNull($expression);
        self::assertSame(LogicOperator::Or, $expression->root->logic);
        self::assertCount(2, $expression->root->children);
        self::assertInstanceOf(Group::class, $expression->root->children[0]);
        self::assertSame(LogicOperator::And, $expression->root->children[0]->logic);
    }

    public function test_it_supports_enum_filter_field(): void
    {
        $definition = new DatatableDefinition('test');
        $definition->addAdvancedFilterField(
            name: 'status',
            field: 'e.status',
            type: FilterType::Enum,
            enumClass: StatusEnumFixture::class,
            nullable: true,
        );

        $payload = [
            'logic' => 'AND',
            'children' => [
                ['field' => 'status', 'operator' => 'eq', 'value' => 'active'],
                ['field' => 'status', 'operator' => 'in', 'value' => ['active', 'pending']],
                ['field' => 'status', 'operator' => 'contains', 'value' => 'active'],
                ['field' => 'status', 'operator' => 'is_null'],
            ],
        ];

        $expression = $this->factory->createFromArray($payload, $definition);

        self::assertNotNull($expression);
        self::assertCount(3, $expression->root->children);

        $advancedField = $definition->getAdvancedFilterFields()['status'];
        self::assertSame(StatusEnumFixture::class, $advancedField->getEnumClass());
        self::assertSame(['Active' => 'active', 'Pending' => 'pending', 'Inactive' => 'inactive'], $advancedField->getChoices());
        self::assertContains('eq', $advancedField->getEffectiveOperatorValues());
        self::assertContains('in', $advancedField->getEffectiveOperatorValues());
        self::assertContains('is_null', $advancedField->getEffectiveOperatorValues());
        self::assertNotContains('contains', $advancedField->getEffectiveOperatorValues());
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
