<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Definition;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterType;

final class DatatableDefinitionUserFiltersTest extends TestCase
{
    public function test_it_stores_declared_user_filters(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addFilter(
                name: 'email',
                field: 'e.email',
                label: 'Email',
                type: FilterType::Text,
                placeholder: 'Search an email',
            )
            ->addFilter(
                name: 'status',
                field: 'e.status',
                label: 'Status',
                type: FilterType::Choice,
                choices: [
                    'Enabled' => 'enabled',
                    'Disabled' => 'disabled',
                ],
                required: true,
            )
        ;

        $filters = $definition->getFilters();

        self::assertArrayHasKey('email', $filters);
        self::assertArrayHasKey('status', $filters);

        self::assertSame('email', $filters['email']->getName());
        self::assertSame('e.email', $filters['email']->getField());
        self::assertSame('Email', $filters['email']->getLabel());
        self::assertSame(FilterType::Text, $filters['email']->getType());
        self::assertSame('Search an email', $filters['email']->getPlaceholder());

        self::assertSame('status', $filters['status']->getName());
        self::assertSame(FilterType::Choice, $filters['status']->getType());
        self::assertTrue($filters['status']->isRequired());
        self::assertSame([
            'Enabled' => 'enabled',
            'Disabled' => 'disabled',
        ], $filters['status']->getChoices());
    }

    public function test_filter_name_can_be_replaced_by_declaring_it_again(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addFilter('email', 'e.email', label: 'Email')
            ->addFilter('email', 'e.secondaryEmail', label: 'Secondary email')
        ;

        $filters = $definition->getFilters();

        self::assertCount(1, $filters);
        self::assertSame('e.secondaryEmail', $filters['email']->getField());
        self::assertSame('Secondary email', $filters['email']->getLabel());
    }
}
