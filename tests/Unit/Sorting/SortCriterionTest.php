<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Sorting;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Sorting\SortCriterion;

final class SortCriterionTest extends TestCase
{
    public function test_it_normalizes_field_and_direction(): void
    {
        $criterion = SortCriterion::create(' e.email ', 'DESC');

        self::assertSame('e.email', $criterion->getField());
        self::assertSame(SortDirection::Desc, $criterion->getDirection());
        self::assertSame([
            'field' => 'e.email',
            'direction' => 'desc',
        ], $criterion->toArray());
    }

    public function test_it_deduplicates_a_list_by_field(): void
    {
        $criteria = SortCriterion::normalizeList([
            SortCriterion::create('e.displayName'),
            SortCriterion::create('e.email', SortDirection::Desc),
            SortCriterion::create('e.displayName', SortDirection::Desc),
        ]);

        self::assertSame([
            ['field' => 'e.displayName', 'direction' => 'asc'],
            ['field' => 'e.email', 'direction' => 'desc'],
        ], array_map(
            static fn (SortCriterion $criterion): array => $criterion->toArray(),
            $criteria,
        ));
    }

    public function test_it_rejects_more_than_the_supported_number_of_criteria(): void
    {
        $criteria = [];

        for ($index = 0; $index <= SortCriterion::MAX_CRITERIA; ++$index) {
            $criteria[] = SortCriterion::create(sprintf('field_%d', $index));
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot use more than 8 sort criteria');

        SortCriterion::normalizeList($criteria);
    }

    public function test_it_rejects_an_empty_field(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('field must not be empty');

        SortCriterion::create(' ');
    }

    public function test_it_rejects_a_non_criterion_list_item(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be a SortCriterion instance');

        SortCriterion::normalizeList(['e.email']);
    }
}
