<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Zhortein\DatatableBundle\Context\DatatableContextRequestResolver;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\ExportFormat;
use Zhortein\DatatableBundle\Export\DatatableExportRequest;
use Zhortein\DatatableBundle\Export\ExportWriterRegistry;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\Factory\DatatableRequestFactory;
use Zhortein\DatatableBundle\Hierarchy\ChildDatatableRequest;
use Zhortein\DatatableBundle\Hierarchy\ChildDatatableRequestResolver;
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
        private ?DatatableContextRequestResolver $contextRequestResolver = null,
        private ?ChildDatatableRequestResolver $childRequestResolver = null,
    ) {
    }

    public function fragments(Request $request, string $name): JsonResponse
    {
        $definition = $this->definitionFactory->create($name);
        [$instance, $childRequest] = $this->resolveContext($request, $definition);
        $datatableRequest = $this->requestFactory->createFromRequest($request, $definition);
        $provider = $this->providerRegistry->resolve($definition);
        $result = $provider->getData($definition, $datatableRequest);

        $renderOptions = $datatableRequest->getColumnVisibilityOptions();
        $renderOptions['filters'] = $datatableRequest->getFilters();
        $renderOptions['filterLayout'] = $request->query->get('filterLayout', 'toolbar');
        $renderOptions['booleanDisplayMode'] = $request->query->get('booleanDisplayMode');
        $renderOptions['paginationSize'] = $request->query->get('paginationSize');
        $renderOptions['tableSmall'] = $request->query->getBoolean('tableSmall');
        $renderOptions['instance'] = $instance;

        if (null !== $childRequest) {
            $renderOptions['childDepth'] = $childRequest->getDepth();
            $renderOptions['forceContextToken'] = true;
        }

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
        $this->resolveContext($request, $definition);
        $datatableRequest = $this->requestFactory->createFromRequest($request, $definition);
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

    public function child(Request $request, string $name): Response
    {
        if (null === $this->childRequestResolver) {
            throw new \LogicException('The child datatable request resolver is required to render child datatables.');
        }

        $definition = $this->definitionFactory->create($name);
        $childRequest = $this->childRequestResolver->resolve($request, $definition);

        $response = new Response($this->renderer->render($definition, [
            'instance' => $childRequest->getInstance(),
            'childDepth' => $childRequest->getDepth(),
            'forceContextToken' => true,
        ]));
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    /**
     * @return array{string, ChildDatatableRequest|null}
     */
    private function resolveContext(Request $request, DatatableDefinition $definition): array
    {
        $instance = $this->contextRequestResolver?->resolve($request, $definition) ?? $definition->getName();

        if (null === $this->childRequestResolver || !$this->childRequestResolver->supports($instance)) {
            return [$instance, null];
        }

        $childRequest = $this->childRequestResolver->resolve($request, $definition);

        return [$childRequest->getInstance(), $childRequest];
    }
}
