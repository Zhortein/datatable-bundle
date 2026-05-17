<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Definition;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterOperator;
use Zhortein\DatatableBundle\Enum\FilterType;
use Zhortein\DatatableBundle\Filter\Expression\ComparisonOperator;

final class DatatableDefinitionAdvancedFiltersTest extends TestCase
{
    public function test_it_stores_advanced_filter_fields(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addAdvancedFilterField(
                name: 'email',
                field: 'e.email',
                label: 'Email',
                type: FilterType::Text,
                allowedOperators: [FilterOperator::Equals, FilterOperator::Like]
            )
            ->addAdvancedFilterField(
                name: 'status',
                field: 'e.status',
                label: 'Status',
                type: FilterType::Choice,
                choices: ['active' => 'Active', 'inactive' => 'Inactive']
            )
        ;

        $fields = $definition->getAdvancedFilterFields();

        self::assertCount(2, $fields);
        self::assertArrayHasKey('email', $fields);
        self::assertArrayHasKey('status', $fields);

        self::assertSame('email', $fields['email']->getName());
        self::assertSame('e.email', $fields['email']->getField());
        self::assertSame('Email', $fields['email']->getLabel());
        self::assertSame(FilterType::Text, $fields['email']->getType());
        self::assertSame(
            [
                ComparisonOperator::Equals,
                ComparisonOperator::Contains,
                ComparisonOperator::StartsWith,
                ComparisonOperator::EndsWith,
            ],
            $fields['email']->getAllowedOperators(),
        );

        self::assertSame('status', $fields['status']->getName());
        self::assertSame('e.status', $fields['status']->getField());
        self::assertSame('Status', $fields['status']->getLabel());
        self::assertSame(FilterType::Choice, $fields['status']->getType());
        self::assertSame(['active' => 'Active', 'inactive' => 'Inactive'], $fields['status']->getChoices());
    }

    public function test_it_has_sensible_defaults(): void
    {
        $definition = new DatatableDefinition('users');

        $definition->addAdvancedFilterField('id', 'e.id');

        $fields = $definition->getAdvancedFilterFields();
        $field = $fields['id'];

        self::assertSame(FilterType::Text, $field->getType());
        self::assertSame([], $field->getAllowedOperators());
        self::assertSame([], $field->getChoices());
        self::assertNull($field->getLabel());
    }

    public function test_it_accepts_comparison_operators_in_allowed_operators(): void
    {
        $definition = new DatatableDefinition('users');

        $definition->addAdvancedFilterField(
            name: 'email',
            field: 'e.email',
            type: FilterType::Text,
            allowedOperators: [
                ComparisonOperator::Contains,
                ComparisonOperator::StartsWith,
            ],
        );

        $field = $definition->getAdvancedFilterFields()['email'];

        self::assertSame(
            [ComparisonOperator::Contains, ComparisonOperator::StartsWith],
            $field->getAllowedOperators(),
        );
        self::assertSame(['contains', 'starts_with'], $field->getEffectiveOperatorValues());
    }

    public function test_it_accepts_legacy_filter_operators_in_allowed_operators(): void
    {
        $definition = new DatatableDefinition('users');

        $definition->addAdvancedFilterField(
            name: 'enabled',
            field: 'e.enabled',
            type: FilterType::Boolean,
            allowedOperators: [
                FilterOperator::Equals,
                FilterOperator::NotEquals,
            ],
        );

        $field = $definition->getAdvancedFilterFields()['enabled'];

        self::assertSame(
            [ComparisonOperator::Equals, ComparisonOperator::NotEquals],
            $field->getAllowedOperators(),
        );
        self::assertSame(['eq', 'neq'], $field->getEffectiveOperatorValues());
    }

    public function test_it_accepts_mixed_operator_types(): void
    {
        $definition = new DatatableDefinition('users');

        $definition->addAdvancedFilterField(
            name: 'email',
            field: 'e.email',
            type: FilterType::Text,
            allowedOperators: [
                FilterOperator::Equals,
                ComparisonOperator::Contains,
            ],
        );

        $field = $definition->getAdvancedFilterFields()['email'];

        self::assertSame(
            [ComparisonOperator::Equals, ComparisonOperator::Contains],
            $field->getAllowedOperators(),
        );
    }

    public function test_effective_operators_drop_incompatible_developer_allowed_operators(): void
    {
        $definition = new DatatableDefinition('users');

        // Developer mistakenly allowed Contains for a Boolean field.
        $definition->addAdvancedFilterField(
            name: 'enabled',
            field: 'e.enabled',
            type: FilterType::Boolean,
            allowedOperators: [
                ComparisonOperator::Equals,
                ComparisonOperator::Contains,
            ],
            nullable: false,
        );

        $field = $definition->getAdvancedFilterFields()['enabled'];

        self::assertSame(['eq'], $field->getEffectiveOperatorValues());
        self::assertNotContains('contains', $field->getEffectiveOperatorValues());
    }

    public function test_nullable_field_exposes_null_operators(): void
    {
        $definition = new DatatableDefinition('users');

        $definition->addAdvancedFilterField(
            name: 'email',
            field: 'e.email',
            type: FilterType::Text,
            nullable: true,
        );

        $effectiveOperators = $definition->getAdvancedFilterFields()['email']->getEffectiveOperatorValues();

        self::assertContains('is_null', $effectiveOperators);
        self::assertContains('is_not_null', $effectiveOperators);
    }

    public function test_non_nullable_field_does_not_expose_null_operators(): void
    {
        $definition = new DatatableDefinition('users');

        $definition->addAdvancedFilterField(
            name: 'email',
            field: 'e.email',
            type: FilterType::Text,
            nullable: false,
        );

        $effectiveOperators = $definition->getAdvancedFilterFields()['email']->getEffectiveOperatorValues();

        self::assertNotContains('is_null', $effectiveOperators);
        self::assertNotContains('is_not_null', $effectiveOperators);
    }
}
