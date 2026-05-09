<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Factory;

use Symfony\Component\HttpFoundation\Request;
use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Request\DatatableRequest;

final readonly class DatatableRequestFactory
{
    public const int DEFAULT_PAGE = 1;
    public const int DEFAULT_PAGE_SIZE = 25;
    public const int MAX_PAGE_SIZE = 500;

    public function createFromRequest(Request $request): DatatableRequest
    {
        $parameters = array_replace(
            $request->query->all(),
            $request->request->all(),
        );

        return DatatableRequest::create(
            page: $this->readPositiveInteger($parameters, 'page', self::DEFAULT_PAGE),
            pageSize: $this->readPageSize($parameters),
            searchQuery: $this->readNullableString($parameters, 'search'),
            sortField: $this->readNullableString($parameters, 'sortField'),
            sortDirection: $this->readSortDirection($parameters),
            options: $this->readOptions($parameters),
        );
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function readPositiveInteger(array $parameters, string $name, int $default): int
    {
        $value = $parameters[$name] ?? null;

        if (null === $value || '' === $value) {
            return $default;
        }

        if (!is_numeric($value)) {
            return $default;
        }

        $value = (int) $value;

        return $value > 0 ? $value : $default;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function readPageSize(array $parameters): int
    {
        $pageSize = $this->readPositiveInteger($parameters, 'pageSize', self::DEFAULT_PAGE_SIZE);

        return min($pageSize, self::MAX_PAGE_SIZE);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function readNullableString(array $parameters, string $name): ?string
    {
        $value = $parameters[$name] ?? null;

        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return '' === $value ? null : $value;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function readSortDirection(array $parameters): SortDirection
    {
        $value = $parameters['sortDirection'] ?? null;

        if (!is_scalar($value)) {
            return SortDirection::Asc;
        }

        try {
            return SortDirection::fromString((string) $value);
        } catch (\InvalidArgumentException) {
            return SortDirection::Asc;
        }
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    private function readOptions(array $parameters): array
    {
        $options = $parameters['options'] ?? [];

        if (!is_array($options)) {
            return [];
        }

        /** @var array<string, mixed> $options */
        return $options;
    }
}
