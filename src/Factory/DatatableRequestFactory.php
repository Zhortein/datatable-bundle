<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Factory;

use Symfony\Component\HttpFoundation\Request;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Request\DatatableRequestInputSanitizer;
use Zhortein\DatatableBundle\Sorting\SortCriterion;
use Zhortein\DatatableBundle\State\DatatableState;

final readonly class DatatableRequestFactory
{
    public const int DEFAULT_PAGE = 1;
    public const int DEFAULT_PAGE_SIZE = 25;
    public const int MAX_PAGE = DatatableRequestInputSanitizer::MAX_PAGE;
    public const int MAX_PAGE_SIZE = 500;
    public const int MAX_SEARCH_LENGTH = DatatableRequestInputSanitizer::MAX_SEARCH_LENGTH;
    public const int MAX_FILTERS = DatatableRequestInputSanitizer::MAX_FILTERS;
    public const int MAX_FILTER_VALUES = DatatableRequestInputSanitizer::MAX_FILTER_VALUES;
    public const int MAX_FILTER_VALUE_LENGTH = DatatableRequestInputSanitizer::MAX_FILTER_VALUE_LENGTH;
    public const int MAX_COLUMN_STATE_VALUES = DatatableRequestInputSanitizer::MAX_COLUMN_STATE_VALUES;
    public const int MAX_CURSOR_LENGTH = DatatableRequestInputSanitizer::MAX_CURSOR_LENGTH;
    public const int MAX_TRANSPORT_DEPTH = DatatableRequestInputSanitizer::MAX_TRANSPORT_DEPTH;
    public const int MAX_TRANSPORT_NODES = DatatableRequestInputSanitizer::MAX_TRANSPORT_NODES;

    private DatatableRequestInputSanitizer $inputSanitizer;

    public function __construct(
        private AdvancedFilterExpressionFactory $advancedFilterExpressionFactory,
        private int $defaultPage = self::DEFAULT_PAGE,
        private int $defaultPageSize = self::DEFAULT_PAGE_SIZE,
        private int $maxPageSize = self::MAX_PAGE_SIZE,
        ?DatatableRequestInputSanitizer $inputSanitizer = null,
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

        $this->inputSanitizer = $inputSanitizer ?? new DatatableRequestInputSanitizer();
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
            options: $this->inputSanitizer->clientOptions($parameters),
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
        $sorts = $this->inputSanitizer->sorts($state->getSorts(), $definition);
        $sortField = [] === $sorts
            ? $this->inputSanitizer->sortField($state->getSortField(), $definition)
            : $sorts[0]->getField();

        return DatatableRequest::create(
            page: $this->inputSanitizer->limitPage($state->getPage()),
            pageSize: min($state->getPageSize(), $this->maxPageSize),
            searchQuery: $this->inputSanitizer->limitSearch($state->getSearchQuery()),
            sortField: $sortField,
            sortDirection: $state->getSortDirection(),
            filters: $this->inputSanitizer->filters($state->getFilters(), $definition),
            visibleColumns: $this->inputSanitizer->columnState($state->getVisibleColumns(), $definition),
            hiddenColumns: $this->inputSanitizer->columnState($state->getHiddenColumns(), $definition),
            options: $options,
            advancedFilterExpression: $this->advancedFilterExpressionFactory->createFromArray(
                $state->getAdvancedFilters(),
                $definition,
            ),
            sorts: $sorts,
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
        $advancedFilters = $this->inputSanitizer->advancedFilters($advancedFilters);

        return DatatableState::create(
            page: $this->inputSanitizer->limitPage(
                $this->readPositiveInteger($parameters, 'page', $this->defaultPage),
            ),
            pageSize: $this->readPageSize($parameters),
            searchQuery: $this->inputSanitizer->limitSearch(
                $this->readNullableString($parameters, 'search'),
            ),
            sortField: $this->readNullableString($parameters, 'sortField'),
            sortDirection: $this->readSortDirection($parameters),
            filters: $this->inputSanitizer->filters(
                $this->readArrayParameter($parameters, 'filters'),
            ),
            advancedFilters: $advancedFilters,
            visibleColumns: array_slice(
                $this->readStringListParameter($parameters, 'visibleColumns'),
                0,
                self::MAX_COLUMN_STATE_VALUES,
            ),
            hiddenColumns: array_slice(
                $this->readStringListParameter($parameters, 'hiddenColumns'),
                0,
                self::MAX_COLUMN_STATE_VALUES,
            ),
            sorts: $this->readSortCriteria($parameters),
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
     * @return list<SortCriterion>
     */
    private function readSortCriteria(array $parameters): array
    {
        $value = $parameters['sorts'] ?? null;

        if (!is_array($value)) {
            return [];
        }

        $criteria = [];
        $fields = [];

        foreach ($value as $item) {
            if (SortCriterion::MAX_CRITERIA === count($criteria)) {
                break;
            }

            if (!is_array($item)) {
                continue;
            }

            $field = $item['field'] ?? null;
            $direction = $item['direction'] ?? null;

            if (!is_scalar($field) || !is_scalar($direction)) {
                continue;
            }

            $field = trim((string) $field);

            if ('' === $field || isset($fields[$field])) {
                continue;
            }

            try {
                $criterion = SortCriterion::create($field, (string) $direction);
            } catch (\InvalidArgumentException) {
                continue;
            }

            $criteria[] = $criterion;
            $fields[$field] = true;
        }

        return $criteria;
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
