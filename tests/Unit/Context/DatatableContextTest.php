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
        $frenchContext = new DatatableContext(['locale' => 'fr'], ['locale']);
        $englishContext = $frenchContext->with('locale', 'en');

        self::assertSame('fr', $frenchContext->get('locale'));
        self::assertSame('en', $englishContext->get('locale'));
        self::assertSame(['locale'], $englishContext->getBrowserSafeKeys());
    }

    public function test_it_exposes_only_allowlisted_values_to_the_browser_contract(): void
    {
        $context = new DatatableContext([
            'locale' => 'fr',
            'tenant' => 'acme',
            'user' => new \stdClass(),
        ], ['locale', 'tenant', 'missing', 'locale']);

        self::assertSame(['locale', 'tenant', 'missing'], $context->getBrowserSafeKeys());
        self::assertSame([
            'locale' => 'fr',
            'tenant' => 'acme',
        ], $context->getBrowserSafeValues());
        self::assertArrayHasKey('user', $context->all());
    }

    public function test_it_applies_browser_values_without_changing_server_only_values(): void
    {
        $user = new \stdClass();
        $context = new DatatableContext([
            'locale' => 'en',
            'tenant' => 'acme',
            'user' => $user,
        ], ['locale', 'tenant']);

        $restored = $context->withBrowserValues([
            'locale' => 'fr',
            'tenant' => 'isatis',
        ]);

        self::assertSame('en', $context->get('locale'));
        self::assertSame('fr', $restored->get('locale'));
        self::assertSame('isatis', $restored->get('tenant'));
        self::assertSame($user, $restored->get('user'));
    }

    public function test_it_rejects_a_browser_value_that_is_not_allowlisted(): void
    {
        $context = new DatatableContext(['locale' => 'fr'], ['locale']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The datatable context key "tenant" is not allowlisted for browser propagation.');

        $context->withBrowserValues(['tenant' => 'acme']);
    }

    public function test_it_rejects_empty_keys(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A datatable context key must be a non-empty string.');

        new DatatableContext([' ' => 'value']);
    }
}
