<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Definition;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\AjaxActionOptions;
use Zhortein\DatatableBundle\Enum\AjaxActionSuccessStrategy;

final class AjaxActionOptionsTest extends TestCase
{
    public function test_it_refreshes_the_table_by_default(): void
    {
        $options = new AjaxActionOptions();

        self::assertSame(AjaxActionSuccessStrategy::RefreshTable, $options->getSuccessStrategy());
    }

    public function test_it_stores_an_explicit_success_strategy(): void
    {
        $options = new AjaxActionOptions(AjaxActionSuccessStrategy::RemoveRow);

        self::assertSame(AjaxActionSuccessStrategy::RemoveRow, $options->getSuccessStrategy());
    }
}
