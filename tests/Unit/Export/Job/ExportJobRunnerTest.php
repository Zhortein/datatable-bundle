<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Export\Job;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Zhortein\DatatableBundle\Contract\DataProviderInterface;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Contract\ExportJobClockInterface;
use Zhortein\DatatableBundle\Contract\ExportRowCountProviderInterface;
use Zhortein\DatatableBundle\Contract\StreamingDataProviderInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\ExportJobStatus;
use Zhortein\DatatableBundle\Exception\RetryableExportJobException;
use Zhortein\DatatableBundle\Export\CsvExportWriter;
use Zhortein\DatatableBundle\Export\DatatableExportRequest;
use Zhortein\DatatableBundle\Export\ExportRow;
use Zhortein\DatatableBundle\Export\ExportStreamContext;
use Zhortein\DatatableBundle\Export\ExportWriterRegistry;
use Zhortein\DatatableBundle\Export\Job\ExportJob;
use Zhortein\DatatableBundle\Export\Job\ExportJobIdentifier;
use Zhortein\DatatableBundle\Export\Job\ExportJobRequest;
use Zhortein\DatatableBundle\Export\Job\ExportJobRunner;
use Zhortein\DatatableBundle\Export\Job\FixedExportJobExpiryPolicy;
use Zhortein\DatatableBundle\Export\Job\InMemoryExportJobRepository;
use Zhortein\DatatableBundle\Export\Job\InMemoryExportJobResultStorage;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\Provider\DataProviderRegistry;
use Zhortein\DatatableBundle\Registry\DatatableRegistry;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Result\DatatableResult;

final class ExportJobRunnerTest extends TestCase
{
    public function test_it_generates_and_stores_a_streamed_csv_artifact(): void
    {
        $repository = new InMemoryExportJobRepository();
        $storage = new InMemoryExportJobResultStorage();
        $clock = new MutableExportJobClock(new \DateTimeImmutable('2026-07-28T08:00:00+00:00'));
        $job = $this->createJob($clock->now());
        $repository->save($job);

        $runner = $this->createRunner(
            repository: $repository,
            storage: $storage,
            clock: $clock,
            provider: new RunnerStreamingProvider(),
        );
        $clock->set(new \DateTimeImmutable('2026-07-28T08:01:00+00:00'));
        $runner->run($job->getIdentifier());

        $completed = $repository->find($job->getIdentifier());

        self::assertNotNull($completed);
        self::assertSame(ExportJobStatus::Completed, $completed->getStatus());
        self::assertSame(1, $completed->getAttempts());
        $result = $completed->getResult();
        self::assertNotNull($result);

        $content = implode('', iterator_to_array($storage->read($result), false));

        self::assertStringContainsString('Email', $content);
        self::assertStringContainsString('alice@example.test', $content);
        self::assertStringContainsString('bob@example.test', $content);

        $runner->run($job->getIdentifier());
        self::assertSame(1, $repository->find($job->getIdentifier())?->getAttempts());
    }

    public function test_it_requeues_a_transient_failure_then_marks_the_final_attempt_failed(): void
    {
        $repository = new InMemoryExportJobRepository();
        $storage = new InMemoryExportJobResultStorage();
        $clock = new MutableExportJobClock(new \DateTimeImmutable('2026-07-28T08:00:00+00:00'));
        $job = $this->createJob($clock->now());
        $repository->save($job);
        $runner = $this->createRunner(
            repository: $repository,
            storage: $storage,
            clock: $clock,
            provider: new FailingRunnerStreamingProvider(),
            maxAttempts: 2,
        );

        try {
            $runner->run($job->getIdentifier());
            self::fail('The first transient failure must be exposed to Messenger for retry.');
        } catch (RetryableExportJobException) {
            $retried = $repository->find($job->getIdentifier());
            self::assertNotNull($retried);
            self::assertSame(ExportJobStatus::Pending, $retried->getStatus());
            self::assertSame(1, $retried->getAttempts());
        }

        $runner->run($job->getIdentifier());
        $failed = $repository->find($job->getIdentifier());

        self::assertNotNull($failed);
        self::assertSame(ExportJobStatus::Failed, $failed->getStatus());
        self::assertSame(2, $failed->getAttempts());
        self::assertSame('internal_error', $failed->getFailureCode());
    }

    public function test_runtime_row_limit_still_guards_an_undercounting_provider(): void
    {
        $repository = new InMemoryExportJobRepository();
        $storage = new InMemoryExportJobResultStorage();
        $clock = new MutableExportJobClock(new \DateTimeImmutable('2026-07-28T08:00:00+00:00'));
        $job = $this->createJob($clock->now(), expectedRows: 1, rowLimit: 1);
        $repository->save($job);
        $runner = $this->createRunner(
            repository: $repository,
            storage: $storage,
            clock: $clock,
            provider: new RunnerStreamingProvider(),
            maxAttempts: 1,
        );

        $runner->run($job->getIdentifier());

        $failed = $repository->find($job->getIdentifier());
        self::assertNotNull($failed);
        self::assertSame(ExportJobStatus::Failed, $failed->getStatus());
        self::assertSame('export_failed', $failed->getFailureCode());
    }

    private function createRunner(
        InMemoryExportJobRepository $repository,
        InMemoryExportJobResultStorage $storage,
        MutableExportJobClock $clock,
        DataProviderInterface $provider,
        int $maxAttempts = 3,
    ): ExportJobRunner {
        return new ExportJobRunner(
            repository: $repository,
            resultStorage: $storage,
            clock: $clock,
            expiryPolicy: new FixedExportJobExpiryPolicy(3600),
            definitionFactory: new DatatableDefinitionFactory($this->createRegistry()),
            providerRegistry: new DataProviderRegistry(['test' => $provider], 'test'),
            writerRegistry: new ExportWriterRegistry(['csv' => new CsvExportWriter()]),
            batchSize: 2,
            maxAttempts: $maxAttempts,
        );
    }

    private function createRegistry(): DatatableRegistry
    {
        $datatable = new RunnerDatatable();

        return new DatatableRegistry(
            new ServiceLocator([
                'users' => static fn (): RunnerDatatable => $datatable,
            ]),
            ['users' => RunnerDatatable::class],
        );
    }

    private function createJob(
        \DateTimeImmutable $createdAt,
        int $expectedRows = 2,
        int $rowLimit = 10,
    ): ExportJob {
        return ExportJob::pending(
            identifier: new ExportJobIdentifier('job_1234567890abcdef'),
            request: new ExportJobRequest(
                exportRequest: new DatatableExportRequest(
                    datatableName: 'users',
                    datatableRequest: new DatatableRequest(),
                ),
                instance: 'users',
                childDatatable: false,
                contextValues: [],
                locale: 'en',
                expectedRowCount: $expectedRows,
                rowLimit: $rowLimit,
            ),
            ownerIdentifier: 'owner-1',
            idempotencyKey: null,
            createdAt: $createdAt,
            expiresAt: $createdAt->modify('+1 hour'),
        );
    }
}

final class MutableExportJobClock implements ExportJobClockInterface
{
    public function __construct(
        private \DateTimeImmutable $now,
    ) {
    }

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }

    public function set(\DateTimeImmutable $now): void
    {
        $this->now = $now;
    }
}

final class RunnerDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition->addColumn('e.email', label: 'Email');
    }
}

class RunnerStreamingProvider implements DataProviderInterface, ExportRowCountProviderInterface, StreamingDataProviderInterface
{
    public function supports(DatatableDefinition $definition): bool
    {
        return true;
    }

    public function getData(DatatableDefinition $definition, DatatableRequest $request): DatatableResult
    {
        return new DatatableResult();
    }

    public function countExportRows(DatatableDefinition $definition, DatatableRequest $request): int
    {
        return 2;
    }

    public function streamExportRows(
        DatatableDefinition $definition,
        DatatableRequest $request,
        ExportStreamContext $context,
    ): iterable {
        yield new ExportRow(['e_email' => 'alice@example.test']);
        yield new ExportRow(['e_email' => 'bob@example.test']);
    }
}

final class FailingRunnerStreamingProvider extends RunnerStreamingProvider
{
    public function streamExportRows(
        DatatableDefinition $definition,
        DatatableRequest $request,
        ExportStreamContext $context,
    ): iterable {
        throw new \RuntimeException('Sensitive upstream detail.');
    }
}
