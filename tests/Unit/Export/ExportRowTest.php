<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Export;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Export\ExportRow;

final class ExportRowTest extends TestCase
{
    public function test_it_exposes_normalized_values_and_optional_source(): void
    {
        $source = new \stdClass();
        $row = new ExportRow(['id' => 42, 'name' => 'Alice'], $source);

        self::assertSame(['id' => 42, 'name' => 'Alice'], $row->getValues());
        self::assertSame($source, $row->getSource());
    }
}
