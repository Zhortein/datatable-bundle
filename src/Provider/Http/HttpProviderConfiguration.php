<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Provider\Http;

use Zhortein\DatatableBundle\Contract\HttpRequestCancellationInterface;
use Zhortein\DatatableBundle\Enum\HttpPaginationStrategy;

/**
 * Server-side configuration for one HTTP-backed datatable.
 */
final readonly class HttpProviderConfiguration
{
    /**
     * @param array<string, string>        $headers
     * @param array<string, string>        $parameterNames
     * @param array<string, string>        $fieldMap local field => remote field
     * @param array<string, string>        $operatorMap local operator => remote operator
     * @param list<string>                 $contextKeys
     * @param list<int>                    $retryStatusCodes
     */
    public function __construct(
        private string $endpoint,
        private HttpProviderCapabilities $capabilities,
        private HttpPaginationStrategy $paginationStrategy = HttpPaginationStrategy::Page,
        private string $method = 'GET',
        private array $headers = [],
        private array $parameterNames = [],
        private array $fieldMap = [],
        private array $operatorMap = [],
        private array $contextKeys = [],
        private HttpResponseMapping $responseMapping = new HttpResponseMapping(),
        private float $timeout = 10.0,
        private int $maxAttempts = 1,
        private array $retryStatusCodes = [429, 502, 503, 504],
        private ?HttpRequestCancellationInterface $cancellation = null,
    ) {
        if ('' === trim($this->endpoint)) {
            throw new \InvalidArgumentException('The HTTP provider endpoint cannot be empty.');
        }

        if (!in_array(strtoupper($this->method), ['GET', 'POST'], true)) {
            throw new \InvalidArgumentException('The default HTTP request mapper supports only GET and POST.');
        }

        if (!$this->capabilities->supportsPagination($this->paginationStrategy)) {
            throw new \InvalidArgumentException(sprintf('The "%s" pagination strategy is not declared by the HTTP provider capabilities.', $this->paginationStrategy->value));
        }

        if ($this->timeout <= 0) {
            throw new \InvalidArgumentException('The HTTP provider timeout must be greater than zero.');
        }

        if ($this->maxAttempts < 1) {
            throw new \InvalidArgumentException('The HTTP provider maximum attempts must be greater than or equal to one.');
        }
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getCapabilities(): HttpProviderCapabilities
    {
        return $this->capabilities;
    }

    public function getPaginationStrategy(): HttpPaginationStrategy
    {
        return $this->paginationStrategy;
    }

    public function getMethod(): string
    {
        return strtoupper($this->method);
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getParameterName(string $name, string $default): string
    {
        return $this->parameterNames[$name] ?? $default;
    }

    public function mapField(string $field): string
    {
        return $this->fieldMap[$field] ?? $field;
    }

    public function mapOperator(string $operator): string
    {
        return $this->operatorMap[$operator] ?? $operator;
    }

    /**
     * @return list<string>
     */
    public function getContextKeys(): array
    {
        return $this->contextKeys;
    }

    public function getResponseMapping(): HttpResponseMapping
    {
        return $this->responseMapping;
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

    public function getCancellation(): ?HttpRequestCancellationInterface
    {
        return $this->cancellation;
    }
}
