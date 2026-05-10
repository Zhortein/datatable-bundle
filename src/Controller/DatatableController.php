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
use Zhortein\DatatableBundle\Result\DatatableResult;

final readonly class DatatableController
{
    public function __construct(
        private DatatableDefinitionFactory $definitionFactory,
        private DatatableRequestFactory $requestFactory,
        private DataProviderRegistry $providerRegistry,
        private DatatableRenderer $renderer,
        private ExportWriterRegistry $exportWriterRegistry,
    ) {
    }

    public function fragments(Request $request, string $name): JsonResponse
    {
        $definition = $this->definitionFactory->create($name);
        $datatableRequest = $this->requestFactory->createFromRequest($request);
        $provider = $this->providerRegistry->resolve($definition);
        $result = $provider->getData($definition, $datatableRequest);

        return new JsonResponse([
            'body' => $this->renderer->renderBody(
                $definition,
                $result,
                $datatableRequest->getColumnVisibilityOptions(),
            ),
            'pagination' => $this->renderer->renderPagination($definition, $result),
            'summary' => $this->createSummary($result),
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

    private function createSummary(DatatableResult $result): string
    {
        if (0 === $result->getFilteredItems()) {
            return 'Showing 0 entries';
        }

        $start = (($result->getPage() - 1) * $result->getPageSize()) + 1;
        $end = min($result->getPage() * $result->getPageSize(), $result->getFilteredItems());

        if ($result->hasFilteredItems()) {
            return sprintf(
                'Showing %d to %d of %d entries, filtered from %d total entries',
                $start,
                $end,
                $result->getFilteredItems(),
                $result->getTotalItems(),
            );
        }

        return sprintf(
            'Showing %d to %d of %d entries',
            $start,
            $end,
            $result->getFilteredItems(),
        );
    }
}
