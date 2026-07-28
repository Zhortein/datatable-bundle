<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export\Job;

use Symfony\Contracts\Translation\LocaleAwareInterface;
use Zhortein\DatatableBundle\Contract\ExportArtifactWriterInterface;
use Zhortein\DatatableBundle\Contract\ExportJobClockInterface;
use Zhortein\DatatableBundle\Contract\ExportJobExpiryPolicyInterface;
use Zhortein\DatatableBundle\Contract\ExportJobRepositoryInterface;
use Zhortein\DatatableBundle\Contract\ExportJobResultStorageInterface;
use Zhortein\DatatableBundle\Contract\StreamingDataProviderInterface;
use Zhortein\DatatableBundle\Enum\ExportJobStatus;
use Zhortein\DatatableBundle\Exception\ExportException;
use Zhortein\DatatableBundle\Exception\RetryableExportJobException;
use Zhortein\DatatableBundle\Export\ExportRow;
use Zhortein\DatatableBundle\Export\ExportStreamContext;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\Provider\DataProviderRegistry;
use Zhortein\DatatableBundle\Export\ExportWriterRegistry;

final readonly class ExportJobRunner
{
    public function __construct(
        private ExportJobRepositoryInterface $repository,
        private ExportJobResultStorageInterface $resultStorage,
        private ExportJobClockInterface $clock,
        private ExportJobExpiryPolicyInterface $expiryPolicy,
        private DatatableDefinitionFactory $definitionFactory,
        private DataProviderRegistry $providerRegistry,
        private ExportWriterRegistry $writerRegistry,
        private int $batchSize,
        private int $maxAttempts,
        private ?LocaleAwareInterface $localeAware = null,
    ) {
        if ($this->batchSize < 1) {
            throw new \InvalidArgumentException('The background export batch size must be greater than or equal to 1.');
        }

        if ($this->maxAttempts < 1) {
            throw new \InvalidArgumentException('The background export maximum attempt count must be greater than or equal to 1.');
        }
    }

    public function run(ExportJobIdentifier $identifier): void
    {
        $job = $this->repository->find($identifier);

        if (null === $job || $job->getStatus()->isTerminal() || ExportJobStatus::Running === $job->getStatus()) {
            return;
        }

        $now = $this->clock->now();

        if ($job->isExpiredAt($now)) {
            $this->repository->save($job->expire($now));

            return;
        }

        $job = $this->repository->claim($identifier, $now);

        if (null === $job) {
            return;
        }

        $artifact = null;
        $previousLocale = $this->localeAware?->getLocale();

        try {
            $request = $job->getRequest();
            $exportRequest = $request->getExportRequest();
            $datatableRequest = $exportRequest->getDatatableRequest();

            if (null === $datatableRequest) {
                throw new ExportException('The background export job does not contain a canonical datatable request.');
            }

            $this->localeAware?->setLocale($request->getLocale());

            $definition = $this->definitionFactory->create($exportRequest->getDatatableName());
            $definition->setContext(
                $definition->getContext()->withBrowserValues($request->getContextValues()),
            );

            $provider = $this->providerRegistry->resolve($definition);
            $writer = $this->writerRegistry->resolve($exportRequest->getFormat());

            if (!$provider instanceof StreamingDataProviderInterface) {
                throw new ExportException('The selected data provider does not support background export streaming.');
            }

            if (!$writer instanceof ExportArtifactWriterInterface) {
                throw new ExportException('The selected export writer does not support background artifacts.');
            }

            $effectiveRequest = $exportRequest->shouldKeepPagination()
                ? $datatableRequest
                : $datatableRequest->withoutPagination();
            $streamContext = new ExportStreamContext(
                batchSize: $this->batchSize,
                expectedRowCount: $request->getExpectedRowCount(),
                cancellation: new NeverCancelledExportCancellation(),
            );

            $artifact = $writer->writeArtifact(
                request: $exportRequest,
                definition: $definition,
                rows: $this->guardRows(
                    $provider->streamExportRows($definition, $effectiveRequest, $streamContext),
                    $request->getRowLimit(),
                ),
                context: $streamContext,
            );

            $completedAt = $this->clock->now();
            $result = $this->resultStorage->store($identifier, $artifact, $completedAt);

            $this->repository->save($job->complete(
                result: $result,
                now: $completedAt,
                expiresAt: $this->expiryPolicy->expiresAt($completedAt),
            ));
        } catch (\Throwable $exception) {
            $failedAt = $this->clock->now();

            if ($job->getAttempts() < $this->maxAttempts) {
                $this->repository->save($job->retry($failedAt));

                throw new RetryableExportJobException('The export job failed and may be retried.', previous: $exception);
            }

            $this->repository->save($job->fail($this->failureCode($exception), $failedAt));
        } finally {
            if (null !== $artifact) {
                try {
                    $artifact->delete();
                } catch (\RuntimeException) {
                    // Result persistence already decided the job state.
                }
            }

            if (null !== $previousLocale) {
                $this->localeAware?->setLocale($previousLocale);
            }
        }
    }

    /**
     * @param iterable<ExportRow> $rows
     *
     * @return iterable<ExportRow>
     */
    private function guardRows(iterable $rows, int $limit): iterable
    {
        $rowCount = 0;

        foreach ($rows as $row) {
            ++$rowCount;

            if ($rowCount > $limit) {
                throw new ExportException(sprintf('The streaming provider exceeded the configured %d-row background export limit.', $limit));
            }

            yield $row;
        }
    }

    private function failureCode(\Throwable $exception): string
    {
        return $exception instanceof ExportException
            ? 'export_failed'
            : 'internal_error';
    }
}
