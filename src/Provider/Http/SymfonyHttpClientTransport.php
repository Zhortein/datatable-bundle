<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Provider\Http;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Zhortein\DatatableBundle\Contract\HttpTransportInterface;
use Zhortein\DatatableBundle\Exception\HttpTransportException;

/**
 * Optional adapter enabled when Symfony HttpClient is installed.
 */
final readonly class SymfonyHttpClientTransport implements HttpTransportInterface
{
    public function __construct(
        private ?HttpClientInterface $httpClient = null,
    ) {
    }

    public function send(HttpTransportRequest $request): HttpTransportResponse
    {
        if (null === $this->httpClient) {
            throw new HttpTransportException('Symfony HttpClient is not available. Install symfony/http-client or provide a custom HttpTransportInterface.');
        }

        $attempt = 0;

        while ($attempt < $request->getMaxAttempts()) {
            ++$attempt;

            if ($request->isCancellationRequested()) {
                throw new HttpTransportException('The remote data provider request was cancelled.');
            }

            try {
                $response = $this->httpClient->request(
                    $request->getMethod(),
                    $request->getUrl(),
                    array_filter([
                        'query' => $request->getQuery(),
                        'headers' => $request->getHeaders(),
                        'json' => $request->getJson(),
                        'timeout' => $request->getTimeout(),
                    ], static fn (mixed $value): bool => null !== $value && [] !== $value),
                );
                $statusCode = $response->getStatusCode();

                if (
                    $attempt < $request->getMaxAttempts()
                    && in_array($statusCode, $request->getRetryStatusCodes(), true)
                ) {
                    $response->cancel();

                    continue;
                }

                if ($request->isCancellationRequested()) {
                    $response->cancel();

                    throw new HttpTransportException('The remote data provider request was cancelled.');
                }

                return new HttpTransportResponse(
                    statusCode: $statusCode,
                    body: $response->getContent(false),
                    headers: $response->getHeaders(false),
                );
            } catch (TransportExceptionInterface) {
                if ($attempt >= $request->getMaxAttempts()) {
                    throw new HttpTransportException('The remote data provider request failed.');
                }
            }
        }

        throw new HttpTransportException('The remote data provider request failed.');
    }
}
