<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Contract;

use Zhortein\DatatableBundle\Export\Job\ExportJob;
use Zhortein\DatatableBundle\Export\Job\ExportJobIdentifier;

interface ExportJobRepositoryInterface
{
    /**
     * Atomically inserts a job or returns the existing owner/idempotency match.
     */
    public function create(ExportJob $job): ExportJob;

    public function save(ExportJob $job): void;

    public function find(ExportJobIdentifier $identifier): ?ExportJob;

    /**
     * Atomically changes a pending job to running and returns the claimed job.
     *
     * Returns null when the job is missing or no longer pending.
     */
    public function claim(ExportJobIdentifier $identifier, \DateTimeImmutable $now): ?ExportJob;

    public function findIdempotent(string $ownerIdentifier, string $idempotencyKey): ?ExportJob;

    /**
     * @return iterable<ExportJob>
     */
    public function findExpired(\DateTimeImmutable $now, int $limit): iterable;
}
