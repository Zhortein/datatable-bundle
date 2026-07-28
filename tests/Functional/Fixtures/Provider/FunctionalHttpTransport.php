<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Fixtures\Provider;

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Zhortein\DatatableBundle\Contract\HttpTransportInterface;
use Zhortein\DatatableBundle\Provider\Http\HttpTransportRequest;
use Zhortein\DatatableBundle\Provider\Http\HttpTransportResponse;
use Zhortein\DatatableBundle\Provider\Http\SymfonyHttpClientTransport;

final readonly class FunctionalHttpTransport implements HttpTransportInterface
{
    public function send(HttpTransportRequest $request): HttpTransportResponse
    {
        $client = new MockHttpClient(static function (string $method, string $url): MockResponse {
            self::assertRequest($method, $url);

            return new MockResponse(json_encode([
                'items' => [
                    ['id' => 1, 'email' => 'alice@example.test'],
                ],
                'total' => 1,
            ], \JSON_THROW_ON_ERROR));
        });

        return (new SymfonyHttpClientTransport($client))->send($request);
    }

    private static function assertRequest(string $method, string $url): void
    {
        if ('GET' !== $method || 'https://api.example.test/users?page=1&page_size=25&search=alice' !== $url) {
            throw new \LogicException('The functional HTTP provider request was not mapped as expected.');
        }
    }
}
