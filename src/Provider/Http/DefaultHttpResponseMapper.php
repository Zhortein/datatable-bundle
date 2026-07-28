<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Provider\Http;

use Zhortein\DatatableBundle\Contract\HttpResponseMapperInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Exception\HttpDataProviderException;
use Zhortein\DatatableBundle\Request\DatatableRequest;

final readonly class DefaultHttpResponseMapper implements HttpResponseMapperInterface
{
    public function mapResponse(
        HttpTransportResponse $response,
        DatatableDefinition $definition,
        DatatableRequest $request,
        HttpProviderConfiguration $configuration,
    ): HttpDataPage {
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new HttpDataProviderException(sprintf('The remote data provider returned HTTP status %d.', $response->getStatusCode()));
        }

        $payload = $response->decodeJson();
        $mapping = $configuration->getResponseMapping();
        $rawRows = $this->readPath($payload, $mapping->getRowsPath());

        if (!is_array($rawRows) || !array_is_list($rawRows)) {
            throw new HttpDataProviderException('The mapped remote rows must be a JSON list.');
        }

        $rows = [];
        $identifiers = [];

        foreach ($rawRows as $rawRow) {
            if (!is_array($rawRow)) {
                throw new HttpDataProviderException('Every mapped remote row must be a JSON object.');
            }

            /** @var array<string, mixed> $rawRow */
            $rows[] = $this->mapRow($rawRow, $definition, $configuration);
            $identifier = $this->readOptionalPath($rawRow, $mapping->getIdentifierPath());

            if (null !== $identifier && !is_string($identifier) && !is_int($identifier)) {
                throw new HttpDataProviderException('A mapped remote row identifier must be a string, integer or null.');
            }

            $identifiers[] = $identifier;
        }

        return new HttpDataPage(
            rows: $rows,
            identifiers: $identifiers,
            totalItems: $this->readOptionalCount($payload, $mapping->getTotalItemsPath()),
            filteredItems: $this->readOptionalCount($payload, $mapping->getFilteredItemsPath()),
            nextCursor: $this->readOptionalString($payload, $mapping->getNextCursorPath()),
            previousCursor: $this->readOptionalString($payload, $mapping->getPreviousCursorPath()),
            hasNextPage: $this->readOptionalBool($payload, $mapping->getHasNextPagePath()),
        );
    }

    /**
     * @param array<string, mixed> $rawRow
     *
     * @return array<string, mixed>
     */
    private function mapRow(
        array $rawRow,
        DatatableDefinition $definition,
        HttpProviderConfiguration $configuration,
    ): array {
        $row = [];

        foreach ($definition->getColumns() as $column) {
            $value = $this->readOptionalPath($rawRow, $configuration->mapField($column->getName()));

            if (null !== $value || $this->pathExists($rawRow, $configuration->mapField($column->getName()))) {
                $row[$column->getName()] = $value;
            }
        }

        return [] === $row ? $rawRow : $row;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function readOptionalCount(array $payload, ?string $path): ?int
    {
        $value = $this->readOptionalPath($payload, $path);

        if (null === $value) {
            return null;
        }

        if (!is_int($value) && !(is_string($value) && ctype_digit($value))) {
            throw new HttpDataProviderException(sprintf('The mapped remote count at "%s" must be a non-negative integer.', $path));
        }

        return (int) $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function readOptionalString(array $payload, ?string $path): ?string
    {
        $value = $this->readOptionalPath($payload, $path);

        if (null === $value) {
            return null;
        }

        if (!is_string($value)) {
            throw new HttpDataProviderException(sprintf('The mapped remote cursor at "%s" must be a string or null.', $path));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function readOptionalBool(array $payload, ?string $path): ?bool
    {
        $value = $this->readOptionalPath($payload, $path);

        if (null === $value) {
            return null;
        }

        if (!is_bool($value)) {
            throw new HttpDataProviderException(sprintf('The mapped remote pagination flag at "%s" must be a boolean or null.', $path));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function readOptionalPath(array $payload, ?string $path): mixed
    {
        return null === $path ? null : $this->readPath($payload, $path, false);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function pathExists(array $payload, string $path): bool
    {
        $missing = new \stdClass();

        return $missing !== $this->readPath($payload, $path, false, $missing);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function readPath(
        array $payload,
        string $path,
        bool $required = true,
        mixed $default = null,
    ): mixed {
        $value = $payload;

        foreach (explode('.', $path) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                if (!$required) {
                    return $default;
                }

                throw new HttpDataProviderException(sprintf('The remote response does not contain required path "%s".', $path));
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
