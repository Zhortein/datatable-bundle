<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Export;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\ExportFormat;
use Zhortein\DatatableBundle\Export\ExportLimitResolver;

final class ExportLimitResolverTest extends TestCase
{
    public function test_it_uses_global_limit_by_default(): void
    {
        $resolver = new ExportLimitResolver(5000);

        self::assertSame(
            5000,
            $resolver->resolve(new DatatableDefinition('users'), ExportFormat::Csv),
        );
    }

    public function test_format_limit_overrides_global_limit(): void
    {
        $resolver = new ExportLimitResolver(5000, [
            'csv' => 2500,
            'xlsx' => 1000,
        ]);
        $definition = new DatatableDefinition('users');

        self::assertSame(2500, $resolver->resolve($definition, ExportFormat::Csv));
        self::assertSame(1000, $resolver->resolve($definition, ExportFormat::Xlsx));
    }

    public function test_datatable_limit_overrides_configured_limits(): void
    {
        $resolver = new ExportLimitResolver(5000, ['xlsx' => 1000]);
        $definition = new DatatableDefinition('users');
        $definition
            ->setExportLimit(750)
            ->setExportLimit(250, ExportFormat::Xlsx)
        ;

        self::assertSame(750, $resolver->resolve($definition, ExportFormat::Csv));
        self::assertSame(250, $resolver->resolve($definition, ExportFormat::Xlsx));
    }

    public function test_it_rejects_invalid_global_limit(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ExportLimitResolver(0);
    }

    public function test_it_rejects_invalid_format_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ExportLimitResolver(100, ['pdf' => 10]);
    }
}
