<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Provider;

use Zhortein\DatatableBundle\Contract\DataProviderInterface;
use Zhortein\DatatableBundle\Contract\ExportRowCountProviderInterface;
use Zhortein\DatatableBundle\Contract\HttpRequestMapperInterface;
use Zhortein\DatatableBundle\Contract\HttpResponseMapperInterface;
use Zhortein\DatatableBundle\Contract\HttpTransportInterface;
use Zhortein\DatatableBundle\Contract\StreamingDataProviderInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\HttpPaginationStrategy;
use Zhortein\DatatableBundle\Exception\HttpDataProviderException;
use Zhortein\DatatableBundle\Export\ExportRow;
use Zhortein\DatatableBundle\Export\ExportStreamContext;
use Zhortein\DatatableBundle\Provider\Http\DefaultHttpRequestMapper;
use Zhortein\DatatableBundle\Provider\Http\DefaultHttpResponseMapper;
use Zhortein\DatatableBundle\Provider\Http\HttpDataPage;
use Zhortein\DatatableBundle\Provider\Http\HttpProviderConfiguration;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Result\DatatableResult;

/**
 * Generic provider driven by explicit transport and mapping contracts.
 */
final readonly class HttpDataProvider implements DataProviderInterface, ExportRowCountProviderInterface, StreamingDataProviderInterface
{
    public const string PROVIDER_NAME = 'http';
    public const string OPTION_CONFIGURATION = 'http_configuration';
    public const string OPTION_REQUEST_MAPPER = 'http_request_mapper';
    public const string OPTION_RESPONSE_MAPPER = 'http_response_mapper';

    public function __construct(
        private ?HttpTransportInterface $transport = null,
        private ?HttpRequestMapperInterface $requestMapper = null,
        private ?HttpResponseMapperInterface $responseMapper = null,
    ) {
    }

    public function supports(DatatableDefinition $definition): bool
    {
        return self::PROVIDER_NAME === $definition->getOption(DataProviderRegistry::OPTION_PROVIDER)
            || $definition->getOption(self::OPTION_CONFIGURATION) instanceof HttpProviderConfiguration;
    }

    public function getData(DatatableDefinition $definition, DatatableRequest $request): DatatableResult
    {
        $page = $this->loadPage($definition, $request);
        $estimatedCount = $this->estimateCount($page, $request);
        $totalItems = $page->getTotalItems() ?? $estimatedCount;
        $filteredItems = $page->getFilteredItems() ?? $page->getTotalItems() ?? $estimatedCount;

        return new DatatableResult(
            rows: $page->getRows(),
            page: $request->getPage(),
            pageSize: $request->getPageSize(),
            totalItems: $totalItems,
            filteredItems: $filteredItems,
            sources: $page->getRows(),
            metadata: [
                'identifiers' => $page->getIdentifiers(),
                'pagination' => [
                    'nextCursor' => $page->getNextCursor(),
                    'previousCursor' => $page->getPreviousCursor(),
                    'hasNextPage' => $page->hasNextPage(),
                ],
            ],
        );
    }

    public function countExportRows(
        DatatableDefinition $definition,
        DatatableRequest $request,
    ): int {
        $configuration = $this->getConfiguration($definition);

        if (
            !$configuration->getCapabilities()->supportsExports()
            || !$configuration->getCapabilities()->providesExactCounts()
        ) {
            return -1;
        }

        $page = $this->loadPage($definition, $this->withPagination($request, 1, 1));

        return $page->getFilteredItems() ?? $page->getTotalItems() ?? -1;
    }

    /**
     * @return iterable<ExportRow>
     */
    public function streamExportRows(
        DatatableDefinition $definition,
        DatatableRequest $request,
        ExportStreamContext $context,
    ): iterable {
        $configuration = $this->getConfiguration($definition);

        if (!$configuration->getCapabilities()->supportsExports()) {
            throw new HttpDataProviderException('The remote data provider does not support exports.');
        }

        $pageNumber = $request->isPaginationEnabled() ? $request->getPage() : 1;
        $cursor = $this->normalizeCursor($request->getOption('http_cursor'));
        $remaining = $context->getExpectedRowCount();

        while ($remaining > 0 && !$context->isCancelled()) {
            $batchSize = min($context->getBatchSize(), $remaining);
            $pageRequest = $this->withPagination($request, $pageNumber, $batchSize, $cursor);
            $page = $this->loadPage($definition, $pageRequest);
            $rows = $page->getRows();

            if ([] === $rows) {
                return;
            }

            foreach ($rows as $row) {
                if ($remaining <= 0 || $context->isCancelled()) {
                    return;
                }

                yield new ExportRow($row, $row);
                --$remaining;
            }

            if (count($rows) < $batchSize || false === $page->hasNextPage()) {
                return;
            }

            if (HttpPaginationStrategy::Cursor === $configuration->getPaginationStrategy()) {
                $cursor = $page->getNextCursor();

                if (null === $cursor) {
                    return;
                }
            } else {
                ++$pageNumber;
            }
        }
    }

    private function loadPage(
        DatatableDefinition $definition,
        DatatableRequest $request,
    ): HttpDataPage {
        if (null === $this->transport) {
            throw new HttpDataProviderException('No HTTP transport is configured for the remote data provider.');
        }

        $configuration = $this->getConfiguration($definition);
        $requestMapper = $definition->getOption(self::OPTION_REQUEST_MAPPER, $this->requestMapper ?? new DefaultHttpRequestMapper());
        $responseMapper = $definition->getOption(self::OPTION_RESPONSE_MAPPER, $this->responseMapper ?? new DefaultHttpResponseMapper());

        if (!$requestMapper instanceof HttpRequestMapperInterface) {
            throw new HttpDataProviderException(sprintf('The "%s" option must implement HttpRequestMapperInterface.', self::OPTION_REQUEST_MAPPER));
        }

        if (!$responseMapper instanceof HttpResponseMapperInterface) {
            throw new HttpDataProviderException(sprintf('The "%s" option must implement HttpResponseMapperInterface.', self::OPTION_RESPONSE_MAPPER));
        }

        $transportRequest = $requestMapper->mapRequest($definition, $request, $configuration);
        $transportResponse = $this->transport->send($transportRequest);

        return $responseMapper->mapResponse($transportResponse, $definition, $request, $configuration);
    }

    private function getConfiguration(DatatableDefinition $definition): HttpProviderConfiguration
    {
        $configuration = $definition->getOption(self::OPTION_CONFIGURATION);

        if (!$configuration instanceof HttpProviderConfiguration) {
            throw new HttpDataProviderException(sprintf('The datatable "%s" must define an HttpProviderConfiguration in option "%s".', $definition->getName(), self::OPTION_CONFIGURATION));
        }

        return $configuration;
    }

    private function estimateCount(HttpDataPage $page, DatatableRequest $request): int
    {
        $count = $request->getOffset() + count($page->getRows());

        return true === $page->hasNextPage() ? $count + 1 : $count;
    }

    private function normalizeCursor(mixed $cursor): ?string
    {
        if (!is_string($cursor)) {
            return null;
        }

        $cursor = trim($cursor);

        return '' === $cursor ? null : $cursor;
    }

    private function withPagination(
        DatatableRequest $request,
        int $page,
        int $pageSize,
        ?string $cursor = null,
    ): DatatableRequest {
        $options = $request->getOptions();
        unset($options['disablePagination']);

        if (null === $cursor) {
            unset($options['http_cursor']);
        } else {
            $options['http_cursor'] = $cursor;
        }

        return DatatableRequest::create(
            page: $page,
            pageSize: $pageSize,
            searchQuery: $request->getSearchQuery(),
            sortField: $request->getSortField(),
            sortDirection: $request->getSortDirection(),
            filters: $request->getFilters(),
            visibleColumns: $request->getVisibleColumns(),
            hiddenColumns: $request->getHiddenColumns(),
            options: $options,
            advancedFilterExpression: $request->getAdvancedFilterExpression(),
            sorts: $request->getSorts(),
        );
    }
}
