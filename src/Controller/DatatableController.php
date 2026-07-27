<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zhortein\DatatableBundle\Context\DatatableContextRequestResolver;
use Zhortein\DatatableBundle\Contract\DatatableExportAuthorizationCheckerInterface;
use Zhortein\DatatableBundle\Contract\ExportCancellationInterface;
use Zhortein\DatatableBundle\Contract\ExportRowCountProviderInterface;
use Zhortein\DatatableBundle\Contract\StreamingDataProviderInterface;
use Zhortein\DatatableBundle\Contract\StreamingExportWriterInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\ExportFormat;
use Zhortein\DatatableBundle\Export\AllowAllDatatableExportAuthorizationChecker;
use Zhortein\DatatableBundle\Export\ConnectionAbortedExportCancellation;
use Zhortein\DatatableBundle\Export\DatatableExportAuthorizationContext;
use Zhortein\DatatableBundle\Export\DatatableExportRequest;
use Zhortein\DatatableBundle\Export\ExportLimitResolver;
use Zhortein\DatatableBundle\Export\ExportStreamContext;
use Zhortein\DatatableBundle\Export\ExportWriterRegistry;
use Zhortein\DatatableBundle\Exception\ExportException;
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
        private ?DatatableExportAuthorizationCheckerInterface $exportAuthorizationChecker = null,
        private ?ExportLimitResolver $exportLimitResolver = null,
        private ?TranslatorInterface $translator = null,
        private int $exportBatchSize = 500,
        private ?ExportCancellationInterface $exportCancellation = null,
    ) {
        if ($this->exportBatchSize < 1) {
            throw new \InvalidArgumentException('The export batch size must be greater than or equal to 1.');
        }
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
        [$instance, $childRequest] = $this->resolveContext($request, $definition);
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

        $authorizationContext = new DatatableExportAuthorizationContext(
            definition: $definition,
            exportRequest: $exportRequest,
            request: $request,
            instance: $instance,
            childDatatable: null !== $childRequest,
        );
        $authorizationChecker = $this->exportAuthorizationChecker
            ?? new AllowAllDatatableExportAuthorizationChecker();

        if (!$authorizationChecker->isGranted($authorizationContext)) {
            return $this->createExportErrorResponse(
                'zhortein_datatable.export.authorization_denied',
                Response::HTTP_FORBIDDEN,
                'You are not allowed to export this datatable.',
            );
        }

        $writer = $this->exportWriterRegistry->resolve($exportFormat);
        $effectiveDatatableRequest = $exportRequest->shouldKeepPagination()
            ? $datatableRequest
            : $datatableRequest->withoutPagination();

        $provider = $this->providerRegistry->resolve($definition);

        if (!$provider instanceof ExportRowCountProviderInterface) {
            return $this->createExportErrorResponse(
                'zhortein_datatable.export.count_unavailable',
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'This data provider cannot safely determine the export size. Implement ExportRowCountProviderInterface or use a supported provider.',
            );
        }

        $filteredRowCount = $provider->countExportRows($definition, $datatableRequest);

        if ($filteredRowCount < 0) {
            return $this->createExportErrorResponse(
                'zhortein_datatable.export.count_unavailable',
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'This data provider cannot safely determine the export size. Implement ExportRowCountProviderInterface or use a supported provider.',
            );
        }

        $exportRowCount = $exportRequest->shouldKeepPagination()
            ? min(
                $datatableRequest->getPageSize(),
                max(0, $filteredRowCount - $datatableRequest->getOffset()),
            )
            : $filteredRowCount;
        $exportLimit = ($this->exportLimitResolver ?? new ExportLimitResolver())
            ->resolve($definition, $exportFormat);

        if ($exportRowCount > $exportLimit) {
            return $this->createExportErrorResponse(
                'zhortein_datatable.export.limit_exceeded',
                Response::HTTP_REQUEST_ENTITY_TOO_LARGE,
                sprintf('This export exceeds the %d-row limit. Apply more filters or export the current page.', $exportLimit),
                ['%limit%' => $exportLimit],
            );
        }

        if (
            $provider instanceof StreamingDataProviderInterface
            && $writer instanceof StreamingExportWriterInterface
        ) {
            $streamContext = new ExportStreamContext(
                batchSize: $this->exportBatchSize,
                expectedRowCount: $exportRowCount,
                cancellation: $this->exportCancellation ?? new ConnectionAbortedExportCancellation(),
            );

            return $writer->writeStream(
                request: $exportRequest,
                definition: $definition,
                rows: $this->guardStreamedRows(
                    $provider->streamExportRows(
                        definition: $definition,
                        request: $effectiveDatatableRequest,
                        context: $streamContext,
                    ),
                    $exportLimit,
                ),
                context: $streamContext,
            );
        }

        $result = $provider->getData($definition, $effectiveDatatableRequest);

        if (count($result->getRows()) > $exportLimit) {
            return $this->createExportErrorResponse(
                'zhortein_datatable.export.limit_exceeded',
                Response::HTTP_REQUEST_ENTITY_TOO_LARGE,
                sprintf('This export exceeds the %d-row limit. Apply more filters or export the current page.', $exportLimit),
                ['%limit%' => $exportLimit],
            );
        }

        return $writer->write($exportRequest, $definition, $result);
    }

    /**
     * @param iterable<\Zhortein\DatatableBundle\Export\ExportRow> $rows
     *
     * @return iterable<\Zhortein\DatatableBundle\Export\ExportRow>
     */
    private function guardStreamedRows(iterable $rows, int $limit): iterable
    {
        $rowCount = 0;

        foreach ($rows as $row) {
            ++$rowCount;

            if ($rowCount > $limit) {
                throw new ExportException(sprintf(
                    'The streaming provider yielded more than the configured %d-row export limit.',
                    $limit,
                ));
            }

            yield $row;
        }
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

    /**
     * @param array<string, int|string> $parameters
     */
    private function createExportErrorResponse(
        string $translationKey,
        int $status,
        string $fallback,
        array $parameters = [],
    ): Response {
        $message = $this->translator?->trans(
            $translationKey,
            $parameters,
            'zhortein_datatable',
        ) ?? $fallback;

        $response = new Response(
            $message,
            $status,
            ['Content-Type' => 'text/plain; charset=UTF-8'],
        );
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }
}
