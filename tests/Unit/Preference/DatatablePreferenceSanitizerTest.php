<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Preference;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Preference\DatatablePreferenceSanitizer;
use Zhortein\DatatableBundle\Sorting\SortCriterion;
use Zhortein\DatatableBundle\State\DatatableState;

final class DatatablePreferenceSanitizerTest extends TestCase
{
    public function test_it_only_keeps_supported_columns_sorts_and_explicitly_safe_filters(): void
    {
        $definition = new DatatableDefinition('users');
        $definition
            ->addColumn('id', visible: false)
            ->addColumn('email')
            ->addComputedColumn('display', 'display_resolver')
            ->addFilter('status', 'status', preferenceSafe: true)
            ->addFilter('secret', 'secret')
        ;
        $state = DatatableState::create(
            pageSize: 1000,
            filters: [
                'status' => 'active',
                'secret' => 'classified',
                'unknown' => 'value',
            ],
            visibleColumns: ['email', 'id', 'unknown'],
            hiddenColumns: ['email', 'unknown'],
            sorts: [
                SortCriterion::create('email'),
                SortCriterion::create('display'),
            ],
        );

        $preference = (new DatatablePreferenceSanitizer(100))->sanitize(
            $definition,
            $state,
        );

        self::assertSame(100, $preference->getPageSize());
        self::assertSame(['email'], $preference->getVisibleColumns());
        self::assertSame(['email'], $preference->getHiddenColumns());
        self::assertSame(['email'], array_map(
            static fn (SortCriterion $sort): string => $sort->getField(),
            $preference->getSorts(),
        ));
        self::assertSame(['status' => 'active'], $preference->getFilters());
    }
}
