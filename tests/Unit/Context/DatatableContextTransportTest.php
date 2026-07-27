<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Context;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Context\DatatableContextTransport;
use Zhortein\DatatableBundle\Exception\InvalidDatatableContextException;

final class DatatableContextTransportTest extends TestCase
{
    private const SECRET = 'unit-test-secret';

    public function test_it_round_trips_only_browser_safe_values(): void
    {
        $transport = new DatatableContextTransport(self::SECRET);
        $serverValue = new \stdClass();
        $context = new DatatableContext([
            'locale' => 'fr',
            'tenant' => 42,
            'enabled' => true,
            'server' => $serverValue,
        ], ['locale', 'tenant', 'enabled']);

        $token = $transport->createToken('articles', 'french-table', $context);

        self::assertNotNull($token);

        $restored = $transport->restore(
            $token,
            'articles',
            'french-table',
            new DatatableContext([
                'locale' => 'en',
                'tenant' => 0,
                'enabled' => false,
                'server' => $serverValue,
            ], ['locale', 'tenant', 'enabled']),
        );

        self::assertSame('fr', $restored->get('locale'));
        self::assertSame(42, $restored->get('tenant'));
        self::assertTrue($restored->get('enabled'));
        self::assertSame($serverValue, $restored->get('server'));
    }

    public function test_it_does_not_create_a_token_without_browser_safe_values(): void
    {
        $transport = new DatatableContextTransport(self::SECRET);

        self::assertNull($transport->createToken(
            'articles',
            'articles',
            new DatatableContext(['server' => new \stdClass()]),
        ));
    }

    public function test_it_rejects_a_tampered_token(): void
    {
        $transport = new DatatableContextTransport(self::SECRET);
        $token = $transport->createToken(
            'articles',
            'articles',
            new DatatableContext(['locale' => 'fr'], ['locale']),
        );

        self::assertNotNull($token);
        [$payload, $signature] = explode('.', $token, 2);
        $tamperedPayload = ('a' === substr($payload, 0, 1) ? 'b' : 'a').substr($payload, 1);

        $this->expectException(InvalidDatatableContextException::class);
        $this->expectExceptionMessage('The datatable context token signature is invalid.');

        $transport->restore(
            $tamperedPayload.'.'.$signature,
            'articles',
            'articles',
            new DatatableContext(['locale' => 'en'], ['locale']),
        );
    }

    public function test_it_binds_a_token_to_the_datatable_and_instance(): void
    {
        $transport = new DatatableContextTransport(self::SECRET);
        $token = $transport->createToken(
            'articles',
            'french-table',
            new DatatableContext(['locale' => 'fr'], ['locale']),
        );

        self::assertNotNull($token);

        $this->expectException(InvalidDatatableContextException::class);
        $this->expectExceptionMessage('The datatable context token does not match this datatable instance.');

        $transport->restore(
            $token,
            'articles',
            'english-table',
            new DatatableContext(['locale' => 'en'], ['locale']),
        );
    }

    public function test_it_rejects_a_key_that_the_rebuilt_definition_does_not_allow(): void
    {
        $transport = new DatatableContextTransport(self::SECRET);
        $token = $transport->createToken(
            'articles',
            'articles',
            new DatatableContext(['tenant' => 'acme'], ['tenant']),
        );

        self::assertNotNull($token);

        $this->expectException(InvalidDatatableContextException::class);
        $this->expectExceptionMessage('The datatable context token contains a forbidden key.');

        $transport->restore(
            $token,
            'articles',
            'articles',
            new DatatableContext(['locale' => 'en'], ['locale']),
        );
    }

    public function test_it_rejects_non_scalar_browser_safe_values(): void
    {
        $transport = new DatatableContextTransport(self::SECRET);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The browser-safe datatable context value "scope" must be scalar');

        $transport->createToken(
            'articles',
            'articles',
            new DatatableContext(['scope' => ['admin']], ['scope']),
        );
    }

    public function test_it_appends_context_to_a_url_without_changing_other_parameters_or_fragments(): void
    {
        $transport = new DatatableContextTransport(self::SECRET);
        $url = $transport->appendToUrl(
            '/custom/export?filters%5Bstatus%5D=active&_zd_instance=old&_zd_context=old#download',
            'signed-token',
            'french-table',
        );

        self::assertSame(
            '/custom/export?filters%5Bstatus%5D=active&_zd_instance=french-table&_zd_context=signed-token#download',
            $url,
        );
    }
}
