<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Factory;

use Symfony\Component\HttpFoundation\Request;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\State\DatatableState;

final readonly class DatatableRequestFactory
{
    public const int DEFAULT_PAGE = 1;
    public const int DEFAULT_PAGE_SIZE = 25;
    public const int MAX_PAGE_SIZE = 500;

    public function __construct(
        private AdvancedFilterExpressionFactory $advancedFilterExpressionFactory,
        private int $defaultPage = self::DEFAULT_PAGE,
        private int $defaultPageSize = self::DEFAULT_PAGE_SIZE,
        private int $maxPageSize = self::MAX_PAGE_SIZE,
    ) {
        if ($this->defaultPage < 1) {
            throw new \InvalidArgumentException('The default datatable page must be greater than or equal to 1.');
        }

        if ($this->defaultPageSize < 1) {
            throw new \InvalidArgumentException('The default datatable page size must be greater than or equal to 1.');
        }

        if ($this->maxPageSize < 1) {
            throw new \InvalidArgumentException('The maximum datatable page size must be greater than or equal to 1.');
        }
    }

    public function createFromRequest(Request $request, ?DatatableDefinition $definition = null): DatatableRequest
    {
        $parameters = array_replace(
            $request->query->all(),
            $request->request->all(),
        );

        return $this->createFromState(
            state: $this->createStateFromParameters($parameters),
            definition: $definition,
            options: $this->readArrayParameter($parameters, 'options'),
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createFromState(
        DatatableState $state,
        ?DatatableDefinition $definition = null,
        array $options = [],
    ): DatatableRequest {
        return DatatableRequest::create(
            page: $state->getPage(),
            pageSize: min($state->getPageSize(), $this->maxPageSize),
            searchQuery: $state->getSearchQuery(),
            sortField: $state->getSortField(),
            sortDirection: $state->getSortDirection(),
            filters: $state->getFilters(),
            visibleColumns: $state->getVisibleColumns(),
            hiddenColumns: $state->getHiddenColumns(),
            options: $options,
            advancedFilterExpression: $this->advancedFilterExpressionFactory->createFromArray(
                $state->getAdvancedFilters(),
                $definition,
            ),
        );
    }

    public function createStateFromRequest(Request $request): DatatableState
    {
        return $this->createStateFromParameters(array_replace(
            $request->query->all(),
            $request->request->all(),
        ));
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function createStateFromParameters(array $parameters): DatatableState
    {
        $advancedFilters = $this->readArrayParameter($parameters, 'advancedFilters');
        if ([] === $advancedFilters) {
            $advancedFilters = $this->readArrayParameter($parameters, 'filterExpression');
        }

        return DatatableState::create(
            page: $this->readPositiveInteger($parameters, 'page', $this->defaultPage),
            pageSize: $this->readPageSize($parameters),
            searchQuery: $this->readNullableString($parameters, 'search'),
            sortField: $this->readNullableString($parameters, 'sortField'),
            sortDirection: $this->readSortDirection($parameters),
            filters: $this->readArrayParameter($parameters, 'filters'),
            advancedFilters: $advancedFilters,
            visibleColumns: $this->readStringListParameter($parameters, 'visibleColumns'),
            hiddenColumns: $this->readStringListParameter($parameters, 'hiddenColumns'),
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
        $pageSize = $this->readPositiveInteger($parameters, 'pageSize', $this->defaultPageSize);

        return min($pageSize, $this->maxPageSize);
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
    private function readArrayParameter(array $parameters, string $name): array
    {
        $value = $parameters[$name] ?? [];

        if (!is_array($value)) {
            return [];
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return list<string>
     */
    private function readStringListParameter(array $parameters, string $name): array
    {
        $value = $parameters[$name] ?? [];

        if (is_string($value)) {
            $value = [$value];
        }

        if (!is_array($value)) {
            return [];
        }

        $values = [];

        foreach ($value as $item) {
            if (!is_scalar($item)) {
                continue;
            }

            $item = trim((string) $item);

            if ('' === $item) {
                continue;
            }

            $values[] = $item;
        }

        return array_values(array_unique($values));
    }
}
