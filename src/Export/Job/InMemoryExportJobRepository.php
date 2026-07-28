<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export\Job;

use Zhortein\DatatableBundle\Contract\ExportJobRepositoryInterface;
use Zhortein\DatatableBundle\Enum\ExportJobStatus;

/**
 * Deterministic process-local implementation intended for tests and examples.
 */
final class InMemoryExportJobRepository implements ExportJobRepositoryInterface
{
    /**
     * @var array<string, ExportJob>
     */
    private array $jobs = [];

    /**
     * @var array<string, string>
     */
    private array $idempotencyIndex = [];

    public function create(ExportJob $job): ExportJob
    {
        if (null !== $job->getIdempotencyKey()) {
            $existing = $this->findIdempotent(
                $job->getOwnerIdentifier(),
                $job->getIdempotencyKey(),
            );

            if (null !== $existing) {
                return $existing;
            }
        }

        if (null !== $this->find($job->getIdentifier())) {
            throw new \RuntimeException(sprintf('The export job "%s" already exists.', $job->getIdentifier()->toString()));
        }

        $this->save($job);

        return $job;
    }

    public function save(ExportJob $job): void
    {
        $identifier = $job->getIdentifier()->toString();
        $this->jobs[$identifier] = $job;

        if (null !== $job->getIdempotencyKey()) {
            $this->idempotencyIndex[$this->idempotencyIndexKey(
                $job->getOwnerIdentifier(),
                $job->getIdempotencyKey(),
            )] = $identifier;
        }
    }

    public function find(ExportJobIdentifier $identifier): ?ExportJob
    {
        return $this->jobs[$identifier->toString()] ?? null;
    }

    public function claim(ExportJobIdentifier $identifier, \DateTimeImmutable $now): ?ExportJob
    {
        $job = $this->find($identifier);

        if (null === $job || ExportJobStatus::Pending !== $job->getStatus()) {
            return null;
        }

        $job = $job->start($now);
        $this->save($job);

        return $job;
    }

    public function findIdempotent(string $ownerIdentifier, string $idempotencyKey): ?ExportJob
    {
        $identifier = $this->idempotencyIndex[$this->idempotencyIndexKey(
            $ownerIdentifier,
            $idempotencyKey,
        )] ?? null;

        return null === $identifier ? null : ($this->jobs[$identifier] ?? null);
    }

    public function findExpired(\DateTimeImmutable $now, int $limit): iterable
    {
        if ($limit < 1) {
            throw new \InvalidArgumentException('The export job cleanup limit must be greater than or equal to 1.');
        }

        $matched = 0;

        foreach ($this->jobs as $job) {
            if ($matched >= $limit) {
                return;
            }

            if (
                !in_array($job->getStatus(), [ExportJobStatus::Running, ExportJobStatus::Expired], true)
                && $job->isExpiredAt($now)
            ) {
                ++$matched;

                yield $job;
            }
        }
    }

    private function idempotencyIndexKey(string $ownerIdentifier, string $idempotencyKey): string
    {
        return hash('sha256', $ownerIdentifier."\0".$idempotencyKey);
    }
}
