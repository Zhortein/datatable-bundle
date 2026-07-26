<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Context;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Context\DatatableContext;

final class DatatableContextTest extends TestCase
{
    public function test_it_exposes_only_explicit_values(): void
    {
        $context = new DatatableContext([
            'locale' => 'fr',
            'nullable' => null,
        ]);

        self::assertTrue($context->has('locale'));
        self::assertTrue($context->has('nullable'));
        self::assertFalse($context->has('tenant'));
        self::assertSame('fr', $context->get('locale'));
        self::assertNull($context->get('nullable'));
        self::assertSame('fallback', $context->get('tenant', 'fallback'));
        self::assertSame([
            'locale' => 'fr',
            'nullable' => null,
        ], $context->all());
    }

    public function test_with_returns_an_isolated_context(): void
    {
        $frenchContext = new DatatableContext(['locale' => 'fr']);
        $englishContext = $frenchContext->with('locale', 'en');

        self::assertSame('fr', $frenchContext->get('locale'));
        self::assertSame('en', $englishContext->get('locale'));
    }

    public function test_it_rejects_empty_keys(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A datatable context key must be a non-empty string.');

        new DatatableContext([' ' => 'value']);
    }
}
