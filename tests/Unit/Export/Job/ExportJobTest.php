<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Export\Job;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Enum\ExportJobStatus;
use Zhortein\DatatableBundle\Export\DatatableExportRequest;
use Zhortein\DatatableBundle\Export\Job\ExportJob;
use Zhortein\DatatableBundle\Export\Job\ExportJobIdentifier;
use Zhortein\DatatableBundle\Export\Job\ExportJobRequest;
use Zhortein\DatatableBundle\Export\Job\ExportJobResultMetadata;
use Zhortein\DatatableBundle\Request\DatatableRequest;

final class ExportJobTest extends TestCase
{
    public function test_it_enforces_the_complete_state_lifecycle(): void
    {
        $createdAt = new \DateTimeImmutable('2026-07-28T08:00:00+00:00');
        $job = $this->createPendingJob($createdAt);

        self::assertSame(ExportJobStatus::Pending, $job->getStatus());
        self::assertSame(0, $job->getAttempts());
        self::assertTrue($job->belongsTo('owner-1'));
        self::assertFalse($job->belongsTo('owner-2'));

        $running = $job->start($createdAt->modify('+1 minute'));

        self::assertSame(ExportJobStatus::Running, $running->getStatus());
        self::assertSame(1, $running->getAttempts());

        $result = new ExportJobResultMetadata(
            storageKey: 'storage-key',
            filename: 'users.csv',
            contentType: 'text/csv',
            size: 42,
            createdAt: $createdAt->modify('+2 minutes'),
        );
        $completed = $running->complete(
            result: $result,
            now: $createdAt->modify('+2 minutes'),
            expiresAt: $createdAt->modify('+1 day'),
        );

        self::assertSame(ExportJobStatus::Completed, $completed->getStatus());
        self::assertSame($result, $completed->getResult());
        self::assertTrue($completed->getStatus()->isTerminal());

        $expired = $completed->expire($createdAt->modify('+1 day'));

        self::assertSame(ExportJobStatus::Expired, $expired->getStatus());
        self::assertNull($expired->getResult());
    }

    public function test_a_running_job_can_return_to_pending_for_a_retry(): void
    {
        $createdAt = new \DateTimeImmutable('2026-07-28T08:00:00+00:00');
        $retried = $this->createPendingJob($createdAt)
            ->start($createdAt->modify('+1 minute'))
            ->retry($createdAt->modify('+2 minutes'))
            ->start($createdAt->modify('+3 minutes'));

        self::assertSame(ExportJobStatus::Running, $retried->getStatus());
        self::assertSame(2, $retried->getAttempts());
    }

    public function test_a_running_job_can_fail_with_a_public_code(): void
    {
        $createdAt = new \DateTimeImmutable('2026-07-28T08:00:00+00:00');
        $failed = $this->createPendingJob($createdAt)
            ->start($createdAt->modify('+1 minute'))
            ->fail('export_failed', $createdAt->modify('+2 minutes'));

        self::assertSame(ExportJobStatus::Failed, $failed->getStatus());
        self::assertSame('export_failed', $failed->getFailureCode());
        self::assertTrue($failed->getStatus()->isTerminal());
    }

    public function test_invalid_transition_is_rejected(): void
    {
        $this->expectException(\LogicException::class);

        $this->createPendingJob(new \DateTimeImmutable())->retry(new \DateTimeImmutable());
    }

    private function createPendingJob(\DateTimeImmutable $createdAt): ExportJob
    {
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
                expectedRowCount: 10,
                rowLimit: 100,
            ),
            ownerIdentifier: 'owner-1',
            idempotencyKey: 'request-1',
            createdAt: $createdAt,
            expiresAt: $createdAt->modify('+1 hour'),
        );
    }
}
