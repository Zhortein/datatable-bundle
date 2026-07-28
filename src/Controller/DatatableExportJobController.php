<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Zhortein\DatatableBundle\Context\DatatableContextRequestResolver;
use Zhortein\DatatableBundle\Contract\DatatableExportAuthorizationCheckerInterface;
use Zhortein\DatatableBundle\Contract\ExportJobClockInterface;
use Zhortein\DatatableBundle\Contract\ExportJobDispatcherInterface;
use Zhortein\DatatableBundle\Contract\ExportJobExpiryPolicyInterface;
use Zhortein\DatatableBundle\Contract\ExportJobIdentifierGeneratorInterface;
use Zhortein\DatatableBundle\Contract\ExportJobOwnerResolverInterface;
use Zhortein\DatatableBundle\Contract\ExportJobRepositoryInterface;
use Zhortein\DatatableBundle\Contract\ExportJobResultStorageInterface;
use Zhortein\DatatableBundle\Contract\ExportRowCountProviderInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\ExportFormat;
use Zhortein\DatatableBundle\Enum\ExportJobStatus;
use Zhortein\DatatableBundle\Export\DatatableExportAuthorizationContext;
use Zhortein\DatatableBundle\Export\DatatableExportRequest;
use Zhortein\DatatableBundle\Export\ExportLimitResolver;
use Zhortein\DatatableBundle\Export\Job\ExportJob;
use Zhortein\DatatableBundle\Export\Job\ExportJobIdentifier;
use Zhortein\DatatableBundle\Export\Job\ExportJobRequest;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\Factory\DatatableRequestFactory;
use Zhortein\DatatableBundle\Hierarchy\ChildDatatableRequest;
use Zhortein\DatatableBundle\Hierarchy\ChildDatatableRequestResolver;
use Zhortein\DatatableBundle\Provider\DataProviderRegistry;

final readonly class DatatableExportJobController
{
    public function __construct(
        private bool $enabled,
        private DatatableDefinitionFactory $definitionFactory,
        private DatatableRequestFactory $requestFactory,
        private DataProviderRegistry $providerRegistry,
        private DatatableExportAuthorizationCheckerInterface $authorizationChecker,
        private ExportLimitResolver $limitResolver,
        private ExportJobRepositoryInterface $repository,
        private ExportJobResultStorageInterface $resultStorage,
        private ExportJobClockInterface $clock,
        private ExportJobExpiryPolicyInterface $expiryPolicy,
        private ExportJobIdentifierGeneratorInterface $identifierGenerator,
        private ExportJobOwnerResolverInterface $ownerResolver,
        private ExportJobDispatcherInterface $dispatcher,
        private ?DatatableContextRequestResolver $contextRequestResolver = null,
        private ?ChildDatatableRequestResolver $childRequestResolver = null,
    ) {
    }

    public function submit(Request $request, string $name, string $format = 'csv'): JsonResponse
    {
        if (!$this->enabled) {
            return $this->error('async_export_disabled', Response::HTTP_NOT_FOUND);
        }

        $ownerIdentifier = $this->resolveOwner($request);

        if (null === $ownerIdentifier) {
            return $this->error('owner_required', Response::HTTP_FORBIDDEN);
        }

        $definition = $this->definitionFactory->create($name);
        [$instance, $childRequest] = $this->resolveContext($request, $definition);
        $datatableRequest = $this->requestFactory->createFromRequest($request, $definition);
        $exportFormat = ExportFormat::fromString($format);
        $exportRequest = DatatableExportRequest::create(
            datatableName: $name,
            format: $exportFormat,
            mode: $request->query->get('mode', 'full'),
            filename: $request->query->get('filename'),
            datatableRequest: $datatableRequest,
        );

        if (!$this->authorizationChecker->isGranted(new DatatableExportAuthorizationContext(
            definition: $definition,
            exportRequest: $exportRequest,
            request: $request,
            instance: $instance,
            childDatatable: null !== $childRequest,
        ))) {
            return $this->error('authorization_denied', Response::HTTP_FORBIDDEN);
        }

        $provider = $this->providerRegistry->resolve($definition);

        if (!$provider instanceof ExportRowCountProviderInterface) {
            return $this->error('count_unavailable', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $filteredRowCount = $provider->countExportRows($definition, $datatableRequest);

        if ($filteredRowCount < 0) {
            return $this->error('count_unavailable', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $rowCount = $exportRequest->shouldKeepPagination()
            ? min(
                $datatableRequest->getPageSize(),
                max(0, $filteredRowCount - $datatableRequest->getOffset()),
            )
            : $filteredRowCount;
        $rowLimit = $this->limitResolver->resolve($definition, $exportFormat);

        if ($rowCount > $rowLimit) {
            return $this->error('limit_exceeded', Response::HTTP_REQUEST_ENTITY_TOO_LARGE, [
                'limit' => $rowLimit,
            ]);
        }

        $jobRequest = new ExportJobRequest(
            exportRequest: $exportRequest,
            instance: $instance,
            childDatatable: null !== $childRequest,
            contextValues: $this->normalizeContextValues(
                $definition->getContext()->getBrowserSafeValues(),
            ),
            locale: $request->getLocale(),
            expectedRowCount: $rowCount,
            rowLimit: $rowLimit,
        );

        try {
            $idempotencyKey = $this->resolveIdempotencyKey($request);
        } catch (\InvalidArgumentException) {
            return $this->error('invalid_idempotency_key', Response::HTTP_BAD_REQUEST);
        }

        if (null !== $idempotencyKey) {
            $existing = $this->repository->findIdempotent($ownerIdentifier, $idempotencyKey);

            if (null !== $existing) {
                if (!hash_equals($existing->getRequest()->fingerprint(), $jobRequest->fingerprint())) {
                    return $this->error('idempotency_conflict', Response::HTTP_CONFLICT);
                }

                $dispatchError = $this->dispatchPending($existing);

                if (null !== $dispatchError) {
                    return $dispatchError;
                }

                return $this->jobResponse(
                    $this->repository->find($existing->getIdentifier()) ?? $existing,
                    Response::HTTP_ACCEPTED,
                );
            }
        }

        $identifier = $this->generateUniqueIdentifier();
        $now = $this->clock->now();
        $job = ExportJob::pending(
            identifier: $identifier,
            request: $jobRequest,
            ownerIdentifier: $ownerIdentifier,
            idempotencyKey: $idempotencyKey,
            createdAt: $now,
            expiresAt: $this->expiryPolicy->expiresAt($now),
        );
        $storedJob = $this->repository->create($job);

        if ($storedJob->getIdentifier()->toString() !== $job->getIdentifier()->toString()) {
            if (!hash_equals($storedJob->getRequest()->fingerprint(), $jobRequest->fingerprint())) {
                return $this->error('idempotency_conflict', Response::HTTP_CONFLICT);
            }

            $dispatchError = $this->dispatchPending($storedJob);

            if (null !== $dispatchError) {
                return $dispatchError;
            }

            return $this->jobResponse(
                $this->repository->find($storedJob->getIdentifier()) ?? $storedJob,
                Response::HTTP_ACCEPTED,
            );
        }

        $dispatchError = $this->dispatchPending($job);

        if (null !== $dispatchError) {
            return $dispatchError;
        }

        return $this->jobResponse(
            $this->repository->find($job->getIdentifier()) ?? $job,
            Response::HTTP_ACCEPTED,
        );
    }

    public function status(Request $request, string $jobIdentifier): JsonResponse
    {
        $job = $this->resolveOwnedJob($request, $jobIdentifier);

        if (null === $job) {
            return $this->error('job_not_found', Response::HTTP_NOT_FOUND);
        }

        $job = $this->expire($job);

        return $this->jobResponse($job);
    }

    public function download(Request $request, string $jobIdentifier): Response
    {
        $job = $this->resolveOwnedJob($request, $jobIdentifier);

        if (null === $job) {
            return $this->error('job_not_found', Response::HTTP_NOT_FOUND);
        }

        $job = $this->expire($job);

        if (ExportJobStatus::Expired === $job->getStatus()) {
            return $this->error('job_expired', Response::HTTP_GONE);
        }

        $result = $job->getResult();

        if (ExportJobStatus::Completed !== $job->getStatus() || null === $result) {
            return $this->error('job_not_ready', Response::HTTP_CONFLICT, [
                'status' => $job->getStatus()->value,
            ]);
        }

        $jobRequest = $job->getRequest();
        $exportRequest = $jobRequest->getExportRequest();
        $definition = $this->definitionFactory->create($exportRequest->getDatatableName());
        $definition->setContext(
            $definition->getContext()->withBrowserValues($jobRequest->getContextValues()),
        );

        if (!$this->authorizationChecker->isGranted(new DatatableExportAuthorizationContext(
            definition: $definition,
            exportRequest: $exportRequest,
            request: $request,
            instance: $jobRequest->getInstance(),
            childDatatable: $jobRequest->isChildDatatable(),
        ))) {
            return $this->error('authorization_denied', Response::HTTP_FORBIDDEN);
        }

        $response = new StreamedResponse(
            function () use ($result): void {
                foreach ($this->resultStorage->read($result) as $chunk) {
                    echo $chunk;
                }
            },
            Response::HTTP_OK,
            [
                'Content-Type' => $result->getContentType(),
                'Content-Disposition' => sprintf('attachment; filename="%s"', $result->getFilename()),
                'Content-Length' => (string) $result->getSize(),
            ],
        );
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    private function resolveOwner(Request $request): ?string
    {
        $ownerIdentifier = $this->ownerResolver->resolve($request);

        if (null === $ownerIdentifier) {
            return null;
        }

        $ownerIdentifier = trim($ownerIdentifier);

        return '' === $ownerIdentifier ? null : $ownerIdentifier;
    }

    private function resolveOwnedJob(Request $request, string $jobIdentifier): ?ExportJob
    {
        if (!$this->enabled) {
            return null;
        }

        $ownerIdentifier = $this->resolveOwner($request);

        if (null === $ownerIdentifier) {
            return null;
        }

        try {
            $identifier = new ExportJobIdentifier($jobIdentifier);
        } catch (\InvalidArgumentException) {
            return null;
        }

        $job = $this->repository->find($identifier);

        return null !== $job && $job->belongsTo($ownerIdentifier) ? $job : null;
    }

    private function expire(ExportJob $job): ExportJob
    {
        $now = $this->clock->now();

        if (
            in_array($job->getStatus(), [ExportJobStatus::Running, ExportJobStatus::Expired], true)
            || !$job->isExpiredAt($now)
        ) {
            return $job;
        }

        $result = $job->getResult();

        if (null !== $result) {
            $this->resultStorage->delete($result);
        }

        $job = $job->expire($now);
        $this->repository->save($job);

        return $job;
    }

    private function generateUniqueIdentifier(): ExportJobIdentifier
    {
        for ($attempt = 0; $attempt < 5; ++$attempt) {
            $identifier = $this->identifierGenerator->generate();

            if (null === $this->repository->find($identifier)) {
                return $identifier;
            }
        }

        throw new \RuntimeException('Unable to generate a unique export job identifier.');
    }

    private function resolveIdempotencyKey(Request $request): ?string
    {
        $idempotencyKey = $request->headers->get('Idempotency-Key');

        if (null === $idempotencyKey) {
            return null;
        }

        $idempotencyKey = trim($idempotencyKey);

        if (
            '' === $idempotencyKey
            || 255 < strlen($idempotencyKey)
            || 1 === preg_match('/[\x00-\x1F\x7F]/', $idempotencyKey)
        ) {
            throw new \InvalidArgumentException('The idempotency key is invalid.');
        }

        return $idempotencyKey;
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, bool|float|int|string|null>
     */
    private function normalizeContextValues(array $values): array
    {
        $normalized = [];

        foreach ($values as $name => $value) {
            if ($value instanceof \BackedEnum) {
                $value = $value->value;
            } elseif ($value instanceof \Stringable) {
                $value = (string) $value;
            }

            if (null !== $value && !is_scalar($value)) {
                throw new \InvalidArgumentException(sprintf('The background export context value "%s" must be scalar or null.', $name));
            }

            $normalized[$name] = $value;
        }

        return $normalized;
    }

    private function dispatchPending(ExportJob $job): ?JsonResponse
    {
        if (ExportJobStatus::Pending !== $job->getStatus()) {
            return null;
        }

        try {
            $this->dispatcher->dispatch($job->getIdentifier());
        } catch (\Throwable) {
            return $this->error('dispatch_unavailable', Response::HTTP_SERVICE_UNAVAILABLE, [
                'jobIdentifier' => $job->getIdentifier()->toString(),
            ]);
        }

        return null;
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
     * @param array<string, bool|int|string> $extra
     */
    private function error(string $code, int $status, array $extra = []): JsonResponse
    {
        $response = new JsonResponse(array_replace(['error' => $code], $extra), $status);
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    private function jobResponse(ExportJob $job, int $status = Response::HTTP_OK): JsonResponse
    {
        $response = new JsonResponse([
            'identifier' => $job->getIdentifier()->toString(),
            'status' => $job->getStatus()->value,
            'attempts' => $job->getAttempts(),
            'createdAt' => $job->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $job->getUpdatedAt()->format(DATE_ATOM),
            'expiresAt' => $job->getExpiresAt()->format(DATE_ATOM),
            'failureCode' => $job->getFailureCode(),
            'result' => null === $job->getResult() ? null : [
                'filename' => $job->getResult()->getFilename(),
                'contentType' => $job->getResult()->getContentType(),
                'size' => $job->getResult()->getSize(),
            ],
        ], $status);
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }
}
