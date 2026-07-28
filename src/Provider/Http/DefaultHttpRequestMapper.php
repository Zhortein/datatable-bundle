<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Provider\Http;

use Zhortein\DatatableBundle\Contract\HttpRequestMapperInterface;
use Zhortein\DatatableBundle\Definition\ContextFilterValue;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterOperator;
use Zhortein\DatatableBundle\Exception\HttpDataProviderException;
use Zhortein\DatatableBundle\Filter\Expression\Condition;
use Zhortein\DatatableBundle\Filter\Expression\ExpressionInterface;
use Zhortein\DatatableBundle\Filter\Expression\Group;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Sorting\SortCriterion;

final readonly class DefaultHttpRequestMapper implements HttpRequestMapperInterface
{
    public function mapRequest(
        DatatableDefinition $definition,
        DatatableRequest $request,
        HttpProviderConfiguration $configuration,
    ): HttpTransportRequest {
        $capabilities = $configuration->getCapabilities();

        if ($request->hasSearchQuery() && !$capabilities->supportsSearch()) {
            throw $this->unsupported('search');
        }

        if ($request->hasSort() && !$capabilities->supportsSorting()) {
            throw $this->unsupported('sorting');
        }

        if ($request->hasFilters() && !$capabilities->supportsSimpleFilters()) {
            throw $this->unsupported('simple filters');
        }

        if ($request->hasAdvancedFilters() && !$capabilities->supportsAdvancedFilters()) {
            throw $this->unsupported('advanced filters');
        }

        $payload = $this->paginationPayload($request, $configuration);

        if ($request->hasSearchQuery()) {
            $payload[$configuration->getParameterName('search', 'search')] = $request->getSearchQuery();
        }

        if ($request->hasSort()) {
            $payload[$configuration->getParameterName('sort', 'sort')] = array_map(
                static fn (SortCriterion $criterion): array => [
                    'field' => $configuration->mapField($criterion->getField()),
                    'direction' => $criterion->getDirection()->value,
                ],
                $request->getSorts(),
            );
        }

        $filters = $this->mapFilters($definition, $request, $configuration);

        if ([] !== $filters) {
            $payload[$configuration->getParameterName('filters', 'filters')] = $filters;
        }

        if (null !== $request->getAdvancedFilterExpression()) {
            $payload[$configuration->getParameterName('advanced_filters', 'advanced_filters')] = $this->mapExpression(
                $request->getAdvancedFilterExpression()->root,
                $configuration,
            );
        }

        $context = [];

        foreach ($configuration->getContextKeys() as $key) {
            if ($definition->getContext()->has($key)) {
                $context[$key] = $this->normalizeValue($definition->getContext()->get($key));
            }
        }

        if ([] !== $context) {
            $payload[$configuration->getParameterName('context', 'context')] = $context;
        }

        $isGet = 'GET' === $configuration->getMethod();

        return new HttpTransportRequest(
            method: $configuration->getMethod(),
            url: $configuration->getEndpoint(),
            query: $isGet ? $payload : [],
            headers: $configuration->getHeaders(),
            json: $isGet ? null : $payload,
            timeout: $configuration->getTimeout(),
            maxAttempts: $configuration->getMaxAttempts(),
            retryStatusCodes: $configuration->getRetryStatusCodes(),
            cancellation: $configuration->getCancellation(),
        );
    }

    /**
     * @return array<string, scalar|array<array-key, mixed>|null>
     */
    private function paginationPayload(
        DatatableRequest $request,
        HttpProviderConfiguration $configuration,
    ): array {
        if (!$request->isPaginationEnabled()) {
            return [];
        }

        return match ($configuration->getPaginationStrategy()) {
            \Zhortein\DatatableBundle\Enum\HttpPaginationStrategy::Page => [
                $configuration->getParameterName('page', 'page') => $request->getPage(),
                $configuration->getParameterName('page_size', 'page_size') => $request->getPageSize(),
            ],
            \Zhortein\DatatableBundle\Enum\HttpPaginationStrategy::Offset => [
                $configuration->getParameterName('offset', 'offset') => $request->getOffset(),
                $configuration->getParameterName('limit', 'limit') => $request->getPageSize(),
            ],
            \Zhortein\DatatableBundle\Enum\HttpPaginationStrategy::Cursor => array_filter([
                $configuration->getParameterName('cursor', 'cursor') => $this->normalizeCursor($request->getOption('http_cursor')),
                $configuration->getParameterName('limit', 'limit') => $request->getPageSize(),
            ], static fn (mixed $value): bool => null !== $value),
        };
    }

    private function normalizeCursor(mixed $cursor): ?string
    {
        if (!is_string($cursor)) {
            return null;
        }

        $cursor = trim($cursor);

        return '' === $cursor ? null : $cursor;
    }

    /**
     * @return list<array{field: string, operator: string, value: mixed}>
     */
    private function mapFilters(
        DatatableDefinition $definition,
        DatatableRequest $request,
        HttpProviderConfiguration $configuration,
    ): array {
        $filters = [];

        foreach ($definition->getPermanentFilters() as $filter) {
            $value = $this->resolveValue($filter->getValue(), $definition);

            if (FilterOperator::Between === $filter->getOperator()) {
                $value = [$value, $this->resolveValue($filter->getSecondValue(), $definition)];
            }

            $filters[] = [
                'field' => $configuration->mapField($filter->getField()),
                'operator' => $configuration->mapOperator($this->normalizePermanentOperator($filter->getOperator())),
                'value' => $this->normalizeValue($value),
            ];
        }

        foreach ($definition->getFilters() as $filter) {
            if (!$request->hasFilter($filter->getName())) {
                continue;
            }

            $filters[] = [
                'field' => $configuration->mapField($filter->getField()),
                'operator' => $configuration->mapOperator('eq'),
                'value' => $this->normalizeValue($request->getFilter($filter->getName())),
            ];
        }

        return $filters;
    }

    private function normalizePermanentOperator(FilterOperator $operator): string
    {
        return match ($operator) {
            FilterOperator::Equals => 'eq',
            FilterOperator::NotEquals => 'neq',
            FilterOperator::GreaterThan => 'gt',
            FilterOperator::GreaterThanOrEquals => 'gte',
            FilterOperator::LessThan => 'lt',
            FilterOperator::LessThanOrEquals => 'lte',
            FilterOperator::In => 'in',
            FilterOperator::NotIn => 'not_in',
            FilterOperator::IsNull => 'is_null',
            FilterOperator::IsNotNull => 'is_not_null',
            FilterOperator::Between => 'between',
            FilterOperator::Like => 'like',
            FilterOperator::NotLike => 'not_like',
        };
    }

    private function resolveValue(mixed $value, DatatableDefinition $definition): mixed
    {
        if (!$value instanceof ContextFilterValue) {
            return $value;
        }

        if (!$definition->getContext()->has($value->getKey())) {
            throw new HttpDataProviderException(sprintf('The HTTP provider references missing datatable context key "%s".', $value->getKey()));
        }

        return $definition->getContext()->get($value->getKey());
    }

    /**
     * @return array<string, mixed>
     */
    private function mapExpression(
        ExpressionInterface $expression,
        HttpProviderConfiguration $configuration,
    ): array {
        if ($expression instanceof Condition) {
            return [
                'field' => $configuration->mapField($expression->field),
                'operator' => $configuration->mapOperator($expression->operator->value),
                'value' => $this->normalizeValue($expression->value),
            ];
        }

        if ($expression instanceof Group) {
            return [
                'logic' => strtolower($expression->logic->value),
                'children' => array_map(
                    fn (ExpressionInterface $child): array => $this->mapExpression($child, $configuration),
                    $expression->children,
                ),
            ];
        }

        throw new HttpDataProviderException(sprintf('Unsupported advanced filter expression "%s".', $expression::class));
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }

        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->normalizeValue($item), $value);
        }

        if (null === $value || is_scalar($value)) {
            return $value;
        }

        throw new HttpDataProviderException(sprintf('The HTTP provider cannot normalize a value of type "%s".', get_debug_type($value)));
    }

    private function unsupported(string $operation): HttpDataProviderException
    {
        return new HttpDataProviderException(sprintf('The remote data provider does not support %s.', $operation));
    }
}
