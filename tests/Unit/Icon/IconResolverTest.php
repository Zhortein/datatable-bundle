<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Icon;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Icon\IconResolver;

final class IconResolverTest extends TestCase
{
    public function test_resolve_default_icons(): void
    {
        $resolver = new IconResolver();

        self::assertSame('bi bi-eye', $resolver->resolve('view'));
        self::assertSame('bi bi-pencil', $resolver->resolve('edit'));
        self::assertSame('bi bi-trash', $resolver->resolve('delete'));
        self::assertSame('bi bi-plus-lg', $resolver->resolve('add'));
        self::assertSame('bi bi-check-lg', $resolver->resolve('check'));
        self::assertSame('bi bi-x-lg', $resolver->resolve('cancel'));
        self::assertSame('bi bi-arrow-down-up', $resolver->resolve('sort'));
        self::assertSame('bi bi-arrow-up', $resolver->resolve('sort_asc'));
        self::assertSame('bi bi-arrow-down', $resolver->resolve('sort_desc'));
        self::assertSame('bi bi-funnel', $resolver->resolve('filter'));
        self::assertSame('bi bi-filetype-csv', $resolver->resolve('export_csv'));
        self::assertSame('bi bi-filetype-xlsx', $resolver->resolve('export_excel'));
    }

    public function test_resolve_unknown_icon_returns_null(): void
    {
        $resolver = new IconResolver();

        self::assertNull($resolver->resolve('unknown'));
    }

    public function test_resolve_overridden_icon(): void
    {
        $resolver = new IconResolver([
            'view' => 'fa fa-eye',
            'custom' => 'fa fa-star',
        ]);

        self::assertSame('fa fa-eye', $resolver->resolve('view'));
        self::assertSame('fa fa-star', $resolver->resolve('custom'));
        self::assertSame('bi bi-pencil', $resolver->resolve('edit'));
    }
}
