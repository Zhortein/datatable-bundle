<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Provider\Http;

use Zhortein\DatatableBundle\Exception\HttpDataProviderException;

final readonly class HttpTransportResponse
{
    /**
     * @param array<string, list<string>> $headers
     */
    public function __construct(
        private int $statusCode,
        private string $body,
        private array $headers = [],
    ) {
        if ($this->statusCode < 100 || $this->statusCode > 599) {
            throw new \InvalidArgumentException('An HTTP transport response status must be between 100 and 599.');
        }
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return array<string, list<string>>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return array<string, mixed>
     */
    public function decodeJson(): array
    {
        try {
            $payload = json_decode($this->body, true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new HttpDataProviderException('The remote data provider returned malformed JSON.');
        }

        if (!is_array($payload)) {
            throw new HttpDataProviderException('The remote data provider JSON response must be an object.');
        }

        /** @var array<string, mixed> $payload */
        return $payload;
    }
}
