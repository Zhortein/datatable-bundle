<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Export\Job;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Contract\ExportJobClockInterface;
use Zhortein\DatatableBundle\Export\DatatableExportRequest;
use Zhortein\DatatableBundle\Export\Job\ExportArtifact;
use Zhortein\DatatableBundle\Export\Job\ExportJob;
use Zhortein\DatatableBundle\Export\Job\ExportJobCleanup;
use Zhortein\DatatableBundle\Export\Job\ExportJobIdentifier;
use Zhortein\DatatableBundle\Export\Job\ExportJobRequest;
use Zhortein\DatatableBundle\Export\Job\InMemoryExportJobRepository;
use Zhortein\DatatableBundle\Export\Job\InMemoryExportJobResultStorage;
use Zhortein\DatatableBundle\Request\DatatableRequest;

final class InMemoryExportJobInfrastructureTest extends TestCase
{
    public function test_repository_indexes_owner_scoped_idempotency_and_expiration(): void
    {
        $repository = new InMemoryExportJobRepository();
        $job = $this->createJob();
        $repository->save($job);

        self::assertSame($job, $repository->find($job->getIdentifier()));
        self::assertSame($job, $repository->findIdempotent('owner-1', 'request-1'));
        self::assertNull($repository->findIdempotent('owner-2', 'request-1'));
        $claimed = $repository->claim(
            $job->getIdentifier(),
            new \DateTimeImmutable('2026-07-28T08:01:00+00:00'),
        );
        self::assertNotNull($claimed);
        self::assertSame(1, $claimed->getAttempts());
        self::assertNull($repository->claim(
            $job->getIdentifier(),
            new \DateTimeImmutable('2026-07-28T08:02:00+00:00'),
        ));
        $pendingRetry = $claimed->retry(new \DateTimeImmutable('2026-07-28T08:03:00+00:00'));
        $repository->save($pendingRetry);
        self::assertSame([], iterator_to_array(
            $repository->findExpired(new \DateTimeImmutable('2026-07-28T08:30:00+00:00'), 10),
        ));
        self::assertSame([$pendingRetry], iterator_to_array(
            $repository->findExpired(new \DateTimeImmutable('2026-07-28T09:00:00+00:00'), 10),
            false,
        ));
    }

    public function test_result_storage_round_trips_chunks_and_deletes_content(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'datatable_job_test_');
        self::assertIsString($path);
        file_put_contents($path, 'abcdefghij');

        $artifact = new ExportArtifact($path, 'users.csv', 'text/csv');
        $storage = new InMemoryExportJobResultStorage(4);
        $metadata = $storage->store(
            new ExportJobIdentifier('job_1234567890abcdef'),
            $artifact,
            new \DateTimeImmutable('2026-07-28T08:00:00+00:00'),
        );

        self::assertSame(10, $metadata->getSize());
        self::assertSame(['abcd', 'efgh', 'ij'], iterator_to_array($storage->read($metadata), false));

        $storage->delete($metadata);
        $artifact->delete();

        $this->expectException(\RuntimeException::class);
        iterator_to_array($storage->read($metadata), false);
    }

    public function test_cleanup_expires_jobs_and_deletes_completed_results(): void
    {
        $repository = new InMemoryExportJobRepository();
        $storage = new InMemoryExportJobResultStorage();
        $job = $this->createJob();
        $path = tempnam(sys_get_temp_dir(), 'datatable_job_cleanup_');
        self::assertIsString($path);
        file_put_contents($path, 'result');
        $artifact = new ExportArtifact($path, 'users.csv', 'text/csv');
        $result = $storage->store(
            $job->getIdentifier(),
            $artifact,
            new \DateTimeImmutable('2026-07-28T08:10:00+00:00'),
        );
        $artifact->delete();
        $repository->save(
            $job
                ->start(new \DateTimeImmutable('2026-07-28T08:05:00+00:00'))
                ->complete(
                    $result,
                    new \DateTimeImmutable('2026-07-28T08:10:00+00:00'),
                    new \DateTimeImmutable('2026-07-28T09:00:00+00:00'),
                ),
        );
        $cleanup = new ExportJobCleanup(
            $repository,
            $storage,
            new class implements ExportJobClockInterface {
                public function now(): \DateTimeImmutable
                {
                    return new \DateTimeImmutable('2026-07-28T10:00:00+00:00');
                }
            },
        );

        self::assertSame(1, $cleanup->cleanup());
        self::assertSame(
            \Zhortein\DatatableBundle\Enum\ExportJobStatus::Expired,
            $repository->find($job->getIdentifier())?->getStatus(),
        );

        $this->expectException(\RuntimeException::class);
        iterator_to_array($storage->read($result), false);
    }

    private function createJob(): ExportJob
    {
        $createdAt = new \DateTimeImmutable('2026-07-28T08:00:00+00:00');

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
                expectedRowCount: 1,
                rowLimit: 10,
            ),
            ownerIdentifier: 'owner-1',
            idempotencyKey: 'request-1',
            createdAt: $createdAt,
            expiresAt: $createdAt->modify('+1 hour'),
        );
    }
}
