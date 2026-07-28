<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Request;

use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Sorting\SortCriterion;

/**
 * Applies transport boundaries before client-controlled state reaches a data
 * provider. Server-owned options passed directly to the request factory do not
 * pass through this sanitizer.
 */
final readonly class DatatableRequestInputSanitizer
{
    public const int MAX_PAGE = 10000;
    public const int MAX_SEARCH_LENGTH = 512;
    public const int MAX_FILTERS = 50;
    public const int MAX_FILTER_VALUES = 100;
    public const int MAX_FILTER_VALUE_LENGTH = 2048;
    public const int MAX_COLUMN_STATE_VALUES = 250;
    public const int MAX_CURSOR_LENGTH = 2048;
    public const int MAX_TRANSPORT_DEPTH = 8;
    public const int MAX_TRANSPORT_NODES = 500;

    public function limitPage(int $page): int
    {
        return min($page, self::MAX_PAGE);
    }

    public function limitSearch(?string $search): ?string
    {
        if (null === $search || mb_strlen($search) <= self::MAX_SEARCH_LENGTH) {
            return $search;
        }

        return mb_substr($search, 0, self::MAX_SEARCH_LENGTH);
    }

    /**
     * Request options are server-owned. Only the opaque HTTP cursor is accepted
     * from the transport and it is bounded before becoming an internal option.
     *
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    public function clientOptions(array $parameters): array
    {
        $rawOptions = $parameters['options'] ?? [];

        if (!is_array($rawOptions)) {
            $rawOptions = [];
        }

        $cursor = $rawOptions['http_cursor'] ?? $parameters['httpCursor'] ?? null;

        if (!is_scalar($cursor)) {
            return [];
        }

        $cursor = trim((string) $cursor);

        if ('' === $cursor || mb_strlen($cursor) > self::MAX_CURSOR_LENGTH) {
            return [];
        }

        return ['http_cursor' => $cursor];
    }

    /**
     * @param array<array-key, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function filters(array $filters, ?DatatableDefinition $definition = null): array
    {
        if (null !== $definition) {
            $filters = array_intersect_key($filters, $definition->getFilters());
        }

        $bounded = [];

        foreach (array_slice($filters, 0, self::MAX_FILTERS, true) as $name => $value) {
            if (!is_string($name)) {
                continue;
            }

            $value = $this->filterValue($value);

            if (null !== $value) {
                $bounded[$name] = $value;
            }
        }

        return $bounded;
    }

    /**
     * @param array<array-key, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function advancedFilters(array $payload): array
    {
        $valid = true;
        $nodes = 0;
        $bounded = $this->transportValue($payload, 1, $nodes, $valid);

        if (!$valid || !is_array($bounded)) {
            return [];
        }

        /** @var array<string, mixed> $bounded */
        return $bounded;
    }

    /**
     * @param list<SortCriterion> $sorts
     *
     * @return list<SortCriterion>
     */
    public function sorts(array $sorts, ?DatatableDefinition $definition): array
    {
        if (null === $definition) {
            return $sorts;
        }

        $columns = $definition->getColumns();

        return array_values(array_filter(
            $sorts,
            static fn (SortCriterion $criterion): bool => isset($columns[$criterion->getField()])
                && $columns[$criterion->getField()]->isSortable(),
        ));
    }

    public function sortField(?string $field, ?DatatableDefinition $definition): ?string
    {
        if (null === $field || null === $definition) {
            return $field;
        }

        $columns = $definition->getColumns();

        return isset($columns[$field]) && $columns[$field]->isSortable() ? $field : null;
    }

    /**
     * @param list<string> $columns
     *
     * @return list<string>
     */
    public function columnState(array $columns, ?DatatableDefinition $definition): array
    {
        if (null === $definition) {
            return array_slice($columns, 0, self::MAX_COLUMN_STATE_VALUES);
        }

        $declaredColumns = $definition->getColumns();
        $columns = array_values(array_filter(
            $columns,
            static fn (string $column): bool => isset($declaredColumns[$column]),
        ));

        return array_slice($columns, 0, self::MAX_COLUMN_STATE_VALUES);
    }

    private function filterValue(mixed $value): mixed
    {
        if (is_string($value)) {
            return mb_strlen($value) <= self::MAX_FILTER_VALUE_LENGTH ? $value : null;
        }

        if (is_array($value)) {
            if (count($value) > self::MAX_FILTER_VALUES) {
                return null;
            }

            $bounded = [];

            foreach ($value as $key => $item) {
                if (is_array($item)) {
                    return null;
                }

                $item = $this->filterValue($item);

                if (null === $item) {
                    continue;
                }

                if (is_string($key)) {
                    $bounded[$key] = $item;
                } else {
                    $bounded[] = $item;
                }
            }

            return $bounded;
        }

        return is_scalar($value) ? $value : null;
    }

    private function transportValue(
        mixed $value,
        int $depth,
        int &$nodes,
        bool &$valid,
    ): mixed {
        ++$nodes;

        if (
            $depth > self::MAX_TRANSPORT_DEPTH
            || $nodes > self::MAX_TRANSPORT_NODES
        ) {
            $valid = false;

            return null;
        }

        if (is_string($value)) {
            if (mb_strlen($value) > self::MAX_FILTER_VALUE_LENGTH) {
                $valid = false;

                return null;
            }

            return $value;
        }

        if (!is_array($value)) {
            return null === $value || is_scalar($value) ? $value : null;
        }

        if (count($value) > self::MAX_FILTER_VALUES) {
            $valid = false;

            return null;
        }

        $bounded = [];

        foreach ($value as $key => $item) {
            $boundedItem = $this->transportValue($item, $depth + 1, $nodes, $valid);

            if (!$valid) {
                return null;
            }

            if (is_string($key)) {
                $bounded[$key] = $boundedItem;
            } else {
                $bounded[] = $boundedItem;
            }
        }

        return $bounded;
    }
}
