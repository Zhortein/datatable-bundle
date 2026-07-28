<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Provider\Http;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Zhortein\DatatableBundle\Contract\HttpRequestCancellationInterface;
use Zhortein\DatatableBundle\Exception\HttpTransportException;
use Zhortein\DatatableBundle\Provider\Http\HttpTransportRequest;
use Zhortein\DatatableBundle\Provider\Http\SymfonyHttpClientTransport;

final class SymfonyHttpClientTransportTest extends TestCase
{
    public function test_it_adapts_symfony_http_client_and_retries_declared_statuses(): void
    {
        $client = new MockHttpClient([
            new MockResponse('{"error":"busy"}', ['http_code' => 503]),
            new MockResponse('{"items":[]}', [
                'http_code' => 200,
                'response_headers' => ['content-type: application/json'],
            ]),
        ]);
        $transport = new SymfonyHttpClientTransport($client);

        $response = $transport->send(new HttpTransportRequest(
            method: 'GET',
            url: 'https://api.example.test/users',
            query: ['page' => 1],
            headers: ['X-Tenant' => 'acme'],
            timeout: 2.0,
            maxAttempts: 2,
            retryStatusCodes: [503],
        ));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['items' => []], $response->decodeJson());
        self::assertSame(2, $client->getRequestsCount());
    }

    public function test_it_stops_before_transport_when_cancelled(): void
    {
        $client = new MockHttpClient();
        $transport = new SymfonyHttpClientTransport($client);
        $cancellation = new class implements HttpRequestCancellationInterface {
            public function isCancellationRequested(): bool
            {
                return true;
            }
        };

        try {
            $transport->send(new HttpTransportRequest(
                method: 'GET',
                url: 'https://api.example.test/users',
                cancellation: $cancellation,
            ));
            self::fail('The cancelled request should fail.');
        } catch (HttpTransportException $exception) {
            self::assertSame('The remote data provider request was cancelled.', $exception->getMessage());
        }

        self::assertSame(0, $client->getRequestsCount());
    }

    public function test_it_fails_safely_when_no_symfony_client_is_available(): void
    {
        $this->expectException(HttpTransportException::class);
        $this->expectExceptionMessage('provide a custom HttpTransportInterface');

        (new SymfonyHttpClientTransport())->send(new HttpTransportRequest(
            method: 'GET',
            url: 'https://api.example.test/users',
        ));
    }
}
