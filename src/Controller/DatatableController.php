<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Zhortein\DatatableBundle\Enum\ExportFormat;
use Zhortein\DatatableBundle\Export\DatatableExportRequest;
use Zhortein\DatatableBundle\Export\ExportWriterRegistry;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\Factory\DatatableRequestFactory;
use Zhortein\DatatableBundle\Provider\DataProviderRegistry;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Renderer\DatatableSummaryRenderer;

final readonly class DatatableController
{
    public function __construct(
        private DatatableDefinitionFactory $definitionFactory,
        private DatatableRequestFactory $requestFactory,
        private DataProviderRegistry $providerRegistry,
        private DatatableRenderer $renderer,
        private ExportWriterRegistry $exportWriterRegistry,
        private DatatableSummaryRenderer $summaryRenderer,
    ) {
    }

    public function fragments(Request $request, string $name): JsonResponse
    {
        $definition = $this->definitionFactory->create($name);
        $datatableRequest = $this->requestFactory->createFromRequest($request);
        $provider = $this->providerRegistry->resolve($definition);
        $result = $provider->getData($definition, $datatableRequest);

        $renderOptions = $datatableRequest->getColumnVisibilityOptions();
        $renderOptions['filters'] = $datatableRequest->getFilters();
        $renderOptions['filterLayout'] = $request->query->get('filterLayout', 'toolbar');
        $renderOptions['booleanDisplayMode'] = $request->query->get('booleanDisplayMode');
        $renderOptions['paginationSize'] = $request->query->get('paginationSize');
        $renderOptions['tableSmall'] = $request->query->getBoolean('tableSmall');

        return new JsonResponse([
            'header' => $this->renderer->renderHeader($definition, $renderOptions),
            'body' => $this->renderer->renderBody($definition, $result, $renderOptions),
            'pagination' => $this->renderer->renderPagination($definition, $result, $renderOptions),
            'summary' => $this->summaryRenderer->render($result),
            'page' => $result->getPage(),
            'pageSize' => $result->getPageSize(),
            'totalItems' => $result->getTotalItems(),
            'filteredItems' => $result->getFilteredItems(),
            'totalPages' => $result->getTotalPages(),
        ], Response::HTTP_OK);
    }

    public function export(Request $request, string $name, string $format = 'csv'): Response
    {
        $definition = $this->definitionFactory->create($name);
        $datatableRequest = $this->requestFactory->createFromRequest($request);
        $exportFormat = ExportFormat::fromString($format);
        $mode = $request->query->get('mode', 'current');
        $filename = $request->query->get('filename');

        $exportRequest = DatatableExportRequest::create(
            datatableName: $name,
            format: $exportFormat,
            mode: $mode,
            filename: $filename,
            datatableRequest: $datatableRequest,
        );

        $effectiveDatatableRequest = $exportRequest->shouldKeepPagination()
            ? $datatableRequest
            : $datatableRequest->withoutPagination();

        $provider = $this->providerRegistry->resolve($definition);
        $result = $provider->getData($definition, $effectiveDatatableRequest);
        $writer = $this->exportWriterRegistry->resolve($exportFormat);

        return $writer->write($exportRequest, $definition, $result);
    }
}
