<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Provider\Http;

use Zhortein\DatatableBundle\Contract\HttpRequestCancellationInterface;

final readonly class HttpTransportRequest
{
    /**
     * @param array<string, scalar|array<array-key, mixed>|null> $query
     * @param array<string, string>                              $headers
     * @param array<string, mixed>|null                          $json
     * @param list<int>                                          $retryStatusCodes
     */
    public function __construct(
        private string $method,
        private string $url,
        private array $query = [],
        private array $headers = [],
        private ?array $json = null,
        private float $timeout = 10.0,
        private int $maxAttempts = 1,
        private array $retryStatusCodes = [],
        private ?HttpRequestCancellationInterface $cancellation = null,
    ) {
        if ('' === trim($this->url)) {
            throw new \InvalidArgumentException('An HTTP transport request URL cannot be empty.');
        }

        if ($this->timeout <= 0) {
            throw new \InvalidArgumentException('An HTTP transport request timeout must be greater than zero.');
        }

        if ($this->maxAttempts < 1) {
            throw new \InvalidArgumentException('An HTTP transport request maximum attempts must be greater than or equal to one.');
        }
    }

    public function getMethod(): string
    {
        return strtoupper($this->method);
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return array<string, scalar|array<array-key, mixed>|null>
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getJson(): ?array
    {
        return $this->json;
    }

    public function getTimeout(): float
    {
        return $this->timeout;
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /**
     * @return list<int>
     */
    public function getRetryStatusCodes(): array
    {
        return $this->retryStatusCodes;
    }

    /** @phpstan-impure */
    public function isCancellationRequested(): bool
    {
        return $this->cancellation?->isCancellationRequested() ?? false;
    }
}
