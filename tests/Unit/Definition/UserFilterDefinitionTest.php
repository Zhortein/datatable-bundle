<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Definition;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\UserFilterDefinition;
use Zhortein\DatatableBundle\Enum\FilterType;

final class UserFilterDefinitionTest extends TestCase
{
    public function test_it_stores_filter_metadata(): void
    {
        $filter = new UserFilterDefinition(
            name: 'status',
            field: 'e.status',
            label: 'Status',
            type: FilterType::Choice,
            choices: [
                'Enabled' => 'enabled',
                'Disabled' => 'disabled',
            ],
            placeholder: 'Choose a status',
            required: true,
            options: [
                'foo' => 'bar',
            ],
        );

        self::assertSame('status', $filter->getName());
        self::assertSame('e.status', $filter->getField());
        self::assertSame('Status', $filter->getLabel());
        self::assertSame(FilterType::Choice, $filter->getType());
        self::assertSame([
            'Enabled' => 'enabled',
            'Disabled' => 'disabled',
        ], $filter->getChoices());
        self::assertSame('Choose a status', $filter->getPlaceholder());
        self::assertTrue($filter->isRequired());
        self::assertSame(['foo' => 'bar'], $filter->getOptions());
        self::assertSame('bar', $filter->getOption('foo'));
        self::assertSame('fallback', $filter->getOption('missing', 'fallback'));
    }

    public function test_it_uses_text_filter_by_default(): void
    {
        $filter = new UserFilterDefinition(
            name: 'email',
            field: 'e.email',
        );

        self::assertSame(FilterType::Text, $filter->getType());
        self::assertNull($filter->getLabel());
        self::assertSame([], $filter->getChoices());
        self::assertNull($filter->getPlaceholder());
        self::assertFalse($filter->isRequired());
    }

    public function test_it_rejects_empty_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The datatable filter name cannot be empty.');

        new UserFilterDefinition(
            name: ' ',
            field: 'e.email',
        );
    }

    public function test_it_rejects_empty_field(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The datatable filter field cannot be empty.');

        new UserFilterDefinition(
            name: 'email',
            field: ' ',
        );
    }
}
