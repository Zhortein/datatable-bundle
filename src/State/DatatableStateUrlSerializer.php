<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\State;

use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Exception\InvalidDatatableStateException;

/**
 * Defines the versioned JSON format stored in a namespaced page URL parameter.
 */
final readonly class DatatableStateUrlSerializer
{
    public const string QUERY_PARAMETER = '_zd_state';
    public const int MAX_PAYLOAD_LENGTH = 32768;

    public function createParameterName(
        string $datatableName,
        string $instance,
        ?string $contextToken = null,
    ): string {
        $datatableName = trim($datatableName);
        $instance = trim($instance);

        if ('' === $datatableName || '' === $instance) {
            throw new \InvalidArgumentException('The datatable name and instance must not be empty.');
        }

        $hash = hash('sha256', $datatableName."\0".$instance."\0".($contextToken ?? ''), true);
        $key = rtrim(strtr(base64_encode(substr($hash, 0, 12)), '+/', '-_'), '=');

        return sprintf('%s[%s]', self::QUERY_PARAMETER, $key);
    }

    public function serialize(DatatableState $state): string
    {
        $payload = json_encode(
            $state->toArray(),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        if (self::MAX_PAYLOAD_LENGTH < strlen($payload)) {
            throw new InvalidDatatableStateException('The serialized datatable state is too large for URL transport.');
        }

        return $payload;
    }

    public function deserialize(string $payload): DatatableState
    {
        if ('' === $payload || self::MAX_PAYLOAD_LENGTH < strlen($payload)) {
            throw new InvalidDatatableStateException('The serialized datatable state has an invalid length.');
        }

        try {
            $state = json_decode($payload, true, 16, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new InvalidDatatableStateException('The serialized datatable state is not valid JSON.', previous: $exception);
        }

        if (!is_array($state) || DatatableState::VERSION !== ($state['version'] ?? null)) {
            throw new InvalidDatatableStateException('The serialized datatable state version is unsupported.');
        }

        try {
            return DatatableState::create(
                page: $this->readPositiveInteger($state, 'page', 1),
                pageSize: $this->readPositiveInteger($state, 'pageSize', 25),
                searchQuery: $this->readNullableString($state, 'search'),
                sortField: $this->readNullableString($state, 'sortField'),
                sortDirection: $this->readSortDirection($state),
                filters: $this->readMap($state, 'filters'),
                advancedFilters: $this->readMap($state, 'advancedFilters'),
                visibleColumns: $this->readStringList($state, 'visibleColumns'),
                hiddenColumns: $this->readStringList($state, 'hiddenColumns'),
            );
        } catch (\InvalidArgumentException $exception) {
            throw new InvalidDatatableStateException('The serialized datatable state contains invalid values.', previous: $exception);
        }
    }

    /**
     * @param array<array-key, mixed> $state
     */
    private function readPositiveInteger(array $state, string $name, int $default): int
    {
        $value = $state[$name] ?? $default;

        if (!is_int($value) || $value < 1) {
            throw new InvalidDatatableStateException(sprintf('The datatable state field "%s" must be a positive integer.', $name));
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $state
     */
    private function readNullableString(array $state, string $name): ?string
    {
        $value = $state[$name] ?? null;

        if (null !== $value && !is_string($value)) {
            throw new InvalidDatatableStateException(sprintf('The datatable state field "%s" must be a string or null.', $name));
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $state
     */
    private function readSortDirection(array $state): SortDirection
    {
        $value = $state['sortDirection'] ?? SortDirection::Asc->value;

        if (!is_string($value)) {
            throw new InvalidDatatableStateException('The datatable state sort direction must be a string.');
        }

        return SortDirection::fromString($value);
    }

    /**
     * @param array<array-key, mixed> $state
     *
     * @return array<string, mixed>
     */
    private function readMap(array $state, string $name): array
    {
        $value = $state[$name] ?? [];

        if (!is_array($value)) {
            throw new InvalidDatatableStateException(sprintf('The datatable state field "%s" must be an object.', $name));
        }

        foreach (array_keys($value) as $key) {
            if (!is_string($key)) {
                throw new InvalidDatatableStateException(sprintf('The datatable state field "%s" must use string keys.', $name));
            }
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /**
     * @param array<array-key, mixed> $state
     *
     * @return list<string>
     */
    private function readStringList(array $state, string $name): array
    {
        $value = $state[$name] ?? [];

        if (!is_array($value)) {
            throw new InvalidDatatableStateException(sprintf('The datatable state field "%s" must be a list.', $name));
        }

        $values = [];

        foreach ($value as $item) {
            if (!is_string($item)) {
                throw new InvalidDatatableStateException(sprintf('The datatable state field "%s" must contain only strings.', $name));
            }

            $values[] = $item;
        }

        return $values;
    }
}
